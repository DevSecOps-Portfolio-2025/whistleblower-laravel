<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$log = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::first();

echo "=== DEBUGGING AUDIT LOG ===\n\n";
echo "Raw created_at from DB: " . $log->getAttributes()['created_at'] . "\n";
echo "Carbon format('Y-m-d H:i:s'): " . $log->created_at->format('Y-m-d H:i:s') . "\n";
echo "Carbon toIso8601String(): " . $log->created_at->toIso8601String() . "\n\n";

echo "Stored hash: " . $log->hash . "\n";
echo "Previous hash: " . $log->previous_hash . "\n\n";

// Try to recalculate the hash
$service = app(\Src\Whistleblowing\Infrastructure\Services\ImmutableAuditService::class);
$isValid = $service->verifyEntry($log);

echo "Is valid: " . ($isValid ? 'YES ✅' : 'NO ❌') . "\n";

// Manual calculation with different formats
echo "\n=== TESTING DIFFERENT FORMATS ===\n";
$formats = [
    'Y-m-d H:i:s',
    'Y-m-d\TH:i:s\Z',
    'Y-m-d\TH:i:sP',
    $log->getAttributes()['created_at'],
];

foreach ($formats as $format) {
    if (strpos($format, '-') === 4) {
        // It's already a string
        $timestampString = $format;
    } else {
        $timestampString = $log->created_at->format($format);
    }
    
    echo "\nFormat: $format\n";
    echo "Result: $timestampString\n";
}
