<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\FormSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicFormController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $form = Form::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return Inertia::render('public/forms/show', [
            'form' => $form->only(['title', 'slug', 'description', 'schema']),
            'submitted' => (bool) $request->session()->pull('submitted', false),
        ]);
    }

    public function submit(Request $request, string $slug, FormSchema $schemas): RedirectResponse
    {
        $form = Form::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        if ($request->filled('website')) {
            throw ValidationException::withMessages(['form' => 'Unable to accept this response.']);
        }

        $schema = $schemas->validateAndNormalize($form->schema);
        $rules = $schemas->rules($schema);
        $rules['started_at'] = ['nullable', 'date'];
        $validated = $request->validate($rules);
        $answers = is_array($validated['answers'] ?? null) ? $validated['answers'] : [];

        foreach ($schemas->fields($schema) as $field) {
            if ($field['type'] !== 'file' || ! $request->hasFile('answers.'.$field['key'])) {
                continue;
            }

            $file = $request->file('answers.'.$field['key']);
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $answers[$field['key']] = [
                'path' => $file->store("form-submissions/{$form->id}", 'public'),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
        }

        $searchableAnswers = [];
        foreach ($answers as $answer) {
            $searchableAnswers[] = is_scalar($answer)
                ? (string) $answer
                : json_encode($answer, JSON_THROW_ON_ERROR);
        }

        FormSubmission::query()->create([
            'form_id' => $form->id,
            'reference' => (string) Str::uuid(),
            'answers' => $answers,
            'searchable_text' => implode(' ', $searchableAnswers),
            'status' => 'complete',
            'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'started_at' => $request->date('started_at'),
            'submitted_at' => now(),
        ]);

        return to_route('public.forms.show', $form->slug)->with('submitted', true);
    }
}
