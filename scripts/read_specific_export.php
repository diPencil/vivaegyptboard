<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__ . '/../public/exports/lead_export_route.xlsx';
if (!file_exists($path)) { echo "MISSING\n"; exit(1); }

$reader = IOFactory::createReader('Xlsx');
$ss = $reader->load($path);
$s = $ss->getActiveSheet();

echo "A1=" . $s->getCell('A1')->getValue() . "\n";
echo "C2=" . $s->getCell('C2')->getValue() . "\n";
echo "D2=" . $s->getCell('D2')->getValue() . "\n";
echo "E2=" . $s->getCell('E2')->getValue() . "\n";
