<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Services\FormSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FormBuilderActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_preview_unsaved_draft_changes_without_persisting_them(): void
    {
        [$user, $form] = $this->formOwnedByAUser();
        $payload = $this->builderPayload('Unsaved preview');
        $payload['schema']['sections'][0]['fields'][0]['label'] = 'Unsaved question';

        $this->actingAs($user)
            ->from(route('forms.edit', $form))
            ->post(route('forms.preview.prepare', $form), $payload)
            ->assertRedirect(route('forms.edit', $form));

        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'title' => 'Builder actions test',
            'status' => 'draft',
        ]);
        $this->actingAs($user)
            ->get(route('forms.preview', $form))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/forms/show')
                ->where('preview', true)
                ->where('form.title', 'Unsaved preview')
                ->where('form.schema.sections.0.fields.0.label', 'Unsaved question'));
        $this->get(route('public.forms.show', $form->slug))->assertNotFound();
    }

    public function test_save_persists_builder_changes_and_flashes_success_feedback(): void
    {
        [$user, $form] = $this->formOwnedByAUser();

        $response = $this->actingAs($user)
            ->from(route('forms.edit', $form))
            ->put(route('forms.update', $form), $this->builderPayload('Saved builder'));

        $response
            ->assertRedirect(route('forms.edit', $form))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'All changes saved.');
        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'title' => 'Saved builder',
            'version' => 2,
        ]);
    }

    public function test_publish_saves_current_builder_changes_and_makes_the_public_url_available(): void
    {
        [$user, $form] = $this->formOwnedByAUser();
        $payload = $this->builderPayload('Published builder');
        $payload['schema']['sections'][0]['fields'][0]['label'] = 'Published question';

        $response = $this->actingAs($user)
            ->from(route('forms.edit', $form))
            ->patch(route('forms.publish', $form), $payload);

        $response
            ->assertRedirect(route('forms.edit', $form))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Form published.');

        $form->refresh();
        $this->assertSame('published', $form->status);
        $this->assertSame('Published builder', $form->title);
        $this->assertSame('Published question', $form->schema['sections'][0]['fields'][0]['label']);
        $this->assertSame(2, $form->version);
        $this->assertNotNull($form->published_at);
        $this->assertDatabaseHas('form_versions', [
            'form_id' => $form->id,
            'version' => 2,
            'source' => 'publish',
        ]);
        $this->get(route('public.forms.show', $form->slug))->assertOk();
    }

    public function test_another_user_cannot_prepare_or_view_a_private_preview(): void
    {
        [, $form] = $this->formOwnedByAUser();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post(route('forms.preview.prepare', $form), $this->builderPayload('Forbidden'))
            ->assertForbidden();
        $this->actingAs($otherUser)
            ->get(route('forms.preview', $form))
            ->assertForbidden();
    }

    /** @return array{User, Form} */
    private function formOwnedByAUser(): array
    {
        $user = User::factory()->create();
        $form = Form::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Builder actions test',
            'slug' => 'builder-actions-'.Str::lower(Str::random(8)),
            'status' => 'draft',
            'schema' => app(FormSchema::class)->blank(),
            'settings' => [],
        ]);

        return [$user, $form];
    }

    /** @return array{title: string, description: string, schema: array<string, mixed>} */
    private function builderPayload(string $title): array
    {
        return [
            'title' => $title,
            'description' => 'Updated from the form builder.',
            'schema' => app(FormSchema::class)->blank(),
        ];
    }
}
