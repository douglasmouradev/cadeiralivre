<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ReviewModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function create(int $tenantId, int $appointmentId, int $clientId, int $rating, ?string $comment, bool $isPublic): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reviews (appointment_id, tenant_id, client_id, rating, comment, is_public, created_at)
             VALUES (:a, :t, :c, :r, :cm, :p, NOW())'
        );
        $stmt->execute([
            'a' => $appointmentId,
            't' => $tenantId,
            'c' => $clientId,
            'r' => $rating,
            'cm' => $comment,
            'p' => (int) $isPublic,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findByAppointment(int $tenantId, int $appointmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM reviews WHERE tenant_id = :t AND appointment_id = :a LIMIT 1'
        );
        $stmt->execute(['t' => $tenantId, 'a' => $appointmentId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
