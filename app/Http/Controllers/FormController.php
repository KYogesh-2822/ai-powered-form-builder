<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\FormSchema;
use App\Services\FormVersionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FormController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $forms = Form::query()
            ->whereBelongsTo($request->user())
            ->withCount('submissions')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('forms/index', [
            'forms' => $forms,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request, FormSchema $schemas, FormVersionManager $versions): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
        ]);

        $title = trim((string) ($data['title'] ?? '')) ?: 'Untitled form';
        $form = Form::query()->create([
            'user_id' => $request->user()->id,
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'slug' => $this->uniqueSlug($title),
            'status' => 'draft',
            'schema' => $schemas->blank(),
            'settings' => [],
        ]);
        $versions->recordInitial($form, $request->user()->id);

        return to_route('forms.edit', $form)
            ->with('toast', ['type' => 'success', 'message' => 'Form created.']);
    }

    public function edit(Form $form): Response
    {
        Gate::authorize('update', $form);
        $automation = $form->automations()->latest()->first();

        return Inertia::render('forms/builder', [
            'form' => [
                ...$form->only(['uuid', 'title', 'slug', 'description', 'status', 'schema', 'version', 'published_at', 'updated_at']),
                'submissions_count' => $form->submissions()->count(),
                'public_url' => route('public.forms.show', $form->slug),
            ],
            'fieldTypes' => FormSchema::FIELD_TYPES,
            'latestAutomation' => $automation?->only([
                'uuid',
                'type',
                'status',
                'input',
                'result_schema',
                'provider',
                'model',
                'input_tokens',
                'output_tokens',
                'latency_ms',
                'error',
                'created_at',
                'completed_at',
                'applied_at',
            ]),
            'versions' => $form->versions()
                ->with('user:id,name')
                ->latest('version')
                ->limit(15)
                ->get(['id', 'form_id', 'user_id', 'version', 'source', 'summary', 'created_at']),
        ]);
    }

    public function update(
        Request $request,
        Form $form,
        FormSchema $schemas,
        FormVersionManager $versions,
    ): RedirectResponse {
        Gate::authorize('update', $form);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'schema' => ['required', 'array'],
        ]);

        $versions->update(
            $form,
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'schema' => $schemas->validateAndNormalize($data['schema']),
            ],
            $request->user()->id,
            'manual',
            'Builder changes saved',
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'All changes saved.']);
    }

    public function publish(Form $form, FormSchema $schemas): RedirectResponse
    {
        Gate::authorize('update', $form);
        $schema = $schemas->validateAndNormalize($form->schema);
        $publishing = $form->status !== 'published';

        $form->update([
            'schema' => $schema,
            'status' => $publishing ? 'published' : 'draft',
            'published_at' => $publishing ? now() : null,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $publishing ? 'Form published.' : 'Form moved back to draft.',
        ]);
    }

    public function updateSlug(Request $request, Form $form): RedirectResponse
    {
        Gate::authorize('update', $form);
        $data = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('forms', 'slug')->ignore($form->id),
            ],
        ]);
        $form->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => 'Public URL updated.']);
    }

    public function destroy(Form $form): RedirectResponse
    {
        Gate::authorize('delete', $form);
        $form->delete();

        return to_route('forms.index')
            ->with('toast', ['type' => 'success', 'message' => 'Form deleted.']);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'form';
        $slug = $base;
        $counter = 2;

        while (Form::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
