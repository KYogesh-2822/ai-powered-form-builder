<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $forms = Form::query()
            ->where('status', 'published')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(function (Form $form): array {
                $sections = $form->schema['sections'] ?? [];
                $sections = is_array($sections) ? $sections : [];
                $fieldsCount = 0;

                foreach ($sections as $section) {
                    if (is_array($section) && is_array($section['fields'] ?? null)) {
                        $fieldsCount += count($section['fields']);
                    }
                }

                return [
                    'title' => $form->title,
                    'slug' => $form->slug,
                    'description' => $form->description ? Str::limit($form->description, 180) : null,
                    'public_url' => route('public.forms.show', $form->slug),
                    'sections_count' => count($sections),
                    'fields_count' => $fieldsCount,
                    'published_at' => $form->published_at?->toISOString(),
                ];
            });

        return Inertia::render('welcome', [
            'forms' => $forms,
            'filters' => ['search' => $search],
        ]);
    }
}
