<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Services\FormSchema;
use App\Services\FormVersionManager;
use App\Services\PublishedFormResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class FormDifferentiatorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_change_is_versioned_and_an_old_version_can_be_restored(): void
    {
        [$user, $form] = $this->formOwnedByAUser();
        $versions = app(FormVersionManager::class);
        $versions->recordInitial($form, $user->id);
        $versions->update($form, ['title' => 'Updated form'], $user->id, 'manual', 'Changed title');

        $original = $form->versions()->where('version', 1)->firstOrFail();
        $versions->restore($form, $original, $user->id);

        $form->refresh();
        $this->assertSame(3, $form->version);
        $this->assertSame('Differentiator test', $form->title);
        $this->assertSame('rollback', $form->versions()->latest('version')->firstOrFail()->source);
        $this->assertCount(3, $form->versions);
    }

    public function test_conditional_required_fields_are_only_validated_when_visible(): void
    {
        $schemas = app(FormSchema::class);
        $schema = $schemas->blank();
        $source = $schema['sections'][0]['fields'][0];
        $source['type'] = 'radio';
        $source['label'] = 'Need a reply?';
        $source['options'] = [
            ['label' => 'Yes', 'value' => 'yes'],
            ['label' => 'No', 'value' => 'no'],
        ];
        $target = $schema['sections'][0]['fields'][1];
        $target['required'] = true;
        $target['condition'] = [
            'field' => $source['key'],
            'operator' => 'equals',
            'value' => 'yes',
        ];
        $schema['sections'][0]['fields'] = [$source, $target];
        $schema = $schemas->validateAndNormalize($schema);

        $hiddenRules = $schemas->rules($schema, [$source['key'] => 'no']);
        $visibleRules = $schemas->rules($schema, [$source['key'] => 'yes']);

        $this->assertArrayNotHasKey('answers.'.$target['key'], $hiddenRules);
        $this->assertArrayHasKey('answers.'.$target['key'], $visibleRules);
        $this->assertFalse(Validator::make(
            ['answers' => [$source['key'] => 'yes']],
            $visibleRules,
        )->passes());
    }

    public function test_submission_tokens_make_public_posts_idempotent(): void
    {
        [, $form] = $this->formOwnedByAUser('published');
        $token = (string) Str::uuid();
        $payload = [
            'submission_token' => $token,
            'started_at' => now()->subSeconds(2)->toIso8601String(),
            'website' => '',
            'answers' => [],
        ];

        $this->post(route('public.forms.submit', $form->slug), $payload)->assertRedirect();
        $this->post(route('public.forms.submit', $form->slug), $payload)->assertRedirect();

        $this->assertDatabaseCount('form_submissions', 1);
        $this->assertDatabaseHas('form_submissions', ['submission_token' => $token]);
    }

    public function test_public_form_cache_is_invalidated_when_a_form_changes(): void
    {
        [, $form] = $this->formOwnedByAUser('published');
        $resolver = app(PublishedFormResolver::class);

        $this->assertSame('Differentiator test', $resolver->payload($form->slug)['title']);
        $form->update(['title' => 'Cache invalidated']);

        $this->assertSame('Cache invalidated', $resolver->payload($form->slug)['title']);
    }

    /** @return array{User, Form} */
    private function formOwnedByAUser(string $status = 'draft'): array
    {
        $user = User::factory()->create();
        $form = Form::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Differentiator test',
            'slug' => 'differentiator-'.Str::lower(Str::random(8)),
            'status' => $status,
            'schema' => app(FormSchema::class)->blank(),
            'settings' => [],
            'published_at' => $status === 'published' ? now() : null,
        ]);

        return [$user, $form];
    }
}
