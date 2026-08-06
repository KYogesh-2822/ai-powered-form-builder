<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateFormWithAi;
use App\Models\Form;
use App\Models\FormAutomation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AiFormController extends Controller
{
    public function store(Request $request, Form $form): RedirectResponse
    {
        Gate::authorize('update', $form);
        $data = $request->validate([
            'mode' => ['required', Rule::in(['generate', 'edit'])],
            'prompt' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        $automation = FormAutomation::query()->create([
            'uuid' => (string) Str::uuid(),
            'form_id' => $form->id,
            'user_id' => $request->user()->id,
            'type' => 'ai_'.$data['mode'],
            'status' => 'queued',
            'input' => $data,
        ]);

        GenerateFormWithAi::dispatch($automation->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'AI request queued. The builder will update when it is ready.',
        ]);

        return back();
    }
}
