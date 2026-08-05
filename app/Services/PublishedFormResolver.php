<?php

namespace App\Services;

use App\Models\Form;
use Illuminate\Support\Facades\Cache;

class PublishedFormResolver
{
    /** @return array<string, mixed> */
    public function payload(string $slug): array
    {
        $payload = Cache::remember(
            self::cacheKey($slug),
            now()->addMinutes(5),
            fn (): ?array => Form::query()
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first()
                ?->only(['title', 'slug', 'description', 'schema']),
        );

        abort_unless(is_array($payload), 404);

        return $payload;
    }

    public function forget(string $slug): void
    {
        Cache::forget(self::cacheKey($slug));
    }

    private static function cacheKey(string $slug): string
    {
        return 'published-form:'.hash('sha256', $slug);
    }
}
