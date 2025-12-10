<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$log = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::first();

echo "=== PAYLOAD COMPARISON ===\n\n";

$storedPayload = $log->payload;
echo "Stored payload (from DB):\n";
echo json_encode($storedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

$sorted = $storedPayload;
ksort($sorted);
echo "Sorted payload:\n";
echo json_encode($sorted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "Are they equal? " . (json_encode($storedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($sorted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ? 'YES' : 'NO') . "\n";
