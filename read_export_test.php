<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

function readExcel($filePath) {
    echo "\n=== Reading $filePath ===\n";
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return;
    }

    try {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        
        echo "Highest Row: $highestRow, Highest Column: $highestColumn\n";
        
        // Print first row (Headings)
        echo "Headings: \n";
        $headings = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false);
        print_r($headings[0]);
        
        // Print second row (Sample data)
        if ($highestRow > 1) {
            echo "\nSample Data (Row 2): \n";
            $data = $worksheet->rangeToArray('A2:' . $highestColumn . '2', null, true, false);
            print_r($data[0]);
        }
        
    } catch (\Exception $e) {
        echo "Error reading file: " . $e->getMessage() . "\n";
    }
}

$storagePath = public_path('user-uploads/');

readExcel($storagePath . 'attendance_export_qa.xlsx');
readExcel($storagePath . 'attendance_by_member_qa.xlsx');
readExcel($storagePath . 'shift_schedule_qa.xlsx');

echo "\nDone.\n";
