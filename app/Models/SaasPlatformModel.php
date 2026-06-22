<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SaasPlatformModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return array<string, int|float> */
    public function platformStats(): array
    {
        $sql = "SELECT
            (SELECT COUNT(*) FROM tenants) AS total_tenants,
            (SELECT COUNT(*) FROM tenants WHERE status <> 'suspended') AS active_tenants,
            (SELECT COUNT(*) FROM tenants WHERE status = 'suspended') AS suspended_tenants,
            (SELECT COUNT(*) FROM tenants WHERE subscription_status = 'trialing') AS trialing_tenants,
            (SELECT COUNT(*) FROM tenants WHERE subscription_status = 'past_due') AS past_due_tenants,
            (SELECT COUNT(*) FROM tenants WHERE subscription_status = 'active') AS paying_tenants,
            (SELECT COUNT(*) FROM tenants WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS new_tenants_30d,
            (SELECT COUNT(*) FROM appointments WHERE DATE(start_datetime) = CURDATE() AND status NOT IN ('cancelled','no_show')) AS appointments_today,
            (SELECT COUNT(*) FROM appointments WHERE YEAR(start_datetime) = YEAR(CURDATE()) AND MONTH(start_datetime) = MONTH(CURDATE()) AND status NOT IN ('cancelled','no_show')) AS appointments_month,
            (SELECT COALESCE(SUM(p.monthly_price_cents), 0) FROM tenants t
                INNER JOIN plan_definitions p ON p.id = t.plan_definition_id
                WHERE t.subscription_status = 'active' AND t.status <> 'suspended') AS mrr_cents";
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return [];
        }
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return [];
        }

        return array_map(static fn ($v) => is_numeric($v) ? (str_contains((string) $v, '.') ? (float) $v : (int) $v) : 0, $row);
    }

    /** @return list<array<string, mixed>> */
    public function trialsExpiringSoon(int $withinDays = 7): array
    {
        $days = max(1, min($withinDays, 30));
        $stmt = $this->pdo->prepare(
            "SELECT t.*, p.name AS plan_label
             FROM tenants t
             LEFT JOIN plan_definitions p ON p.id = t.plan_definition_id
             WHERE t.status <> 'suspended'
               AND t.trial_ends_at IS NOT NULL
               AND t.trial_ends_at <= DATE_ADD(NOW(), INTERVAL :days DAY)
               AND t.trial_ends_at >= NOW()
             ORDER BY t.trial_ends_at ASC
             LIMIT 20"
        );
        $stmt->execute(['days' => $days]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTenantsFiltered(?string $search, ?string $status, ?string $subscriptionStatus, string $sort): array
    {
        $sql = 'SELECT t.*, p.name AS plan_label, p.slug AS plan_slug, p.monthly_price_cents
                FROM tenants t
                LEFT JOIN plan_definitions p ON p.id = t.plan_definition_id
                WHERE 1=1';
        $params = [];

        $q = trim((string) $search);
        if ($q !== '') {
            $sql .= ' AND (t.name LIKE :q OR t.slug LIKE :q OR t.email LIKE :q OR t.city LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            if ($status === 'active') {
                $sql .= " AND t.status <> 'suspended'";
            } elseif ($status === 'suspended') {
                $sql .= " AND t.status = 'suspended'";
            } elseif ($status === 'trial') {
                $sql .= " AND t.status = 'trial'";
            }
        }
        if ($subscriptionStatus !== null && $subscriptionStatus !== '' && $subscriptionStatus !== 'all') {
            $sql .= ' AND t.subscription_status = :sub';
            $params['sub'] = $subscriptionStatus;
        }

        $order = match ($sort) {
            'name' => 't.name ASC',
            'created_asc' => 't.created_at ASC',
            'trial' => 't.trial_ends_at IS NULL, t.trial_ends_at ASC',
            default => 't.created_at DESC',
        };
        $sql .= ' ORDER BY ' . $order;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed> */
    public function tenantDetailStats(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM appointments WHERE tenant_id = :t1 AND YEAR(start_datetime) = YEAR(CURDATE()) AND MONTH(start_datetime) = MONTH(CURDATE()) AND status NOT IN ('cancelled','no_show')) AS appointments_month,
                (SELECT COUNT(*) FROM appointments WHERE tenant_id = :t2 AND DATE(start_datetime) = CURDATE() AND status NOT IN ('cancelled','no_show')) AS appointments_today,
                (SELECT COUNT(*) FROM clients WHERE tenant_id = :t3) AS clients_total,
                (SELECT COUNT(*) FROM barbers WHERE tenant_id = :t4) AS barbers_total,
                (SELECT MAX(start_datetime) FROM appointments WHERE tenant_id = :t5 AND status NOT IN ('cancelled','no_show')) AS last_appointment_at"
        );
        $stmt->execute([
            't1' => $tenantId,
            't2' => $tenantId,
            't3' => $tenantId,
            't4' => $tenantId,
            't5' => $tenantId,
        ]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : [];
    }
}
