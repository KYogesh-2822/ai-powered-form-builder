<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\User;
use App\Services\FormSchema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(['email' => 'demo@formforge.test'], [
            'name' => 'Demo Builder',
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        $schema = app(FormSchema::class)->blank();
        $schema['sections'][0]['title'] = 'Event registration';
        $schema['sections'][0]['description'] = 'Tell us how to prepare for your visit.';
        $schema['sections'][0]['fields'][0]['required'] = true;
        $schema['sections'][0]['fields'][1]['required'] = true;

        Form::query()->updateOrCreate(['slug' => 'event-registration-demo'], [
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Event registration',
            'description' => 'A seeded form that is ready to share.',
            'status' => 'published',
            'schema' => $schema,
            'settings' => [],
            'published_at' => now(),
        ]);
    }
}
