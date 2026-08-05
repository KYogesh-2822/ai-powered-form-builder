<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OpenAiFormGenerator
{
    public function __construct(private readonly FormSchema $schemas) {}

    /**
     * @param  array<string, mixed>|null  $existingSchema
     * @return array{schema: array<string, mixed>, model: string, input_tokens: int, output_tokens: int, latency_ms: int}
     */
    public function generate(string $instruction, ?array $existingSchema, string $safetyIdentifier): array
    {
        $apiKey = trim((string) config('services.openai.key'));
        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = (string) config('services.openai.model', 'gpt-5.6-luna');
        $lastError = 'The model did not return a valid schema.';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $started = microtime(true);
            $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
                ->withToken($apiKey)
                ->acceptJson()
                ->timeout(90)
                ->retry(2, 500, throw: false)
                ->post('/responses', [
                    'model' => $model,
                    'store' => false,
                    'safety_identifier' => $safetyIdentifier,
                    'reasoning' => ['effort' => 'low'],
                    'max_output_tokens' => 12000,
                    'input' => [
                        [
                            'role' => 'developer',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->userPrompt($instruction, $existingSchema, $lastError, $attempt),
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'form_schema',
                            'strict' => true,
                            'schema' => $this->responseSchema(),
                        ],
                    ],
                ]);

            $latency = (int) round((microtime(true) - $started) * 1000);
            if (! $response->successful()) {
                $lastError = $this->apiError($response);

                continue;
            }

            try {
                $payload = $response->json();
                if (! is_array($payload)) {
                    throw new RuntimeException('OpenAI returned an invalid response body.');
                }

                $decoded = json_decode($this->outputText($payload), true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decoded)) {
                    throw new RuntimeException('OpenAI returned a non-object schema.');
                }

                $schema = $this->schemas->validateAndNormalize($decoded);

                return [
                    'schema' => $schema,
                    'model' => (string) ($payload['model'] ?? $model),
                    'input_tokens' => (int) data_get($payload, 'usage.input_tokens', 0),
                    'output_tokens' => (int) data_get($payload, 'usage.output_tokens', 0),
                    'latency_ms' => $latency,
                ];
            } catch (ValidationException $exception) {
                $lastError = collect($exception->errors())->flatten()->implode(' ');
            } catch (Throwable $exception) {
                $lastError = $exception->getMessage();
            }
        }

        throw new RuntimeException('AI schema generation failed after three attempts: '.$lastError);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are the schema engine for a production form builder. Return only the requested schema.

Rules:
- Use only: text, textarea, number, email, phone, date, dropdown, radio, checkbox, file, heading, rating, url.
- Use lowercase snake_case keys, unique across the entire form. Heading keys must be empty.
- Make required fields intentional. Add useful placeholders, help text, options, and conservative validation.
- Use sections to group related questions. Keep labels concise and user-facing.
- Never include secrets, HTML, executable code, or unsupported field types.
- For edits, preserve unaffected fields and IDs while applying the instruction.
- condition is null unless conditional display is clearly useful.
PROMPT;
    }

    /** @param array<string, mixed>|null $existingSchema */
    private function userPrompt(string $instruction, ?array $existingSchema, string $lastError, int $attempt): string
    {
        $mode = $existingSchema === null ? 'Create a new form.' : 'Edit the existing form.';
        $context = $existingSchema === null
            ? ''
            : "\nExisting schema:\n".json_encode($existingSchema, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $repair = $attempt === 1 ? '' : "\nRepair note from the previous attempt: {$lastError}";

        return "{$mode}\nInstruction: {$instruction}{$context}{$repair}";
    }

    /** @param array<string, mixed> $payload */
    private function outputText(array $payload): string
    {
        foreach ($payload['output'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach ($item['content'] ?? [] as $content) {
                if (is_array($content) && ($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        throw new RuntimeException('OpenAI response contained no output text.');
    }

    private function apiError(Response $response): string
    {
        $message = $response->json('error.message');

        return is_string($message)
            ? "OpenAI request failed ({$response->status()}): {$message}"
            : "OpenAI request failed with HTTP {$response->status()}.";
    }

    /** @return array<string, mixed> */
    private function responseSchema(): array
    {
        $nullableNumber = ['anyOf' => [['type' => 'number'], ['type' => 'null']]];
        $nullableInteger = ['anyOf' => [['type' => 'integer'], ['type' => 'null']]];
        $condition = [
            'anyOf' => [
                ['type' => 'null'],
                [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'field' => ['type' => 'string'],
                        'operator' => ['type' => 'string', 'enum' => ['equals', 'not_equals', 'contains']],
                        'value' => ['type' => 'string'],
                    ],
                    'required' => ['field', 'operator', 'value'],
                ],
            ],
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'version' => ['type' => 'integer', 'enum' => [1]],
                'settings' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'submitLabel' => ['type' => 'string'],
                        'successMessage' => ['type' => 'string'],
                        'showProgress' => ['type' => 'boolean'],
                    ],
                    'required' => ['submitLabel', 'successMessage', 'showProgress'],
                ],
                'sections' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'fields' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => [
                                        'id' => ['type' => 'string'],
                                        'type' => ['type' => 'string', 'enum' => FormSchema::FIELD_TYPES],
                                        'label' => ['type' => 'string'],
                                        'key' => ['type' => 'string'],
                                        'placeholder' => ['type' => 'string'],
                                        'helpText' => ['type' => 'string'],
                                        'defaultValue' => [
                                            'anyOf' => [
                                                ['type' => 'string'],
                                                ['type' => 'number'],
                                                ['type' => 'boolean'],
                                                ['type' => 'null'],
                                            ],
                                        ],
                                        'required' => ['type' => 'boolean'],
                                        'options' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'additionalProperties' => false,
                                                'properties' => [
                                                    'label' => ['type' => 'string'],
                                                    'value' => ['type' => 'string'],
                                                ],
                                                'required' => ['label', 'value'],
                                            ],
                                        ],
                                        'validation' => [
                                            'type' => 'object',
                                            'additionalProperties' => false,
                                            'properties' => [
                                                'min' => $nullableNumber,
                                                'max' => $nullableNumber,
                                                'minLength' => $nullableInteger,
                                                'maxLength' => $nullableInteger,
                                                'regex' => ['type' => 'string'],
                                                'fileTypes' => ['type' => 'array', 'items' => ['type' => 'string']],
                                                'maxFileSize' => ['type' => 'integer'],
                                            ],
                                            'required' => ['min', 'max', 'minLength', 'maxLength', 'regex', 'fileTypes', 'maxFileSize'],
                                        ],
                                        'condition' => $condition,
                                    ],
                                    'required' => ['id', 'type', 'label', 'key', 'placeholder', 'helpText', 'defaultValue', 'required', 'options', 'validation', 'condition'],
                                ],
                            ],
                        ],
                        'required' => ['id', 'title', 'description', 'fields'],
                    ],
                ],
            ],
            'required' => ['version', 'settings', 'sections'],
        ];
    }
}
