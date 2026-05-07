<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ServiceModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return list<array<string, mixed>> */
    public function allForTenant(int $tenantId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM services WHERE tenant_id = :t';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY display_order ASC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['t' => $tenantId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function find(int $tenantId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM services WHERE tenant_id = :t AND id = :id LIMIT 1');
        $stmt->execute(['t' => $tenantId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO services (tenant_id, name, description, duration_minutes, price, category, is_active, display_order, created_at, updated_at)
             VALUES (:t, :name, :desc, :dur, :price, :cat, :active, :ord, NOW(), NOW())'
        );
        $stmt->execute([
            't' => $tenantId,
            'name' => $data['name'],
            'desc' => $data['description'],
            'dur' => (int) $data['duration_minutes'],
            'price' => $data['price'],
            'cat' => $data['category'],
            'active' => (int) (bool) $data['is_active'],
            'ord' => (int) ($data['display_order'] ?? 0),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $tenantId, int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE services SET name = :name, description = :desc, duration_minutes = :dur, price = :price,
             category = :cat, is_active = :active, display_order = :ord, updated_at = NOW()
             WHERE tenant_id = :t AND id = :id'
        );
        $stmt->execute([
            'name' => $data['name'],
            'desc' => $data['description'],
            'dur' => (int) $data['duration_minutes'],
            'price' => $data['price'],
            'cat' => $data['category'],
            'active' => (int) (bool) $data['is_active'],
            'ord' => (int) $data['display_order'],
            't' => $tenantId,
            'id' => $id,
        ]);
    }

    public function delete(int $tenantId, int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM services WHERE tenant_id = :t AND id = :id');
        $stmt->execute(['t' => $tenantId, 'id' => $id]);
    }

    /** @param list<int> $orderedIds */
    public function reorder(int $tenantId, array $orderedIds): void
    {
        $ord = 0;
        foreach ($orderedIds as $sid) {
            $stmt = $this->pdo->prepare('UPDATE services SET display_order = :o, updated_at = NOW() WHERE tenant_id = :t AND id = :id');
            $stmt->execute(['o' => $ord++, 't' => $tenantId, 'id' => (int) $sid]);
        }
    }
}
