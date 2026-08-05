<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormAutomation;
use App\Services\FormSchema;
use App\Services\FormVersionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FormAutomationController extends Controller
{
    public function latest(Form $form): JsonResponse
    {
        Gate::authorize('view', $form);
        $automation = $form->automations()->latest()->first();

        return response()->json([
            'automation' => $automation?->only([
                'uuid',
                'type',
                'status',
                'model',
                'input_tokens',
                'output_tokens',
                'latency_ms',
                'error',
                'result_schema',
                'completed_at',
                'applied_at',
            ]),
        ]);
    }

    public function apply(
        Request $request,
        FormAutomation $automation,
        FormSchema $schemas,
        FormVersionManager $versions,
    ): RedirectResponse {
        Gate::authorize('update', $automation->form);
        abort_unless($automation->status === 'completed' && is_array($automation->result_schema), 422);
        $data = $request->validate(['schema' => ['nullable', 'array']]);
        $candidate = is_array($data['schema'] ?? null) ? $data['schema'] : $automation->result_schema;
        $normalized = $schemas->validateAndNormalize($candidate);

        $versions->update(
            $automation->form,
            ['schema' => $normalized],
            $request->user()->id,
            $automation->type === 'import' ? 'import' : 'ai',
            $automation->type === 'import' ? 'Reviewed document import' : 'Applied AI schema',
        );
        $automation->update([
            'result_schema' => $normalized,
            'applied_at' => now(),
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Generated schema applied to the form.']);
    }
}
