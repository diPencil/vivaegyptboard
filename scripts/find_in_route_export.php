<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__ . '/../public/exports/lead_export_route.xlsx';
if (!file_exists($path)) { echo "NO_EXPORT\n"; exit(1); }

$reader = IOFactory::createReader('Xlsx');
$ss = $reader->load($path);
$sheet = $ss->getActiveSheet();

$found = false;
foreach ($sheet->getRowIterator(2) as $row) {
    $cells = [];
    foreach ($row->getCellIterator() as $cell) {
        $cells[] = $cell->getValue();
    }
    if (in_array('01110575930', $cells, true)) {
        echo "FOUND_ROW: " . implode(' | ', $cells) . "\n";
        $found = true;
    }
}
if (!$found) echo "NOT_FOUND\n";
