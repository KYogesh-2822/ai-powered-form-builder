<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $form_id
 * @property string $reference
 * @property array<string, mixed> $answers
 * @property Carbon|null $submitted_at
 */
#[Fillable([
    'form_id',
    'reference',
    'answers',
    'searchable_text',
    'status',
    'ip_hash',
    'user_agent',
    'started_at',
    'submitted_at',
])]
class FormSubmission extends Model
{
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Form, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
