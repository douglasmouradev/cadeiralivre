<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class WorkingHoursModel
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
            'SELECT * FROM working_hours WHERE tenant_id = :t AND barber_id = :b ORDER BY day_of_week ASC'
        );
        $stmt->execute(['t' => $tenantId, 'b' => $barberId]);

        return $stmt->fetchAll() ?: [];
    }

    public function replaceWeek(int $tenantId, int $barberId, array $rows): void
    {
        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $del = $this->pdo->prepare('DELETE FROM working_hours WHERE tenant_id = :t AND barber_id = :b');
            $del->execute(['t' => $tenantId, 'b' => $barberId]);
            $ins = $this->pdo->prepare(
                'INSERT INTO working_hours (barber_id, tenant_id, day_of_week, start_time, end_time, is_day_off)
                 VALUES (:b, :t, :dow, :st, :en, :off)'
            );
            foreach ($rows as $r) {
                $ins->execute([
                    'b' => $barberId,
                    't' => $tenantId,
                    'dow' => (int) $r['day_of_week'],
                    'st' => $r['start_time'],
                    'en' => $r['end_time'],
                    'off' => (int) (bool) ($r['is_day_off'] ?? false),
                ]);
            }
            if ($ownTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
