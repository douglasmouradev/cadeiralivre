<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PlanDefinitionModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM plan_definitions WHERE slug = :s LIMIT 1');
        $stmt->execute(['s' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM plan_definitions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM plan_definitions ORDER BY sort_order ASC, id ASC');
        if ($stmt === false) {
            return [];
        }

        return $stmt->fetchAll() ?: [];
    }

    public function idForSignupDefault(): int
    {
        $row = $this->findBySlug('free');

        return $row !== null ? (int) $row['id'] : 0;
    }

    /** @return array<string, mixed>|null */
    public function findByStripePriceId(string $stripePriceId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM plan_definitions WHERE stripe_price_id = :p LIMIT 1');
        $stmt->execute(['p' => $stripePriceId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
