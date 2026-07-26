<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$headers = ['client_name','client_email','mobile','lead_category','lead_requirements','company_name'];
$sheet->fromArray($headers, null, 'A1');

// Example row
$row = ['Sample Lead','sample@example.com','01110575930','Umrah','Customer requests an Umrah package for two adults.','Sample Co'];
$sheet->fromArray($row, null, 'A2');

$writer = new Xlsx($spreadsheet);
$outPath = __DIR__ . '/../public/sample-import/lead-contact-sample.xlsx';
$writer->save($outPath);

echo "WROTE: $outPath\n";
