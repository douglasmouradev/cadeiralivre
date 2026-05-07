<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PasswordResetModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function upsert(string $email, string $tokenHash): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (email, token, created_at) VALUES (:e, :t, NOW())
             ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = VALUES(created_at)'
        );
        $stmt->execute(['e' => mb_strtolower($email), 't' => $tokenHash]);
    }

    /** @return array<string, mixed>|null */
    public function findByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM password_resets WHERE token = :t LIMIT 1');
        $stmt->execute(['t' => $tokenHash]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function delete(string $email): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM password_resets WHERE email = :e');
        $stmt->execute(['e' => mb_strtolower($email)]);
    }
}
