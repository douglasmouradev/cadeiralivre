<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class BarberModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return list<array<string, mixed>> */
    public function listWithUser(int $tenantId): array
    {
        $sql = 'SELECT b.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
                FROM barbers b
                INNER JOIN users u ON u.id = b.user_id
                WHERE b.tenant_id = :t
                ORDER BY u.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['t' => $tenantId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function find(int $tenantId, int $barberId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.name AS user_name, u.email AS user_email
             FROM barbers b INNER JOIN users u ON u.id = b.user_id
             WHERE b.tenant_id = :t AND b.id = :id LIMIT 1'
        );
        $stmt->execute(['t' => $tenantId, 'id' => $barberId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByUserId(int $tenantId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.* FROM barbers b WHERE b.tenant_id = :t AND b.user_id = :u LIMIT 1'
        );
        $stmt->execute(['t' => $tenantId, 'u' => $userId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(int $tenantId, int $userId, array $data): int
    {
        $spec = $data['specialties'] ?? [];
        $json = is_array($spec) ? json_encode($spec, JSON_THROW_ON_ERROR) : '[]';
        $stmt = $this->pdo->prepare(
            'INSERT INTO barbers (user_id, tenant_id, bio, specialties, commission_percent, is_available, created_at, updated_at)
             VALUES (:uid, :t, :bio, :spec, :com, :av, NOW(), NOW())'
        );
        $stmt->execute([
            'uid' => $userId,
            't' => $tenantId,
            'bio' => $data['bio'] ?? null,
            'spec' => $json,
            'com' => $data['commission_percent'],
            'av' => (int) (bool) ($data['is_available'] ?? true),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $tenantId, int $barberId, array $data): void
    {
        $spec = $data['specialties'] ?? [];
        $json = is_array($spec) ? json_encode($spec, JSON_THROW_ON_ERROR) : '[]';
        $stmt = $this->pdo->prepare(
            'UPDATE barbers SET bio = :bio, specialties = :spec, commission_percent = :com, is_available = :av, updated_at = NOW()
             WHERE tenant_id = :t AND id = :id'
        );
        $stmt->execute([
            'bio' => $data['bio'] ?? null,
            'spec' => $json,
            'com' => $data['commission_percent'],
            'av' => (int) (bool) ($data['is_available'] ?? true),
            't' => $tenantId,
            'id' => $barberId,
        ]);
    }

    public function setAvailability(int $tenantId, int $barberId, bool $on): void
    {
        $stmt = $this->pdo->prepare('UPDATE barbers SET is_available = :a, updated_at = NOW() WHERE tenant_id = :t AND id = :id');
        $stmt->execute(['a' => (int) $on, 't' => $tenantId, 'id' => $barberId]);
    }

    public function deactivate(int $tenantId, int $barberId): void
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM barbers WHERE tenant_id = :t AND id = :id');
        $stmt->execute(['t' => $tenantId, 'id' => $barberId]);
        $row = $stmt->fetch();
        if ($row === false) {
            return;
        }
        $userId = (int) $row['user_id'];
        $this->pdo->prepare('UPDATE barbers SET is_available = 0, updated_at = NOW() WHERE id = :id')->execute(['id' => $barberId]);
        $this->pdo->prepare('UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = :u AND tenant_id = :t')->execute(['u' => $userId, 't' => $tenantId]);
    }

    public function syncServices(int $tenantId, int $barberId, array $serviceIds, array $customPrices): void
    {
        $this->pdo->prepare('DELETE FROM barber_services WHERE barber_id = :b')->execute(['b' => $barberId]);
        $ins = $this->pdo->prepare(
            'INSERT INTO barber_services (barber_id, service_id, custom_price) VALUES (:b, :s, :cp)'
        );
        foreach ($serviceIds as $sid) {
            $sid = (int) $sid;
            $cp = $customPrices[$sid] ?? null;
            $ins->execute(['b' => $barberId, 's' => $sid, 'cp' => $cp === '' || $cp === null ? null : $cp]);
        }
    }

    /** @return list<int> */
    public function serviceIdsForBarber(int $barberId): array
    {
        $stmt = $this->pdo->prepare('SELECT service_id FROM barber_services WHERE barber_id = :b');
        $stmt->execute(['b' => $barberId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map(intval(...), $rows ?: []);
    }

    /** @return list<array<string, mixed>> */
    public function barbersForService(int $tenantId, int $serviceId): array
    {
        $sql = 'SELECT b.*, u.name AS user_name, u.avatar_path AS user_avatar
                FROM barbers b
                INNER JOIN users u ON u.id = b.user_id
                INNER JOIN barber_services bs ON bs.barber_id = b.id
                WHERE b.tenant_id = :t AND bs.service_id = :s AND b.is_available = 1 AND u.is_active = 1
                ORDER BY u.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['t' => $tenantId, 's' => $serviceId]);

        return $this->hydrateBarberRows($stmt->fetchAll() ?: []);
    }

    /** @return list<array<string, mixed>> */
    public function availableBarbersForTenant(int $tenantId): array
    {
        $sql = 'SELECT b.*, u.name AS user_name, u.avatar_path AS user_avatar FROM barbers b
                INNER JOIN users u ON u.id = b.user_id
                WHERE b.tenant_id = :t AND b.is_available = 1 AND u.is_active = 1
                ORDER BY u.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['t' => $tenantId]);

        return $this->hydrateBarberRows($stmt->fetchAll() ?: []);
    }

    /** @param array<string, mixed> $row */
    private function hydrateBarberRow(array $row): array
    {
        if (array_key_exists('specialties', $row) && is_string($row['specialties'])) {
            $decoded = json_decode($row['specialties'], true);
            if (is_array($decoded)) {
                $row['specialties'] = $decoded;
            }
        }

        return $row;
    }

    /** @param list<array<string, mixed>> $rows */
    private function hydrateBarberRows(array $rows): array
    {
        return array_map(fn (array $row): array => $this->hydrateBarberRow($row), $rows);
    }

    public function countForTenant(int $tenantId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM barbers WHERE tenant_id = :t');
        $stmt->execute(['t' => $tenantId]);

        return (int) $stmt->fetchColumn();
    }
}
