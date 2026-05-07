<?php

declare(strict_types=1);

namespace App\Helpers;

final class Flash
{
    private const KEY = '_flash';

    /** @param array<string, mixed>|string $data */
    public static function set(string $type, array|string $data): void
    {
        $_SESSION[self::KEY][$type] = $data;
    }

    /** @return array<string, mixed>|string|null */
    public static function get(string $type): mixed
    {
        if (!isset($_SESSION[self::KEY][$type])) {
            return null;
        }
        $v = $_SESSION[self::KEY][$type];
        unset($_SESSION[self::KEY][$type]);

        return $v;
    }
}
