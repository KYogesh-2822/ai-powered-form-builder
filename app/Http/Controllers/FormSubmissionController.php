<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\FormSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    public function index(Request $request, Form $form, FormSchema $schemas): Response
    {
        Gate::authorize('view', $form);
        $search = trim((string) $request->string('search'));

        return Inertia::render('submissions/index', [
            'form' => $form->only(['uuid', 'title']),
            'fields' => collect($schemas->fields($form->schema))
                ->where('type', '!=', 'heading')
                ->map(fn (array $field): array => Arr::only($field, ['key', 'label', 'type']))
                ->values(),
            'submissions' => $form->submissions()
                ->when($search !== '', fn ($query) => $query->where('searchable_text', 'like', "%{$search}%"))
                ->latest('submitted_at')
                ->paginate(20)
                ->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function export(Form $form, FormSchema $schemas): StreamedResponse
    {
        Gate::authorize('view', $form);
        $fields = collect($schemas->fields($form->schema))->where('type', '!=', 'heading')->values();

        return response()->streamDownload(function () use ($form, $fields): void {
            $output = fopen('php://output', 'wb');
            if (! is_resource($output)) {
                throw new \RuntimeException('Unable to open CSV output stream.');
            }
            fputcsv($output, ['Reference', 'Submitted at', ...$fields->pluck('label')->all()]);

            $form->submissions()->latest('submitted_at')->chunkById(500, function ($submissions) use ($output, $fields): void {
                foreach ($submissions as $submission) {
                    $row = [$submission->reference, $submission->submitted_at?->toIso8601String()];
                    foreach ($fields as $field) {
                        $value = data_get($submission->answers, $field['key'], '');
                        $row[] = is_scalar($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR);
                    }
                    fputcsv($output, $row);
                }
            });

            fclose($output);
        }, $form->slug.'-submissions.csv', ['Content-Type' => 'text/csv']);
    }
}
