<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Company::first();
echo "login_logo: " . $c->login_logo . "\n";
echo "masked_login_logo_url: " . var_export($c->masked_login_logo_url, true) . "\n";
echo "login_logo_url: " . var_export($c->login_logo_url, true) . "\n";
echo "asset: " . asset_url_local_s3('app-logo/' . $c->login_logo) . "\n";
