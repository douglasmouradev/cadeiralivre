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

    /** @return list<array<string, mixed>> */
    public function listForTenant(int $tenantId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, c.name AS client_name, a.start_datetime, s.name AS service_name, u.name AS barber_name
             FROM reviews r
             INNER JOIN clients c ON c.id = r.client_id AND c.tenant_id = r.tenant_id
             INNER JOIN appointments a ON a.id = r.appointment_id AND a.tenant_id = r.tenant_id
             LEFT JOIN services s ON s.id = a.service_id AND s.tenant_id = r.tenant_id
             LEFT JOIN barbers b ON b.id = a.barber_id AND b.tenant_id = r.tenant_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE r.tenant_id = :t
             ORDER BY r.created_at DESC
             LIMIT ' . max(1, min(200, $limit))
        );
        $stmt->execute(['t' => $tenantId]);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /** @return array{count: int, average: float} */
    public function statsForTenant(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS cnt, COALESCE(AVG(rating), 0) AS avg_rating FROM reviews WHERE tenant_id = :t'
        );
        $stmt->execute(['t' => $tenantId]);
        $row = $stmt->fetch();

        return [
            'count' => (int) ($row['cnt'] ?? 0),
            'average' => round((float) ($row['avg_rating'] ?? 0), 1),
        ];
    }

    public function setPublic(int $tenantId, int $reviewId, bool $isPublic): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE reviews SET is_public = :p WHERE id = :id AND tenant_id = :t'
        );
        $stmt->execute(['p' => (int) $isPublic, 'id' => $reviewId, 't' => $tenantId]);

        return $stmt->rowCount() > 0;
    }
}
