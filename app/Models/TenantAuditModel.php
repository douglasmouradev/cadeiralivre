<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class TenantAuditModel
{
    private PDO $pdo;

    /** @var bool|null */
    private static ?bool $tableReady = null;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function isAvailable(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'tenant_audit_logs'");
            self::$tableReady = $stmt !== false && $stmt->fetch() !== false;
        } catch (\Throwable) {
            self::$tableReady = false;
        }

        return self::$tableReady;
    }

    /** @param array<string, mixed>|null $meta */
    public function log(int $tenantId, int $actorUserId, string $action, ?array $meta = null): void
    {
        if (!$this->isAvailable()) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO tenant_audit_logs (tenant_id, actor_user_id, action, meta_json, created_at)
             VALUES (:tenant, :actor, :action, :meta, NOW())'
        );
        $stmt->execute([
            'tenant' => $tenantId,
            'actor' => $actorUserId,
            'action' => $action,
            'meta' => $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $tenantId, int $limit = 30): array
    {
        if (!$this->isAvailable()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT l.*, u.name AS actor_name
             FROM tenant_audit_logs l
             INNER JOIN users u ON u.id = l.actor_user_id
             WHERE l.tenant_id = :t
             ORDER BY l.id DESC
             LIMIT ' . max(1, min($limit, 100))
        );
        $stmt->execute(['t' => $tenantId]);

        return $stmt->fetchAll() ?: [];
    }
}
