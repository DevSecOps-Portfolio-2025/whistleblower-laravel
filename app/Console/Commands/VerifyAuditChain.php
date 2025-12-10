<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\Whistleblowing\Infrastructure\Services\ImmutableAuditService;

class VerifyAuditChain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:verify
                          {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the integrity of the immutable audit chain';

    /**
     * Execute the console command.
     */
    public function handle(ImmutableAuditService $auditService): int
    {
        $this->info('🔍 Verifying audit chain integrity...');
        $this->newLine();

        $result = $auditService->verifyChain();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $result['valid'] ? self::SUCCESS : self::FAILURE;
        }

        // Display results in formatted output
        $this->info("📊 Total audit log entries: {$result['total_entries']}");
        $this->newLine();

        if ($result['valid']) {
            $this->components->info('✅ AUDIT CHAIN INTEGRITY VERIFIED');
            $this->info('All hashes match. No corruption detected.');
            return self::SUCCESS;
        }

        // Corruption detected
        $this->components->error('❌ CORRUPTION DETECTED IN AUDIT CHAIN');
        $this->newLine();
        
        $this->error('⚠️  The following entries have been tampered with:');
        $this->newLine();

        $corruptedCount = count($result['corrupted_entries']);
        $this->warn("Found {$corruptedCount} corrupted " . ($corruptedCount === 1 ? 'entry' : 'entries') . ':');
        $this->newLine();

        $headers = ['ID', 'Entity Type', 'Entity ID', 'Action', 'Created At', 'Stored Hash'];
        $rows = array_map(function ($entry) {
            return [
                $entry['id'],
                $entry['entity_type'],
                $entry['entity_id'],
                $entry['action'],
                $entry['created_at'],
                substr($entry['stored_hash'], 0, 16) . '...',
            ];
        }, $result['corrupted_entries']);

        $this->table($headers, $rows);

        $this->newLine();
        $this->error('🚨 SECURITY ALERT: Audit log manipulation detected!');
        $this->warn('Immediate investigation required.');

        return self::FAILURE;
    }
}
