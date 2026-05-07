<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, tenant_id, name, email, password_hash, role, phone, avatar_path, is_active, email_verified_at, created_at, updated_at
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, tenant_id, name, email, password_hash, role, phone, avatar_path, is_active, email_verified_at, created_at, updated_at
             FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => mb_strtolower($email)]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function listByTenant(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, tenant_id, name, email, role, phone, avatar_path, is_active, created_at
             FROM users WHERE tenant_id = :tid ORDER BY name ASC'
        );
        $stmt->execute(['tid' => $tenantId]);

        return $stmt->fetchAll() ?: [];
    }

    /** Donos e recepcionistas do tenant (não inclui barbeiros). */
    /** @return list<array<string, mixed>> */
    public function listAdministrativeByTenant(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, tenant_id, name, email, role, phone, is_active, created_at
             FROM users
             WHERE tenant_id = :tid AND role IN ('owner', 'receptionist')
             ORDER BY FIELD(role, 'owner', 'receptionist'), name ASC"
        );
        $stmt->execute(['tid' => $tenantId]);

        return $stmt->fetchAll() ?: [];
    }

    public function create(
        ?int $tenantId,
        string $name,
        string $email,
        string $passwordHash,
        string $role,
        ?string $phone = null,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (tenant_id, name, email, password_hash, role, phone, is_active, created_at, updated_at)
             VALUES (:tenant_id, :name, :email, :password_hash, :role, :phone, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => mb_strtolower($email),
            'password_hash' => $passwordHash,
            'role' => $role,
            'phone' => $phone,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :ph, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['ph' => $passwordHash, 'id' => $userId]);
    }

    public function setRememberToken(int $userId, ?string $token): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET remember_token = :t, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['t' => $token, 'id' => $userId]);
    }

    /** @return array<string, mixed>|null */
    public function findByRememberToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, tenant_id, name, email, password_hash, role, phone, avatar_path, is_active, remember_token
             FROM users WHERE remember_token = :t LIMIT 1'
        );
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function updateProfile(int $userId, string $name, ?string $phone): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET name = :n, phone = :p, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['n' => $name, 'p' => $phone, 'id' => $userId]);
    }

    public function setAvatar(int $userId, ?string $path): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET avatar_path = :a, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['a' => $path, 'id' => $userId]);
    }
}
