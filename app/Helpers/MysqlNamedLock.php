<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;
use Throwable;

/** GET_LOCK / RELEASE_LOCK na mesma ligação PDO (MySQL). */
final class MysqlNamedLock
{
    public static function normalizeName(string $name): string
    {
        return substr($name, 0, 64);
    }

    public static function acquire(PDO $pdo, string $name, int $timeoutSeconds = 10): bool
    {
        $k = self::normalizeName($name);
        $st = $pdo->prepare('SELECT GET_LOCK(:k, :t) AS got');
        $st->execute(['k' => $k, 't' => max(1, $timeoutSeconds)]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['got'] ?? 0) === 1;
    }

    public static function release(PDO $pdo, string $name): void
    {
        $k = self::normalizeName($name);
        try {
            $st = $pdo->prepare('SELECT RELEASE_LOCK(:k)');
            $st->execute(['k' => $k]);
        } catch (Throwable) {
        }
    }
}
