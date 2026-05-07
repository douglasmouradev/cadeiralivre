<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class TenantModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE slug = :s LIMIT 1');
        $stmt->execute(['s' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @param array{name: string, slug: string, email: string, phone: ?string, address: ?string, city: ?string, state: ?string, timezone: string} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tenants (name, slug, email, phone, address, city, state, timezone, status, trial_ends_at, plan, created_at, updated_at)
             VALUES (:name, :slug, :email, :phone, :address, :city, :state, :timezone, \'trial\', DATE_ADD(NOW(), INTERVAL 14 DAY), \'free\', NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'timezone' => $data['timezone'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $fields */
    public function update(int $tenantId, array $fields): void
    {
        $allowed = ['name', 'email', 'phone', 'address', 'city', 'state', 'logo_path', 'primary_color', 'timezone'];
        $sets = [];
        $params = ['tid' => $tenantId];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $sets[] = $col . ' = :' . $col;
                $params[$col] = $fields[$col];
            }
        }
        if ($sets === []) {
            return;
        }
        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE tenants SET ' . implode(', ', $sets) . ' WHERE id = :tid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
}
