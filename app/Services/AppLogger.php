<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

final class AppLogger
{
    private static function logPath(string $root): string
    {
        $dir = $root . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }

        return $dir . '/app.log';
    }

    /** @param array<string, mixed> $context */
    public static function info(string $root, string $message, array $context = []): void
    {
        self::write($root, 'INFO', $message, $context);
    }

    public static function exception(string $root, Throwable $e): void
    {
        self::write($root, 'ERROR', $e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    /** @param array<string, mixed> $context */
    private static function write(string $root, string $level, string $message, array $context): void
    {
        $line = json_encode(
            [
                'ts' => date('c'),
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (!is_string($line)) {
            return;
        }
        @file_put_contents(self::logPath($root), $line . "\n", FILE_APPEND | LOCK_EX);
    }
}
