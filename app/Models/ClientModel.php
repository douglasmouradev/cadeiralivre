<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ClientModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return array{rows: list<array<string, mixed>>, total: int} */
    public function paginate(int $tenantId, int $page, int $perPage, ?string $search): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = 'tenant_id = :t';
        $params = ['t' => $tenantId];
        if ($search !== null && $search !== '') {
            $where .= ' AND (name LIKE :q1 OR email LIKE :q2 OR phone LIKE :q3)';
            $sq = '%' . $search . '%';
            $params['q1'] = $sq;
            $params['q2'] = $sq;
            $params['q3'] = $sq;
        }
        $cstmt = $this->pdo->prepare("SELECT COUNT(*) FROM clients WHERE $where");
        $cstmt->execute($params);
        $total = (int) $cstmt->fetchColumn();

        $sql = "SELECT * FROM clients WHERE $where ORDER BY created_at DESC LIMIT :lim OFFSET :off";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        return ['rows' => $rows, 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function findByTenantEmail(int $tenantId, string $email): ?array
    {
        $e = mb_strtolower(trim($email));
        if ($e === '') {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE tenant_id = :t AND email = :e LIMIT 1');
        $stmt->execute(['t' => $tenantId, 'e' => $e]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function createWithPortal(int $tenantId, string $name, string $email, string $phone, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO clients (tenant_id, name, email, phone, birth_date, notes, portal_password_hash, created_at, updated_at)
             VALUES (:t, :name, :email, :phone, NULL, NULL, :ph, NOW(), NOW())'
        );
        $stmt->execute([
            't' => $tenantId,
            'name' => $name,
            'email' => mb_strtolower(trim($email)),
            'phone' => $phone !== '' ? $phone : null,
            'ph' => $passwordHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function activatePortal(int $tenantId, int $clientId, string $name, string $phone, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE clients SET name = :name, phone = :phone, portal_password_hash = :ph, updated_at = NOW()
             WHERE tenant_id = :t AND id = :id AND portal_password_hash IS NULL'
        );
        $stmt->execute([
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'ph' => $passwordHash,
            't' => $tenantId,
            'id' => $clientId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function updatePhone(int $tenantId, int $clientId, string $phone): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE clients SET phone = :phone, updated_at = NOW() WHERE tenant_id = :t AND id = :id'
        );
        $stmt->execute([
            'phone' => $phone !== '' ? $phone : null,
            't' => $tenantId,
            'id' => $clientId,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $tenantId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE tenant_id = :t AND id = :id LIMIT 1');
        $stmt->execute(['t' => $tenantId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO clients (tenant_id, name, email, phone, birth_date, notes, created_at, updated_at)
             VALUES (:t, :name, :email, :phone, :bd, :notes, NOW(), NOW())'
        );
        $stmt->execute([
            't' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'] !== '' ? mb_strtolower((string) $data['email']) : null,
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'bd' => $data['birth_date'] !== '' ? $data['birth_date'] : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $tenantId, int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE clients SET name = :name, email = :email, phone = :phone, birth_date = :bd, notes = :notes, updated_at = NOW()
             WHERE tenant_id = :t AND id = :id'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'] !== '' ? mb_strtolower((string) $data['email']) : null,
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'bd' => $data['birth_date'] !== '' ? $data['birth_date'] : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
            't' => $tenantId,
            'id' => $id,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function searchQuick(int $tenantId, string $q, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clients WHERE tenant_id = :t AND (name LIKE :q1 OR email LIKE :q2 OR phone LIKE :q3)
             ORDER BY name ASC LIMIT :lim'
        );
        $sq = '%' . $q . '%';
        $stmt->bindValue(':t', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':q1', $sq);
        $stmt->bindValue(':q2', $sq);
        $stmt->bindValue(':q3', $sq);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }
}
