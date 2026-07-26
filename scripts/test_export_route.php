<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\LeadContactsExport;
use Maatwebsite\Excel\Facades\Excel;

// Attempt to authenticate as user ID 1
try {
    Auth::loginUsingId(1);
} catch (Throwable $e) {
    // ignore
}

// Check permission helper
if (!function_exists('canDataTableExport')) {
    function canDataTableExport() { return true; }
}

$req = Request::create('/','GET', []);

// Generate raw XLSX bytes
$bytes = Excel::raw(new LeadContactsExport($req), \Maatwebsite\Excel\Excel::XLSX);

$outDir = __DIR__ . '/../public/exports';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);
$outPath = $outDir . '/lead_export_route.xlsx';
file_put_contents($outPath, $bytes);

echo "WROTE: $outPath\n";
