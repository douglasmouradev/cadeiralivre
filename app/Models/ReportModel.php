<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ReportModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return list<array<string, mixed>> */
    public function revenueByPeriod(int $tenantId, string $start, string $end): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(p.paid_at) AS day, SUM(p.amount) AS total
             FROM payments p
             WHERE p.tenant_id = :t AND p.status = 'paid' AND p.paid_at IS NOT NULL
               AND DATE(p.paid_at) BETWEEN :s AND :e
             GROUP BY DATE(p.paid_at) ORDER BY day ASC"
        );
        $stmt->execute(['t' => $tenantId, 's' => $start, 'e' => $end]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function revenueByBarber(int $tenantId, string $start, string $end): array
    {
        $sql = "SELECT u.name AS barber_name, SUM(p.amount) AS total
                FROM payments p
                INNER JOIN appointments a ON a.id = p.appointment_id
                INNER JOIN barbers b ON b.id = a.barber_id
                INNER JOIN users u ON u.id = b.user_id
                WHERE p.tenant_id = :t AND p.status = 'paid' AND p.paid_at IS NOT NULL
                  AND DATE(p.paid_at) BETWEEN :s AND :e
                GROUP BY b.id, u.name
                ORDER BY total DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['t' => $tenantId, 's' => $start, 'e' => $end]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function revenueByService(int $tenantId, string $start, string $end): array
    {
        $sql = "SELECT s.name AS service_name, SUM(p.amount) AS total
                FROM payments p
                INNER JOIN appointments a ON a.id = p.appointment_id
                INNER JOIN services s ON s.id = a.service_id
                WHERE p.tenant_id = :t AND p.status = 'paid' AND p.paid_at IS NOT NULL
                  AND DATE(p.paid_at) BETWEEN :s AND :e
                GROUP BY s.id, s.name
                ORDER BY total DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['t' => $tenantId, 's' => $start, 'e' => $end]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function commissionsByBarber(int $tenantId, string $start, string $end): array
    {
        $sql = "SELECT u.name AS barber_name, b.commission_percent,
                       SUM(p.amount) AS gross,
                       SUM(p.amount * (b.commission_percent / 100)) AS commission
                FROM payments p
                INNER JOIN appointments a ON a.id = p.appointment_id
                INNER JOIN barbers b ON b.id = a.barber_id
                INNER JOIN users u ON u.id = b.user_id
                WHERE p.tenant_id = :t AND p.status = 'paid' AND p.paid_at IS NOT NULL
                  AND DATE(p.paid_at) BETWEEN :s AND :e
                GROUP BY b.id, u.name, b.commission_percent
                ORDER BY commission DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['t' => $tenantId, 's' => $start, 'e' => $end]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, float|int> */
    public function cancellationStats(int $tenantId, string $start, string $end): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) AS no_show,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                COUNT(*) AS total
             FROM appointments
             WHERE tenant_id = :t AND DATE(start_datetime) BETWEEN :s AND :e"
        );
        $stmt->execute(['t' => $tenantId, 's' => $start, 'e' => $end]);
        $row = $stmt->fetch() ?: ['no_show' => 0, 'cancelled' => 0, 'total' => 0];
        $total = (int) ($row['total'] ?? 0);
        $ns = (int) ($row['no_show'] ?? 0);
        $cx = (int) ($row['cancelled'] ?? 0);

        return [
            'no_show' => $ns,
            'cancelled' => $cx,
            'total' => $total,
            'no_show_rate' => $total > 0 ? round($ns / $total * 100, 2) : 0.0,
            'cancel_rate' => $total > 0 ? round($cx / $total * 100, 2) : 0.0,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function peakHours(int $tenantId, string $start, string $end): array
    {
        $sql = "SELECT HOUR(start_datetime) AS hr, COUNT(*) AS cnt
                FROM appointments
                WHERE tenant_id = :t AND DATE(start_datetime) BETWEEN :s AND :e
                  AND status NOT IN ('cancelled','no_show')
                GROUP BY HOUR(start_datetime)
                ORDER BY cnt DESC, hr ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['t' => $tenantId, 's' => $start, 'e' => $end]);

        return $stmt->fetchAll() ?: [];
    }
}
