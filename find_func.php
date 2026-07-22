<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$refl = new ReflectionFunction('currency_format_setting');
echo $refl->getFileName() . ':' . $refl->getStartLine();
