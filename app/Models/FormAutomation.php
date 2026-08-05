<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $form_id
 * @property int $user_id
 * @property string $type
 * @property string $status
 * @property array<string, mixed> $input
 * @property array<string, mixed>|null $result_schema
 */
#[Fillable([
    'uuid',
    'form_id',
    'user_id',
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
    'started_at',
    'completed_at',
    'applied_at',
])]
class FormAutomation extends Model
{
    protected function casts(): array
    {
        return [
            'input' => 'array',
            'result_schema' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Form, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
