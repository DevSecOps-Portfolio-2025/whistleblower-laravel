<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create a report
$useCase = app(\Src\Whistleblowing\Application\UseCases\CreateReportUseCase::class);

$result = $useCase->execute([
    'title' => 'Test Audit Trail - ' . date('Y-m-d H:i:s'),
    'description' => 'Testing immutable audit trail functionality with corrected timestamp format',
    'reporterId' => null,
    'actorIp' => '192.168.1.100'
]);

echo "✅ Report created successfully!\n";
echo "Report ID: {$result->reportId}\n";
echo "Access Code: {$result->accessCode}\n\n";

// Check audit logs
$auditCount = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::count();
echo "📊 Total audit logs: {$auditCount}\n";

if ($auditCount > 0) {
    $lastAudit = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::latest('created_at')->first();
    echo "Last audit log:\n";
    echo "  - Entity: {$lastAudit->entity_type}\n";
    echo "  - Action: {$lastAudit->action}\n";
    echo "  - Hash: " . substr($lastAudit->hash, 0, 16) . "...\n";
}
