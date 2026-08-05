<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormVersionManager
{
    public function recordInitial(Form $form, int $userId): void
    {
        $this->snapshot($form->refresh(), $userId, 'created', 'Initial form');
    }

    /** @param array<string, mixed> $attributes */
    public function update(
        Form $form,
        array $attributes,
        int $userId,
        string $source,
        string $summary,
    ): Form {
        DB::transaction(function () use ($form, $attributes, $userId, $source, $summary): void {
            $locked = Form::query()->lockForUpdate()->findOrFail($form->id);
            $locked->update([
                ...$attributes,
                'version' => $locked->version + 1,
            ]);
            $this->snapshot($locked, $userId, $source, $summary);
        });

        return $form->refresh();
    }

    public function restore(Form $form, FormVersion $version, int $userId): Form
    {
        abort_unless($version->form_id === $form->id, 404);

        return $this->update(
            $form,
            [
                'title' => $version->title,
                'description' => $version->description,
                'schema' => $version->schema,
            ],
            $userId,
            'rollback',
            "Restored version {$version->version}",
        );
    }

    private function snapshot(Form $form, int $userId, string $source, string $summary): void
    {
        FormVersion::query()->create([
            'form_id' => $form->id,
            'user_id' => $userId,
            'version' => $form->version,
            'source' => Str::limit($source, 30, ''),
            'summary' => Str::limit($summary, 255, ''),
            'title' => $form->title,
            'description' => $form->description,
            'schema' => $form->schema,
            'created_at' => now(),
        ]);
    }
}
