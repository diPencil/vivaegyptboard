<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lead;

$lead = Lead::find(2);
if (!$lead) {
    echo "LEAD_NOT_FOUND\n";
    exit(1);
}

echo "ID:" . $lead->id . "\n";
echo "mobile:" . ($lead->mobile ?? 'NULL') . "\n";
echo "category_id:" . ($lead->category_id ?? 'NULL') . "\n";
echo "category_name:" . ($lead->category?->category_name ?? 'NULL') . "\n";
echo "lead_requirements:" . ($lead->lead_requirements ?? 'NULL') . "\n";
