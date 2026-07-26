<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Exports\LeadContactsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

// Create request with no filters to emulate full export
$req = Request::create('/','GET', []);

$export = new LeadContactsExport($req);

$outPath = 'public/exports/leads_test.xlsx';
if (!is_dir(dirname($outPath))) {
    mkdir(dirname($outPath), 0777, true);
}

Excel::store($export, 'exports/leads_test.xlsx');

echo "WROTE: public/exports/leads_test.xlsx\n";
