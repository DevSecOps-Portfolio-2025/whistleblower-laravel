<?php

namespace Src\Whistleblowing\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog;

class ImmutableAuditService
{
    /**
     * Genesis hash used when there are no previous records.
     */
    private const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Secret salt for hash calculation (should be in .env in production).
     */
    private string $secretSalt;

    public function __construct()
    {
        $this->secretSalt = config('app.audit_salt', 'default-audit-salt-change-in-production');
    }

    /**
     * Log an action on an entity to the immutable audit trail.
     *
     * @param string $action The action performed (Create, Read, Update, Delete)
     * @param object $entity The entity being audited (Report or Message)
     * @param string|null $ip The actor's IP address (will be anonymized)
     * @return AuditLog The created audit log entry
     */
    public function log(string $action, object $entity, ?string $ip = null): AuditLog
    {
        // Use transaction to ensure atomicity
        return DB::transaction(function () use ($action, $entity, $ip) {
            // Get the last audit log record
            $lastLog = AuditLog::orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            // Get previous hash or use genesis hash
            $previousHash = $lastLog ? $lastLog->hash : self::GENESIS_HASH;

            // Extract entity data
            $entityType = $this->getEntityType($entity);
            $entityId = $entity->id ?? $entity->getId();
            $payload = $this->serializeEntity($entity);
            $anonymizedIp = $this->anonymizeIp($ip);
            $timestamp = now();

            // Use a deterministic timestamp format (UTC, no microseconds)
            $timestampString = $timestamp->format('Y-m-d H:i:s');

            // Calculate the new hash
            $hash = $this->calculateHash(
                $previousHash,
                $action,
                $entityType,
                $entityId,
                $timestampString,
                $payload
            );

            // Create and save the audit log
            $auditLog = new AuditLog([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'payload' => $payload,
                'actor_ip' => $anonymizedIp,
                'previous_hash' => $previousHash,
                'hash' => $hash,
                'created_at' => $timestamp,
            ]);

            $auditLog->save();

            return $auditLog;
        });
    }

    /**
     * Calculate the SHA256 hash for the audit log entry.
     *
     * @param string $previousHash
     * @param string $action
     * @param string $entityType
     * @param string $entityId
     * @param string $timestamp
     * @param array $payload
     * @return string
     */
    private function calculateHash(
        string $previousHash,
        string $action,
        string $entityType,
        string $entityId,
        string $timestamp,
        array $payload
    ): string {
        // Sort payload keys for deterministic JSON encoding
        // This ensures the hash is the same regardless of array key order
        ksort($payload);

        // Create a deterministic string from the data
        $data = implode('|', [
            $previousHash,
            $action,
            $entityType,
            $entityId,
            $timestamp,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $this->secretSalt,
        ]);

        return hash('sha256', $data);
    }

    /**
     * Verify the integrity of a specific audit log entry.
     *
     * @param AuditLog $auditLog
     * @return bool
     */
    public function verifyEntry(AuditLog $auditLog): bool
    {
        // Use the same deterministic format as when creating
        $timestampString = $auditLog->created_at->format('Y-m-d H:i:s');

        $calculatedHash = $this->calculateHash(
            $auditLog->previous_hash,
            $auditLog->action,
            $auditLog->entity_type,
            $auditLog->entity_id,
            $timestampString,
            $auditLog->payload ?? []
        );

        return $calculatedHash === $auditLog->hash;
    }

    /**
     * Verify the integrity of the entire audit chain.
     *
     * @return array{valid: bool, corrupted_entries: array, total_entries: int}
     */
    public function verifyChain(): array
    {
        $corruptedEntries = [];
        $totalEntries = 0;

        // Get all audit logs ordered chronologically
        AuditLog::orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->chunk(100, function ($logs) use (&$corruptedEntries, &$totalEntries) {
                foreach ($logs as $log) {
                    $totalEntries++;

                    if (!$this->verifyEntry($log)) {
                        $corruptedEntries[] = [
                            'id' => $log->id,
                            'entity_type' => $log->entity_type,
                            'entity_id' => $log->entity_id,
                            'action' => $log->action,
                            'created_at' => $log->created_at->toIso8601String(),
                            'stored_hash' => $log->hash,
                        ];
                    }
                }
            });

        return [
            'valid' => empty($corruptedEntries),
            'corrupted_entries' => $corruptedEntries,
            'total_entries' => $totalEntries,
        ];
    }

    /**
     * Get the entity type name.
     *
     * @param object $entity
     * @return string
     */
    private function getEntityType(object $entity): string
    {
        $className = get_class($entity);
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Serialize entity to array for audit payload.
     *
     * @param object $entity
     * @return array
     */
    private function serializeEntity(object $entity): array
    {
        // Check if entity has toArray method (Eloquent models)
        if (method_exists($entity, 'toArray')) {
            return $entity->toArray();
        }

        // For domain entities, try to extract properties
        if (method_exists($entity, 'getId')) {
            $data = ['id' => $entity->getId()];

            // Try common getter methods (excluding sensitive data like AccessCode)
            $methods = ['getTitle', 'getDescription', 'getStatus', 'getReportId', 'getMessage'];
            foreach ($methods as $method) {
                if (method_exists($entity, $method)) {
                    $key = lcfirst(substr($method, 3)); // Remove 'get' and lowercase first char
                    $value = $entity->$method();
                    
                    // Handle Value Objects
                    if (is_object($value) && method_exists($value, 'value')) {
                        $data[$key] = $value->value();
                    } else {
                        $data[$key] = $value;
                    }
                }
            }

            // Sort keys for deterministic JSON encoding
            ksort($data);

            return $data;
        }

        // Fallback: use object properties
        return get_object_vars($entity);
    }

    /**
     * Anonymize an IP address for GDPR compliance.
     *
     * @param string|null $ip
     * @return string|null
     */
    private function anonymizeIp(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        // IPv4: Replace last octet with 0
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        // IPv6: Keep only first 4 groups
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::';
        }

        return $ip;
    }
}
