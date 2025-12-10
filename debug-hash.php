<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$log = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::first();
$service = app(\Src\Whistleblowing\Infrastructure\Services\ImmutableAuditService::class);

echo "=== MANUAL HASH CALCULATION ===\n\n";

$timestampString = $log->created_at->format('Y-m-d H:i:s');
$payload = $log->payload ?? [];

echo "Inputs:\n";
echo "  previous_hash: " . $log->previous_hash . "\n";
echo "  action: " . $log->action . "\n";
echo "  entity_type: " . $log->entity_type . "\n";
echo "  entity_id: " . $log->entity_id . "\n";
echo "  timestamp: " . $timestampString . "\n";
echo "  payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
echo "  salt: " . config('app.audit_salt') . "\n\n";

$data = implode('|', [
    $log->previous_hash,
    $log->action,
    $log->entity_type,
    $log->entity_id,
    $timestampString,
    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    config('app.audit_salt'),
]);

echo "Data string to hash:\n";
echo $data . "\n\n";

$calculatedHash = hash('sha256', $data);

echo "Calculated hash: " . $calculatedHash . "\n";
echo "Stored hash:     " . $log->hash . "\n";
echo "Match: " . ($calculatedHash === $log->hash ? 'YES ✅' : 'NO ❌') . "\n";
