<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class BarberDateHoursModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findForDate(int $tenantId, int $barberId, string $dateYmd): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM barber_date_hours
             WHERE tenant_id = :t AND barber_id = :b AND work_date = :d LIMIT 1'
        );
        $stmt->execute(['t' => $tenantId, 'b' => $barberId, 'd' => $dateYmd]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function listUpcoming(int $tenantId, int $barberId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM barber_date_hours
             WHERE tenant_id = :t AND barber_id = :b AND work_date >= CURDATE()
             ORDER BY work_date ASC
             LIMIT :lim'
        );
        $stmt->bindValue(':t', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':b', $barberId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function upsert(
        int $tenantId,
        int $barberId,
        string $dateYmd,
        string $startTime,
        string $endTime,
        bool $isClosed,
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO barber_date_hours (barber_id, tenant_id, work_date, start_time, end_time, is_closed)
             VALUES (:b, :t, :d, :st, :en, :c)
             ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time),
                 is_closed = VALUES(is_closed), updated_at = NOW()'
        );
        $stmt->execute([
            'b' => $barberId,
            't' => $tenantId,
            'd' => $dateYmd,
            'st' => $startTime,
            'en' => $endTime,
            'c' => (int) $isClosed,
        ]);
    }

    public function delete(int $tenantId, int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM barber_date_hours WHERE tenant_id = :t AND id = :id');
        $stmt->execute(['t' => $tenantId, 'id' => $id]);
    }
}
