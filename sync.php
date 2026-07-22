<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Company::first();
$g = \App\Models\GlobalSetting::first();
if ($c && $g) {
    $g->login_logo = $c->login_logo;
    $g->login_ui = $c->login_ui;
    $g->save();
    echo "Synced!";
}
