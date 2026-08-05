<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;

require dirname(__DIR__).'/vendor/autoload.php';

$sampleDirectory = dirname(__DIR__).'/docs/samples';
if (! is_dir($sampleDirectory) && ! mkdir($sampleDirectory, 0775, true) && ! is_dir($sampleDirectory)) {
    throw new RuntimeException('Unable to create the sample directory.');
}

$spreadsheet = new Spreadsheet;
$spreadsheet->getProperties()->setTitle('FormForge Excel import sample');
$spreadsheet->getActiveSheet()->setTitle('Form fields')->fromArray([
    ['section', 'label', 'type', 'required', 'options', 'placeholder', 'help_text', 'key'],
    ['Applicant details', 'Full name', 'text', 'yes', '', 'Jane Doe', 'Use your legal name.', 'full_name'],
    ['Applicant details', 'Email address', 'email', 'yes', '', 'jane@example.com', '', 'email_address'],
    ['Application', 'Preferred role', 'dropdown', 'yes', 'Engineering, Design, Product', '', '', 'preferred_role'],
    ['Application', 'Why are you interested?', 'textarea', 'yes', '', 'Tell us about your motivation', '', 'motivation'],
    ['Application', 'Portfolio URL', 'url', 'no', '', 'https://example.com', '', 'portfolio_url'],
]);
(new Xlsx($spreadsheet))->save($sampleDirectory.'/form-import-template.xlsx');

$document = new PhpWord;
$document->getDocInfo()->setTitle('FormForge Word import sample');
$document->addTitleStyle(1, ['bold' => true, 'size' => 16]);
$section = $document->addSection();
$section->addTitle('Contact details', 1);
$section->addText('Full name:');
$section->addText('Email address:');
$section->addText('Phone number:');
$section->addTitle('Event preferences', 1);
$section->addText('Which sessions would you attend?');
$section->addListItem('Engineering leadership');
$section->addListItem('Design systems');
$section->addListItem('Product strategy');
$section->addText('Dietary requirements:');
WordIOFactory::createWriter($document, 'Word2007')->save($sampleDirectory.'/event-registration-sample.docx');

echo "Import samples generated in {$sampleDirectory}".PHP_EOL;
