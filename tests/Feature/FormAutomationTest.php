<?php

namespace Tests\Feature;

use App\Jobs\GenerateFormWithAi;
use App\Jobs\ImportFormDocument;
use App\Models\Form;
use App\Models\FormAutomation;
use App\Models\User;
use App\Services\DocumentFormImporter;
use App\Services\FormSchema;
use App\Services\OpenAiFormGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class FormAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_owner_can_queue_ai_generation(): void
    {
        Queue::fake();
        [$user, $form] = $this->formOwnedByAUser();

        $response = $this->actingAs($user)->post(route('forms.ai.store', $form), [
            'mode' => 'generate',
            'prompt' => 'Create a detailed event registration form.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('form_automations', [
            'form_id' => $form->id,
            'user_id' => $user->id,
            'type' => 'ai_generate',
            'status' => 'queued',
        ]);
        Queue::assertPushed(GenerateFormWithAi::class, 1);
    }

    public function test_an_authenticated_owner_can_queue_a_private_document_import(): void
    {
        Queue::fake();
        [$user, $form] = $this->formOwnedByAUser();
        $file = UploadedFile::fake()->create(
            'requirements.xlsx',
            32,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $response = $this->actingAs($user)->post(route('forms.imports.store', $form), [
            'document' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('form_automations', [
            'form_id' => $form->id,
            'type' => 'import',
            'status' => 'queued',
        ]);
        Queue::assertPushed(ImportFormDocument::class, 1);
    }

    public function test_users_cannot_start_automation_for_another_users_form(): void
    {
        Queue::fake();
        [, $form] = $this->formOwnedByAUser();

        $this->actingAs(User::factory()->create())->post(route('forms.ai.store', $form), [
            'mode' => 'generate',
            'prompt' => 'Create a detailed event registration form.',
        ])->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_reviewed_import_mapping_is_validated_before_it_is_applied(): void
    {
        [$user, $form] = $this->formOwnedByAUser();
        $schema = app(FormSchema::class)->blank();
        $schema['sections'][0]['fields'][0]['type'] = 'textarea';
        $automation = FormAutomation::query()->create([
            'uuid' => (string) Str::uuid(),
            'form_id' => $form->id,
            'user_id' => $user->id,
            'type' => 'import',
            'status' => 'completed',
            'input' => ['original_name' => 'mapping.xlsx'],
            'result_schema' => app(FormSchema::class)->blank(),
            'completed_at' => now(),
        ]);

        $this->actingAs($user)->post(route('form-automations.apply', $automation), [
            'schema' => $schema,
        ])->assertRedirect();

        $this->assertSame('textarea', $form->refresh()->schema['sections'][0]['fields'][0]['type']);
        $this->assertNotNull($automation->refresh()->applied_at);
    }

    public function test_openai_generator_uses_structured_outputs_and_validates_the_schema(): void
    {
        config()->set('services.openai.key', 'test-key');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');
        config()->set('services.openai.model', 'gpt-test');
        $schema = app(FormSchema::class)->blank();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'model' => 'gpt-test-2026-08-01',
                'usage' => ['input_tokens' => 120, 'output_tokens' => 80],
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode($schema, JSON_THROW_ON_ERROR),
                    ]],
                ]],
            ]),
        ]);

        $result = app(OpenAiFormGenerator::class)->generate(
            'Create a useful customer intake form.',
            null,
            'safe-user-hash',
        );

        $this->assertSame('gpt-test-2026-08-01', $result['model']);
        $this->assertSame(120, $result['input_tokens']);
        $this->assertCount(2, $result['schema']['sections'][0]['fields']);
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['store'] === false
            && $request['safety_identifier'] === 'safe-user-hash'
            && $request['text']['format']['type'] === 'json_schema'
            && $request['text']['format']['strict'] === true
        );
    }

    public function test_excel_template_is_converted_to_a_valid_schema(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['section', 'label', 'type', 'required', 'options', 'placeholder'],
            ['Contact', 'Full name', 'text', 'yes', '', 'Jane Doe'],
            ['Contact', 'Email address', 'email', 'yes', '', 'jane@example.com'],
            ['Preferences', 'Preferred plan', 'select', 'no', 'Starter, Growth, Enterprise', ''],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'formforge-xlsx-');
        $this->assertNotFalse($path);
        (new Xlsx($spreadsheet))->save($path);

        try {
            $schema = app(DocumentFormImporter::class)->import($path, 'xlsx');
        } finally {
            unlink($path);
        }

        $this->assertCount(2, $schema['sections']);
        $this->assertSame('email', $schema['sections'][0]['fields'][1]['type']);
        $this->assertSame('dropdown', $schema['sections'][1]['fields'][0]['type']);
        $this->assertCount(3, $schema['sections'][1]['fields'][0]['options']);
    }

    public function test_word_headings_and_prompts_are_converted_to_a_valid_schema(): void
    {
        $document = new PhpWord;
        $document->addTitleStyle(1, ['bold' => true, 'size' => 16]);
        $section = $document->addSection();
        $section->addTitle('Contact details', 1);
        $section->addText('Full name:');
        $section->addText('Email address:');
        $section->addTitle('Preferences', 1);
        $section->addText('Preferred plan: Starter, Growth, Enterprise');
        $section->addText('Preferred contact channels?');
        $section->addListItem('Email');
        $section->addListItem('Phone');
        $path = tempnam(sys_get_temp_dir(), 'formforge-docx-');
        $this->assertNotFalse($path);
        WordIOFactory::createWriter($document, 'Word2007')->save($path);

        try {
            $schema = app(DocumentFormImporter::class)->import($path, 'docx');
        } finally {
            unlink($path);
        }

        $this->assertCount(2, $schema['sections']);
        $this->assertSame('Contact details', $schema['sections'][0]['title']);
        $this->assertSame('email', $schema['sections'][0]['fields'][1]['type']);
        $this->assertSame('radio', $schema['sections'][1]['fields'][0]['type']);
        $this->assertSame('radio', $schema['sections'][1]['fields'][1]['type']);
        $this->assertCount(2, $schema['sections'][1]['fields'][1]['options']);
    }

    public function test_committed_import_samples_remain_parseable(): void
    {
        $importer = app(DocumentFormImporter::class);
        $excel = $importer->import(base_path('docs/samples/form-import-template.xlsx'), 'xlsx');
        $word = $importer->import(base_path('docs/samples/event-registration-sample.docx'), 'docx');

        $this->assertCount(2, $excel['sections']);
        $this->assertSame('dropdown', $excel['sections'][1]['fields'][0]['type']);
        $this->assertCount(2, $word['sections']);
        $this->assertCount(3, $word['sections'][1]['fields'][0]['options']);
    }

    /** @return array{User, Form} */
    private function formOwnedByAUser(): array
    {
        $user = User::factory()->create();
        $form = Form::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Automation test',
            'slug' => 'automation-test-'.Str::lower(Str::random(6)),
            'status' => 'draft',
            'schema' => app(FormSchema::class)->blank(),
            'settings' => [],
        ]);

        return [$user, $form];
    }
}
