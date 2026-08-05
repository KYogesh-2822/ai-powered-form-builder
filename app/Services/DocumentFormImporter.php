<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Throwable;

/**
 * Converts deliberately simple Word and Excel documents into the canonical form schema.
 *
 * @phpstan-type FieldArray array<string, mixed>
 * @phpstan-type SectionArray array{id: string, title: string, description: string, fields: list<FieldArray>}
 */
class DocumentFormImporter
{
    public function __construct(private readonly FormSchema $schemas) {}

    /** @return array<string, mixed> */
    public function import(string $absolutePath, string $extension): array
    {
        if (! is_file($absolutePath)) {
            $this->invalid('The uploaded document is no longer available.');
        }

        $schema = match (Str::lower($extension)) {
            'xlsx' => $this->fromSpreadsheet($absolutePath),
            'docx' => $this->fromWordDocument($absolutePath),
            default => $this->invalid('Only DOCX and XLSX documents are supported.'),
        };

        return $this->schemas->validateAndNormalize($schema);
    }

    /** @return array<string, mixed> */
    private function fromSpreadsheet(string $path): array
    {
        try {
            $rawRows = SpreadsheetIOFactory::load($path)->getActiveSheet()->toArray('', true, true, false);
        } catch (Throwable $exception) {
            $this->invalid('The Excel workbook could not be read: '.$exception->getMessage());
        }

        $rows = array_map(fn (array $row): array => array_values($row), $rawRows);
        $rows = array_values(array_filter($rows, fn (array $row): bool => $this->firstValue($row) !== ''));
        if ($rows === []) {
            $this->invalid('The Excel workbook does not contain any fields.');
        }

        $headers = array_map(fn (mixed $value): string => Str::snake(trim((string) $value)), $rows[0]);
        $hasHeader = in_array('label', $headers, true);
        if (! $hasHeader) {
            return $this->fromPlainHeaderRow($rows[0]);
        }

        $dataRows = array_slice($rows, 1);
        $sections = [];

        foreach ($dataRows as $rowIndex => $row) {
            $record = $this->combineRow($headers, $row);
            $label = trim((string) ($record['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $sectionTitle = trim((string) ($record['section'] ?? '')) ?: 'Imported fields';
            $sectionKey = Str::lower($sectionTitle);
            if (! isset($sections[$sectionKey])) {
                $sections[$sectionKey] = $this->section($sectionTitle);
            }

            $options = $this->options((string) ($record['options'] ?? ''));
            $type = $this->normalizeType((string) ($record['type'] ?? ''), $label, $options);
            $field = $this->schemas->newField($type, $label);
            $field['required'] = $this->truthy($record['required'] ?? false);
            $field['placeholder'] = trim((string) ($record['placeholder'] ?? ''));
            $field['helpText'] = trim((string) ($record['help_text'] ?? $record['help'] ?? ''));

            $requestedKey = Str::snake(Str::ascii(trim((string) ($record['key'] ?? ''))));
            if ($requestedKey !== '' && preg_match('/^[a-z][a-z0-9_]{1,63}$/', $requestedKey)) {
                $field['key'] = $requestedKey.'_'.($rowIndex + 1);
            }
            if (in_array($type, ['dropdown', 'radio'], true) && $options !== []) {
                $field['options'] = $options;
            }

            $sections[$sectionKey]['fields'][] = $field;
        }

        return $this->schema(array_values($sections));
    }

    /** @return array<string, mixed> */
    private function fromWordDocument(string $path): array
    {
        try {
            $document = WordIOFactory::load($path);
        } catch (Throwable $exception) {
            $this->invalid('The Word document could not be read: '.$exception->getMessage());
        }

        $lines = [];
        foreach ($document->getSections() as $section) {
            $this->collectWordElements($section->getElements(), $lines);
        }

        $sections = [];
        $current = $this->section('Imported fields');
        foreach ($lines as $line) {
            $text = trim($line['text']);
            if ($text === '') {
                continue;
            }

            if ($line['kind'] === 'heading') {
                if ($current['fields'] !== []) {
                    $sections[] = $current;
                }
                $current = $this->section(Str::limit($text, 160, ''));

                continue;
            }

            if ($line['kind'] === 'option' && $current['fields'] !== []) {
                $fieldIndex = count($current['fields']) - 1;
                $optionLabel = trim((string) preg_replace('/^[0-9]+[.)]\s*|^[-*]\s*/', '', $text));
                if ($optionLabel !== '') {
                    $current['fields'][$fieldIndex]['type'] = 'radio';
                    $current['fields'][$fieldIndex]['options'][] = [
                        'label' => Str::limit($optionLabel, 120, ''),
                        'value' => Str::limit(Str::snake(Str::ascii($optionLabel)), 120, ''),
                    ];
                }

                continue;
            }

            $text = trim((string) preg_replace('/^[0-9]+[.)]\s*|^[-*]\s*/', '', $text));
            $options = [];
            $label = rtrim($text, " \t\n\r\0\x0B:");
            if (preg_match('/^(.+?)\s*[:\-]\s*([^,;|]+(?:\s*[,;|]\s*[^,;|]+)+)$/', $text, $matches) === 1) {
                $label = trim($matches[1]);
                $options = $this->options($matches[2]);
            }

            if ($label === '' || mb_strlen($label) > 160) {
                continue;
            }

            $type = $this->normalizeType('', $label, $options);
            $field = $this->schemas->newField($type, $label);
            if ($options !== []) {
                $field['type'] = 'radio';
                $field['options'] = $options;
            }
            $current['fields'][] = $field;
        }

        if ($current['fields'] !== []) {
            $sections[] = $current;
        }

        return $this->schema($sections);
    }

    /**
     * @param  list<array{kind: string, text: string}>  $lines
     */
    private function collectWordElements(mixed $elements, array &$lines): void
    {
        if (! is_iterable($elements)) {
            return;
        }

        foreach ($elements as $element) {
            if (! is_object($element)) {
                continue;
            }

            $class = Str::lower(class_basename($element));
            if (str_contains($class, 'title')) {
                $text = $this->wordText($element);
                if ($text !== '') {
                    $lines[] = ['kind' => 'heading', 'text' => $text];
                }

                continue;
            }

            if (str_contains($class, 'listitem') || str_contains($class, 'checkbox')) {
                $text = $this->wordText($element);
                if ($text !== '') {
                    $lines[] = ['kind' => 'option', 'text' => $text];
                }

                continue;
            }

            if (method_exists($element, 'getRows')) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $this->collectWordElements($cell->getElements(), $lines);
                    }
                }

                continue;
            }

            if (method_exists($element, 'getElements')) {
                $text = $this->wordText($element);
                if ($text !== '') {
                    $lines[] = ['kind' => 'text', 'text' => $text];
                } else {
                    $this->collectWordElements($element->getElements(), $lines);
                }

                continue;
            }

            $text = $this->wordText($element);
            if ($text !== '') {
                $lines[] = ['kind' => 'text', 'text' => $text];
            }
        }
    }

