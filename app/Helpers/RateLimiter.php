<?php

declare(strict_types=1);

namespace App\Helpers;

use JsonException;

final class RateLimiter
{
    /**
     * Regista um acesso e devolve true se ainda estiver dentro do limite.
     *
     * @param non-empty-string $key
     */
    public static function allow(string $key, int $maxHits, int $windowSeconds): bool
    {
        if ($maxHits < 1 || $windowSeconds < 1) {
            return true;
        }
        $dir = sys_get_temp_dir() . '/cadeira_livre_saas_rl';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
            return true;
        }
        $safe = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $key);
        $path = $dir . '/' . substr(sha1($key), 0, 32) . '_' . substr($safe, 0, 48) . '.rl';
        $fh = fopen($path, 'c+');
        if ($fh === false) {
            return true;
        }
        try {
            if (!flock($fh, LOCK_EX)) {
                return true;
            }
            $now = time();
            $raw = stream_get_contents($fh);
            $hits = [];
            if (is_string($raw) && $raw !== '') {
                try {
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        foreach ($decoded as $t) {
                            if (is_int($t) || (is_string($t) && ctype_digit($t))) {
                                $hits[] = (int) $t;
                            }
                        }
                    }
                } catch (JsonException) {
                    $hits = [];
                }
            }
            $cutoff = $now - $windowSeconds;
            $hits = array_values(array_filter($hits, static fn (int $t): bool => $t >= $cutoff));
            if (count($hits) >= $maxHits) {
                return false;
            }
            $hits[] = $now;
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($hits, JSON_THROW_ON_ERROR));
            fflush($fh);

            return true;
        } catch (JsonException) {
            return true;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}
