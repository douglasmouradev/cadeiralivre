<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SaasAuditModel
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
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'saas_audit_logs'");
            self::$tableReady = $stmt !== false && $stmt->fetch() !== false;
        } catch (\Throwable) {
            self::$tableReady = false;
        }

        return self::$tableReady;
    }

    /** @param array<string, mixed>|null $meta */
    public function log(int $actorUserId, string $action, ?int $tenantId = null, ?array $meta = null): void
    {
        if (!$this->isAvailable()) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO saas_audit_logs (actor_user_id, action, tenant_id, meta_json, created_at)
             VALUES (:actor, :action, :tenant, :meta, NOW())'
        );
        $stmt->execute([
            'actor' => $actorUserId,
            'action' => $action,
            'tenant' => $tenantId,
            'meta' => $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 50, ?int $tenantId = null): array
    {
        if (!$this->isAvailable()) {
            return [];
        }
        $sql = 'SELECT l.*, u.name AS actor_name, u.email AS actor_email, t.name AS tenant_name
                FROM saas_audit_logs l
                INNER JOIN users u ON u.id = l.actor_user_id
                LEFT JOIN tenants t ON t.id = l.tenant_id';
        $params = [];
        if ($tenantId !== null) {
            $sql .= ' WHERE l.tenant_id = :tid';
            $params['tid'] = $tenantId;
        }
        $sql .= ' ORDER BY l.id DESC LIMIT ' . max(1, min($limit, 200));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }
}
