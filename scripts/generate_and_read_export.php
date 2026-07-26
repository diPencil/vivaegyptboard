<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lead;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$leads = Lead::with('category')->select('client_name','client_email','mobile','lead_requirements','created_at','category_id')->get();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = [
    trans('modules.leadContact.contactName'),
    trans('modules.lead.email'),
    trans('modules.lead.mobile'),
    trans('modules.lead.leadCategory'),
    trans('modules.lead.leadRequirements'),
    trans('app.createdOn'),
];
$sheet->fromArray($headers, null, 'A1');

$row = 2;
foreach ($leads as $lead) {
    $vals = [
        $lead->client_name,
        $lead->client_email,
        $lead->mobile,
        $lead->category?->category_name ?? '',
        strip_tags($lead->lead_requirements ?? ''),
        optional($lead->created_at)->format('Y-m-d H:i:s'),
    ];

    $col = 'A';
    foreach ($vals as $i => $v) {
        $cell = $col . $row;
        // Sanitize potential formula injection
        if (is_string($v) && preg_match('/^[=+\-@]/', $v)) {
            $v = "'" . $v;
        }
        // Force mobile (column C) and requirements (column E) as string
        if ($col === 'C' || $col === 'E') {
            $sheet->setCellValueExplicit($cell, (string)$v, DataType::TYPE_STRING);
        } else {
            $sheet->setCellValue($cell, $v);
        }
        $col++;
    }
    $row++;
}

$outDir = __DIR__ . '/../public/exports';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);
$outPath = $outDir . '/leads_test.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outPath);

echo "WROTE: $outPath\n";

// Now read back
use PhpOffice\PhpSpreadsheet\IOFactory;
$reader = IOFactory::createReader('Xlsx');
$ss = $reader->load($outPath);
$s = $ss->getActiveSheet();
$headersRow = [];
foreach ($s->getRowIterator(1,1)->current()->getCellIterator() as $cell) {
    $headersRow[] = $cell->getValue();
}
echo "HEADERS: " . implode(' | ', $headersRow) . "\n";

$dataRow = [];
foreach ($s->getRowIterator(2,2)->current()->getCellIterator() as $cell) {
    $dataRow[] = $cell->getValue();
}
echo "ROW2: " . implode(' | ', $dataRow) . "\n";
