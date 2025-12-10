<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "APP_AUDIT_SALT: " . config('app.audit_salt') . "\n";
echo "Length: " . strlen(config('app.audit_salt')) . "\n";
echo "Is empty: " . (empty(config('app.audit_salt')) ? 'YES' : 'NO') . "\n";
