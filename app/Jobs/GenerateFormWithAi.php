<?php

namespace App\Jobs;

use App\Models\FormAutomation;
use App\Services\FormVersionManager;
use App\Services\OpenAiFormGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateFormWithAi implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public readonly int $automationId)
    {
        $this->onQueue('ai');
    }

    public function handle(OpenAiFormGenerator $generator, FormVersionManager $versions): void
    {
        $automation = FormAutomation::query()->with('form')->findOrFail($this->automationId);
        $automation->update([
            'status' => 'running',
            'started_at' => now(),
            'error' => null,
        ]);

        $mode = (string) ($automation->input['mode'] ?? 'generate');
        $prompt = (string) ($automation->input['prompt'] ?? '');
        $existing = $mode === 'edit' ? $automation->form->schema : null;
        $safetyIdentifier = hash_hmac('sha256', 'user:'.$automation->user_id, (string) config('app.key'));

        $result = $generator->generate($prompt, $existing, $safetyIdentifier);

        DB::transaction(function () use ($automation, $result, $versions, $mode): void {
            $versions->update(
                $automation->form,
                ['schema' => $result['schema']],
                $automation->user_id,
                'ai',
                $mode === 'edit' ? 'AI-assisted form edit' : 'AI-generated form',
            );
            $automation->update([
                'status' => 'completed',
                'result_schema' => $result['schema'],
                'provider' => 'openai',
                'model' => $result['model'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'latency_ms' => $result['latency_ms'],
                'completed_at' => now(),
                'applied_at' => now(),
            ]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        FormAutomation::query()->whereKey($this->automationId)->update([
            'status' => 'failed',
            'error' => mb_substr($exception?->getMessage() ?? 'AI generation failed.', 0, 2000),
            'completed_at' => now(),
        ]);
    }
}
