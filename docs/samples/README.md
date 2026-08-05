# Import samples

These are the documents used to exercise the deterministic import layer.

- `form-import-template.xlsx` demonstrates the documented mapping layout. The `label` column is required; supported optional columns are `section`, `type`, `required`, `options`, `placeholder`, `help_text`, and `key`. Options are comma-, semicolon-, or pipe-separated.
- `event-registration-sample.docx` demonstrates Heading 1 sections, question paragraphs, and list items that become choice options.

An XLSX file without the mapping columns is also accepted: its first populated row is treated as a list of field labels. Run `php scripts/generate-import-samples.php` to regenerate both binary fixtures.
