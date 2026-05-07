<?php

declare(strict_types=1);

namespace App\Helpers;

final class Str
{
    public static function slug(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $text));
        $text = trim($text, '-');

        return $text !== '' ? $text : 'tenant';
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function confirmationCode(): string
    {
        return str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
    }
}
