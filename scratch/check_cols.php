<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$results = \Illuminate\Support\Facades\DB::select("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('tasks', 'users', 'task_notes', 'task_comments') AND COLUMN_NAME IN ('id', 'task_id', 'added_by', 'last_updated_by')");
print_r($results);

$laravelVersion = app()->version();
echo "Laravel Version: " . $laravelVersion . "\n";
