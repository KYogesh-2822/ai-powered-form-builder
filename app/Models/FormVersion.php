<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $form_id
 * @property int|null $user_id
 * @property int $version
 * @property string $source
 * @property string $summary
 * @property string $title
 * @property string|null $description
 * @property array<string, mixed> $schema
 */
#[Fillable([
    'form_id',
    'user_id',
    'version',
    'source',
    'summary',
    'title',
    'description',
    'schema',
    'created_at',
])]
class FormVersion extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['schema' => 'array'];
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
}
