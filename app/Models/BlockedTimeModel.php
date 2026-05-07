<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class BlockedTimeModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return list<array<string, mixed>> */
    public function forBarber(int $tenantId, int $barberId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM blocked_times WHERE tenant_id = :t AND barber_id = :b ORDER BY start_datetime DESC'
        );
        $stmt->execute(['t' => $tenantId, 'b' => $barberId]);

        return $stmt->fetchAll() ?: [];
    }

    public function create(int $tenantId, int $barberId, string $start, string $end, ?string $reason): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO blocked_times (barber_id, tenant_id, start_datetime, end_datetime, reason)
             VALUES (:b, :t, :s, :e, :r)'
        );
        $stmt->execute(['b' => $barberId, 't' => $tenantId, 's' => $start, 'e' => $end, 'r' => $reason]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $tenantId, int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM blocked_times WHERE tenant_id = :t AND id = :id');
        $stmt->execute(['t' => $tenantId, 'id' => $id]);
    }
}
