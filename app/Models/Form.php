<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $uuid
 * @property string $title
 * @property string $slug
 * @property string $status
 * @property array<string, mixed> $schema
 * @property array<string, mixed>|null $settings
 * @property int $version
 */
#[Fillable([
    'user_id',
    'uuid',
    'title',
    'slug',
    'description',
    'status',
    'schema',
    'settings',
    'version',
    'published_at',
])]
class Form extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'settings' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<FormSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /** @return HasMany<FormAutomation, $this> */
    public function automations(): HasMany
    {
        return $this->hasMany(FormAutomation::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
