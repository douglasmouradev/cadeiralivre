<?php

declare(strict_types=1);

namespace App\Helpers;

/** Registra última execução de scripts cron para monitoramento. */
final class CronHeartbeat
{
    public static function touch(string $job): void
    {
        $root = dirname(__DIR__, 2);
        $dir = $root . '/storage/logs';
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            return;
        }
        $file = $dir . '/cron-heartbeat.json';
        $data = [];
        if (is_readable($file)) {
            $raw = file_get_contents($file);
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }
        $data[$job] = date('c');
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /** @return array<string, string> */
    public static function readAll(): array
    {
        $file = dirname(__DIR__, 2) . '/storage/logs/cron-heartbeat.json';
        if (!is_readable($file)) {
            return [];
        }
        $raw = file_get_contents($file);
        if (!is_string($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function isRecent(string $job, int $maxAgeMinutes): bool
    {
        $all = self::readAll();
        $ts = $all[$job] ?? '';
        if ($ts === '') {
            return false;
        }
        $t = strtotime($ts);

        return $t !== false && (time() - $t) <= $maxAgeMinutes * 60;
    }
}
