<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PaymentModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function create(int $tenantId, int $appointmentId, float $amount, string $method, string $status): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (appointment_id, tenant_id, amount, method, status, paid_at, created_at)
             VALUES (:a, :t, :amt, :m, :st, :pa, NOW())'
        );
        $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;
        $stmt->execute([
            'a' => $appointmentId,
            't' => $tenantId,
            'amt' => $amount,
            'm' => $method,
            'st' => $status,
            'pa' => $paidAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function forAppointment(int $tenantId, int $appointmentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE tenant_id = :t AND appointment_id = :a ORDER BY id ASC');
        $stmt->execute(['t' => $tenantId, 'a' => $appointmentId]);

        return $stmt->fetchAll() ?: [];
    }

    public function hasPaidForAppointment(int $tenantId, int $appointmentId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM payments WHERE tenant_id = :t AND appointment_id = :a AND status = 'paid'"
        );
        $stmt->execute(['t' => $tenantId, 'a' => $appointmentId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function markPaid(int $tenantId, int $paymentId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE payments SET status = 'paid', paid_at = NOW() WHERE tenant_id = :t AND id = :id"
        );
        $stmt->execute(['t' => $tenantId, 'id' => $paymentId]);
    }
}
