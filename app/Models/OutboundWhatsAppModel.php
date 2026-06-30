<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class OutboundWhatsAppModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function enqueue(string $phone, string $message): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO outbound_whatsapp (phone, message, attempts, available_at, created_at)
             VALUES (:p, :m, 0, NOW(), NOW())'
        );
        $stmt->execute(['p' => $phone, 'm' => $message]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function fetchPending(int $limit = 25): array
    {
        $lim = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM outbound_whatsapp
             WHERE sent_at IS NULL AND attempts < 5 AND available_at <= NOW()
             ORDER BY id ASC
             LIMIT {$lim}"
        );
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function markSent(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE outbound_whatsapp SET sent_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function markRetry(int $id, string $error): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE outbound_whatsapp SET attempts = attempts + 1, last_error = :err, available_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'err' => mb_substr($error, 0, 500)]);
    }

    public function countPending(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) FROM outbound_whatsapp WHERE sent_at IS NULL AND attempts < 5'
        );

        return (int) ($stmt !== false ? $stmt->fetchColumn() : 0);
    }
}
