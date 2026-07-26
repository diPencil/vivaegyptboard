<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lead;
use App\Models\LeadCategory;

// Ensure category exists
$category = LeadCategory::firstOrCreate(['category_name' => 'Umrah']);

$lead = new Lead();
$lead->client_name = 'Test Lead';
$lead->client_email = 'testlead@example.com';
$lead->mobile = '01110575930';
$lead->category_id = $category->id;
$lead->lead_requirements = 'Customer requests an Umrah package for two adults.';
$lead->added_by = 1;
$lead->lead_owner = 1;
$lead->save();

echo "CREATED_LEAD_ID: {$lead->id}\n";
