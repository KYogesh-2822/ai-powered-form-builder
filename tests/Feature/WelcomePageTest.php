<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Services\FormSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_only_published_forms(): void
    {
        $user = User::factory()->create();
        $published = $this->createForm($user, 'Public feedback', 'public-feedback', 'published');
        $this->createForm($user, 'Private draft', 'private-draft', 'draft');

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->has('forms.data', 1)
                ->where('forms.data.0.title', 'Public feedback')
                ->where('forms.data.0.slug', 'public-feedback')
                ->where('forms.data.0.public_url', route('public.forms.show', $published->slug))
                ->where('forms.data.0.sections_count', 1)
                ->where('forms.data.0.fields_count', 2)
                ->where('filters.search', ''));
    }

    public function test_guests_can_open_published_forms_but_not_management_pages(): void
    {
        $user = User::factory()->create();
        $published = $this->createForm($user, 'Public registration', 'public-registration', 'published');

        $this->get(route('public.forms.show', $published->slug))->assertOk();
        $this->get(route('forms.edit', $published))->assertRedirect(route('login'));
    }

    private function createForm(User $user, string $title, string $slug, string $status): Form
    {
        return Form::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'slug' => $slug,
            'description' => "Description for {$title}",
            'status' => $status,
            'schema' => app(FormSchema::class)->blank(),
            'settings' => [],
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }
}
