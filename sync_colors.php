<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Company::first();
$g = \App\Models\GlobalSetting::first();
if ($c && $g) {
    $g->header_color = $c->header_color;
    $g->logo_background_color = $c->logo_background_color;
    $g->auth_theme = $c->auth_theme;
    $g->auth_theme_text = $c->auth_theme_text;
    $g->saveQuietly();
    echo "Synced colors!";
}
