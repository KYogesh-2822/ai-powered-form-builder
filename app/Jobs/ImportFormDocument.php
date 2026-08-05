<?php

namespace App\Jobs;

use App\Models\FormAutomation;
use App\Services\DocumentFormImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportFormDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public readonly int $automationId)
    {
        $this->onQueue('imports');
    }

    public function handle(DocumentFormImporter $importer): void
    {
        $automation = FormAutomation::query()->findOrFail($this->automationId);
        $automation->update([
            'status' => 'running',
            'started_at' => now(),
            'error' => null,
        ]);

        $started = microtime(true);
        $path = (string) ($automation->input['path'] ?? '');
        $extension = (string) ($automation->input['extension'] ?? '');
        $schema = $importer->import(Storage::disk('local')->path($path), $extension);

        $automation->update([
            'status' => 'completed',
            'result_schema' => $schema,
            'provider' => 'deterministic-parser',
            'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        FormAutomation::query()->whereKey($this->automationId)->update([
            'status' => 'failed',
            'error' => mb_substr($exception?->getMessage() ?? 'Document import failed.', 0, 2000),
            'completed_at' => now(),
        ]);
    }
}
