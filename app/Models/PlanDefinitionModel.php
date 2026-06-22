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

    /**
     * @param array{name?: string, max_barbers?: ?int, max_appointments_per_month?: ?int, monthly_price_cents?: int, stripe_price_id?: ?string, sort_order?: int} $fields
     */
    public function update(int $id, array $fields): void
    {
        $allowed = ['name', 'max_barbers', 'max_appointments_per_month', 'monthly_price_cents', 'stripe_price_id', 'sort_order'];
        $sets = [];
        $params = ['id' => $id];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $sets[] = $col . ' = :' . $col;
                $params[$col] = $fields[$col];
            }
        }
        if ($sets === []) {
            return;
        }
        $sql = 'UPDATE plan_definitions SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function countTenantsOnPlan(int $planId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tenants WHERE plan_definition_id = :id');
        $stmt->execute(['id' => $planId]);

        return (int) $stmt->fetchColumn();
    }
}
