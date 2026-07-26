<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__ . '/../public/exports/leads_test.xlsx';
if (!file_exists($path)) {
    echo "MISSING_FILE\n";
    exit(1);
}

$reader = IOFactory::createReader('Xlsx');
$spreadsheet = $reader->load($path);
$sheet = $spreadsheet->getActiveSheet();

$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();

echo "HighestRow: $highestRow\n";
echo "HighestColumn: $highestColumn\n";

// Print headers (row 1)
$headers = [];
foreach ($sheet->getRowIterator(1,1)->current()->getCellIterator() as $cell) {
    $headers[] = $cell->getValue();
}
echo "HEADERS: " . implode(' | ', $headers) . "\n";

// Print first data row (row 2)
$data = [];
foreach ($sheet->getRowIterator(2,2)->current()->getCellIterator() as $cell) {
    $data[] = $cell->getValue();
}
echo "ROW2: " . implode(' | ', $data) . "\n";