    private function wordText(object $element): string
    {
        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text)) {
                return trim($text);
            }
            if (is_object($text)) {
                return $this->wordText($text);
            }
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                if (is_object($child)) {
                    $parts[] = $this->wordText($child);
                }
            }

            return trim(implode('', $parts));
        }

        return '';
    }

    /**
     * @param  list<string>  $headers
     * @param  list<mixed>  $row
     * @return array<string, mixed>
     */
    private function combineRow(array $headers, array $row): array
    {
        $record = [];
        foreach ($headers as $index => $header) {
            if ($header !== '') {
                $record[$header] = $row[$index] ?? '';
            }
        }

        return $record;
    }

    /**
     * @param  list<mixed>  $headers
     * @return array<string, mixed>
     */
    private function fromPlainHeaderRow(array $headers): array
    {
        $section = $this->section('Imported spreadsheet columns');
        foreach ($headers as $header) {
            $label = trim((string) $header);
            if ($label !== '') {
                $section['fields'][] = $this->schemas->newField(
                    $this->normalizeType('', $label, []),
                    $label,
                );
            }
        }

        return $this->schema([$section]);
    }

    /** @param list<mixed> $row */
    private function firstValue(array $row): string
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    /** @return list<array{label: string, value: string}> */
    private function options(string $value): array
    {
        return array_values(collect(preg_split('/\s*[,;|]\s*/', trim($value)) ?: [])
            ->filter()
            ->unique()
            ->map(fn (string $option): array => [
                'label' => Str::limit(trim($option), 120, ''),
                'value' => Str::limit(Str::snake(Str::ascii($option)), 120, ''),
            ])
            ->values()
            ->all());
    }

    /** @param  list<array{label: string, value: string}>  $options */
    private function normalizeType(string $requested, string $label, array $options): string
    {
        $requested = Str::lower(trim($requested));
        $aliases = [
            'select' => 'dropdown', 'choice' => 'radio', 'yes/no' => 'radio',
            'long text' => 'textarea', 'integer' => 'number', 'telephone' => 'phone',
            'website' => 'url', 'upload' => 'file',
        ];
        $requested = $aliases[$requested] ?? $requested;
        if (in_array($requested, FormSchema::FIELD_TYPES, true)) {
            return $requested;
        }
        if ($options !== []) {
            return 'radio';
        }

        $label = Str::lower($label);

        return match (true) {
            str_contains($label, 'email') => 'email',
            str_contains($label, 'phone'), str_contains($label, 'mobile') => 'phone',
            str_contains($label, 'date') => 'date',
            str_contains($label, 'website'), str_contains($label, 'url') => 'url',
            str_contains($label, 'rating'), str_contains($label, 'score') => 'rating',
            str_contains($label, 'number'), str_contains($label, 'quantity') => 'number',
            str_contains($label, 'describe'), str_contains($label, 'comments') => 'textarea',
            default => 'text',
        };
    }

    private function truthy(mixed $value): bool
    {
        return in_array(Str::lower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'required'], true);
    }

    /** @return SectionArray */
    private function section(string $title): array
    {
        return ['id' => (string) Str::uuid(), 'title' => $title, 'description' => '', 'fields' => []];
    }

    /**
     * @param  list<SectionArray>  $sections
     * @return array<string, mixed>
     */
    private function schema(array $sections): array
    {
        if ($sections === []) {
            $this->invalid('No form fields could be detected in the document.');
        }

        return [
            'version' => 1,
            'settings' => [
                'submitLabel' => 'Submit response',
                'successMessage' => 'Thank you. Your response has been recorded.',
                'showProgress' => true,
            ],
            'sections' => $sections,
        ];
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['document' => $message]);
    }
}
