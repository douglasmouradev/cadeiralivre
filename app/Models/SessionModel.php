<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SessionModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function create(int $userId, string $tokenHash, ?string $ip, ?string $ua, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at, created_at)
             VALUES (:uid, :tok, :ip, :ua, :exp, NOW())'
        );
        $stmt->execute([
            'uid' => $userId,
            'tok' => $tokenHash,
            'ip' => $ip,
            'ua' => $ua !== null ? mb_substr($ua, 0, 512) : null,
            'exp' => $expiresAt,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, u.id AS user_id_join FROM sessions s
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.token = :t AND s.expires_at > NOW() AND u.is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['t' => $tokenHash]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function deleteByTokenHash(string $tokenHash): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE token = :t');
        $stmt->execute(['t' => $tokenHash]);
    }

    public function deleteExpired(): void
    {
        $this->pdo->exec('DELETE FROM sessions WHERE expires_at < NOW()');
    }

    public function deleteAllForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE user_id = :u');
        $stmt->execute(['u' => $userId]);
    }
}
