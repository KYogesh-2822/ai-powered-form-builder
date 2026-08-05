<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-type SchemaArray array<string, mixed>
 * @phpstan-type FieldArray array<string, mixed>
 */
class FormSchema
{
    public const FIELD_TYPES = [
        'text',
        'textarea',
        'number',
        'email',
        'phone',
        'date',
        'dropdown',
        'radio',
        'checkbox',
        'file',
        'heading',
        'rating',
        'url',
    ];

    /** @return SchemaArray */
    public function blank(): array
    {
        return [
            'version' => 1,
            'settings' => [
                'submitLabel' => 'Submit response',
                'successMessage' => 'Thank you. Your response has been recorded.',
                'showProgress' => true,
            ],
            'sections' => [
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'Your first section',
                    'description' => 'Add fields from the palette to start building.',
                    'fields' => [
                        $this->newField('text', 'Full name'),
                        $this->newField('email', 'Email address'),
                    ],
                ],
            ],
        ];
    }

    /** @return FieldArray */
    public function newField(string $type, ?string $label = null): array
    {
        $type = in_array($type, self::FIELD_TYPES, true) ? $type : 'text';
        $label ??= Str::headline($type);
        $key = trim((string) preg_replace('/[^a-z0-9]+/', '_', Str::lower(Str::ascii($label))), '_');
        if ($key === '' || preg_match('/^[a-z]/', $key) !== 1) {
            $key = 'field_'.$key;
        }

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'label' => $label,
            'key' => Str::limit($key, 55, '').'_'.Str::lower(Str::random(4)),
            'placeholder' => '',
            'helpText' => '',
            'defaultValue' => $type === 'checkbox' ? false : '',
            'required' => false,
            'options' => in_array($type, ['dropdown', 'radio'], true)
                ? [['label' => 'Option 1', 'value' => 'option_1']]
                : [],
            'validation' => [
                'min' => null,
                'max' => null,
                'minLength' => null,
                'maxLength' => null,
                'regex' => '',
                'fileTypes' => [],
                'maxFileSize' => 5120,
            ],
            'condition' => null,
        ];
    }

    /**
     * @param  SchemaArray  $schema
     * @return SchemaArray
     */
    public function validateAndNormalize(array $schema): array
    {
        if (! isset($schema['sections']) || ! is_array($schema['sections'])) {
            throw ValidationException::withMessages([
                'schema' => 'The schema must contain a sections array.',
            ]);
        }

        if (count($schema['sections']) === 0) {
            throw ValidationException::withMessages([
                'schema' => 'A form must contain at least one section.',
            ]);
        }

        $normalized = [
            'version' => 1,
            'settings' => [
                'submitLabel' => Str::limit((string) Arr::get($schema, 'settings.submitLabel', 'Submit response'), 60, ''),
                'successMessage' => Str::limit((string) Arr::get($schema, 'settings.successMessage', 'Thank you. Your response has been recorded.'), 500, ''),
                'showProgress' => (bool) Arr::get($schema, 'settings.showProgress', true),
            ],
            'sections' => [],
        ];

        $keys = [];
        $fieldCount = 0;

        foreach ($schema['sections'] as $sectionIndex => $section) {
            if (! is_array($section)) {
                $this->invalid("Section {$sectionIndex} must be an object.");
            }

            $normalizedSection = [
                'id' => $this->identifier($section['id'] ?? null),
                'title' => Str::limit(trim((string) ($section['title'] ?? 'Untitled section')), 160, ''),
                'description' => Str::limit(trim((string) ($section['description'] ?? '')), 1000, ''),
                'fields' => [],
            ];

            $fields = $section['fields'] ?? [];
            if (! is_array($fields)) {
                $this->invalid("Fields for section {$sectionIndex} must be an array.");
            }

            foreach ($fields as $fieldIndex => $field) {
                if (! is_array($field)) {
                    $this->invalid("Field {$fieldIndex} in section {$sectionIndex} must be an object.");
                }

                $type = (string) ($field['type'] ?? '');
                if (! in_array($type, self::FIELD_TYPES, true)) {
                    $this->invalid("Unsupported field type [{$type}].");
                }

                $label = trim((string) ($field['label'] ?? ''));
                if ($label === '') {
                    $this->invalid("Field {$fieldIndex} requires a label.");
                }

                $key = trim((string) ($field['key'] ?? ''));
                if ($type !== 'heading') {
                    if (! preg_match('/^[a-z][a-z0-9_]{1,63}$/', $key)) {
                        $this->invalid("Field key [{$key}] must use lowercase letters, numbers, and underscores.");
                    }
                    if (isset($keys[$key])) {
                        $this->invalid("Field key [{$key}] is duplicated.");
                    }
                    $keys[$key] = true;
                }

                $options = [];
                foreach (($field['options'] ?? []) as $option) {
                    if (! is_array($option)) {
                        continue;
                    }
                    $optionLabel = trim((string) ($option['label'] ?? ''));
                    $optionValue = trim((string) ($option['value'] ?? ''));
                    if ($optionLabel !== '' && $optionValue !== '') {
                        $options[] = [
                            'label' => Str::limit($optionLabel, 120, ''),
                            'value' => Str::limit($optionValue, 120, ''),
                        ];
                    }
                }

                if (in_array($type, ['dropdown', 'radio'], true) && $options === []) {
                    $this->invalid("Choice field [{$label}] requires at least one option.");
                }

                $validation = is_array($field['validation'] ?? null) ? $field['validation'] : [];
                $normalizedSection['fields'][] = [
                    'id' => $this->identifier($field['id'] ?? null),
                    'type' => $type,
                    'label' => Str::limit($label, 160, ''),
                    'key' => $type === 'heading' ? '' : $key,
                    'placeholder' => Str::limit((string) ($field['placeholder'] ?? ''), 255, ''),
                    'helpText' => Str::limit((string) ($field['helpText'] ?? ''), 500, ''),
                    'defaultValue' => $field['defaultValue'] ?? ($type === 'checkbox' ? false : ''),
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => $options,
                    'validation' => [
                        'min' => $this->nullableNumber($validation['min'] ?? null),
                        'max' => $this->nullableNumber($validation['max'] ?? null),
                        'minLength' => $this->nullableInteger($validation['minLength'] ?? null),
                        'maxLength' => $this->nullableInteger($validation['maxLength'] ?? null),
                        'regex' => Str::limit((string) ($validation['regex'] ?? ''), 300, ''),
                        'fileTypes' => array_values(array_filter(array_map('strval', $validation['fileTypes'] ?? []))),
                        'maxFileSize' => max(1, min(20480, (int) ($validation['maxFileSize'] ?? 5120))),
                    ],
                    'condition' => is_array($field['condition'] ?? null) ? $field['condition'] : null,
                ];
                $fieldCount++;
            }

            $normalized['sections'][] = $normalizedSection;
        }

        if ($fieldCount === 0) {
            $this->invalid('A form must contain at least one field.');
        }

        if ($fieldCount > 200) {
            $this->invalid('A form may contain at most 200 fields.');
        }

        return $normalized;
    }

    /**
     * @param  SchemaArray  $schema
     * @return array<string, list<mixed>>
     */
    public function rules(array $schema): array
    {
        $rules = [];

        foreach ($this->fields($schema) as $field) {
            if ($field['type'] === 'heading') {
                continue;
            }

            $fieldRules = [$field['required'] ? 'required' : 'nullable'];
            $validation = $field['validation'] ?? [];

            $fieldRules[] = match ($field['type']) {
                'email' => 'email:rfc',
                'url' => 'url:http,https',
                'number', 'rating' => 'numeric',
                'date' => 'date_format:Y-m-d',
                'checkbox' => 'boolean',
                'file' => 'file',
                default => 'string',
            };

            if (in_array($field['type'], ['dropdown', 'radio'], true)) {
                $fieldRules[] = Rule::in(array_column($field['options'] ?? [], 'value'));
            }

            if ($validation['min'] !== null && in_array($field['type'], ['number', 'rating'], true)) {
                $fieldRules[] = 'min:'.$validation['min'];
            }
            if ($validation['max'] !== null && in_array($field['type'], ['number', 'rating'], true)) {
                $fieldRules[] = 'max:'.$validation['max'];
            }
            if ($validation['minLength'] !== null && $field['type'] !== 'file') {
                $fieldRules[] = 'min:'.$validation['minLength'];
            }
            if ($validation['maxLength'] !== null && $field['type'] !== 'file') {
                $fieldRules[] = 'max:'.$validation['maxLength'];
            }
            if ($validation['regex'] !== '') {
                $fieldRules[] = 'regex:'.$validation['regex'];
            }
            if ($field['type'] === 'file') {
                $fieldRules[] = 'max:'.$validation['maxFileSize'];
                if ($validation['fileTypes'] !== []) {
                    $fieldRules[] = 'mimes:'.implode(',', $validation['fileTypes']);
                }
            }

            $rules['answers.'.$field['key']] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  SchemaArray  $schema
     * @return list<FieldArray>
     */
    public function fields(array $schema): array
    {
        $fields = [];

        foreach ($schema['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }
            foreach ($section['fields'] ?? [] as $field) {
                if (is_array($field)) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    private function identifier(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? Str::limit($value, 80, '') : (string) Str::uuid();
    }

    private function nullableNumber(mixed $value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? max(0, (int) $value) : null;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['schema' => $message]);
    }
}
