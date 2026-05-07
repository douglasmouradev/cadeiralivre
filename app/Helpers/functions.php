<?php

declare(strict_types=1);

if (!function_exists('e')) {
    /**
     * Escape HTML (output seguro nas views).
     */
    function e(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('app_name')) {
    /**
     * Nome do produto (APP_NAME no .env).
     */
    function app_name(): string
    {
        $n = trim((string) ($_ENV['APP_NAME'] ?? ''));

        return $n !== '' ? $n : 'CadeiraLivre';
    }
}

if (!function_exists('tenant_brand_hex')) {
    /**
     * Cor hex da marca do tenant (para --tenant-accent no portal).
     */
    function tenant_brand_hex(?string $color): string
    {
        if ($color !== null && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1) {
            return $color;
        }

        return '#7c5e3c';
    }
}

if (!function_exists('format_datetime_in_tenant_tz')) {
    /**
     * Formata data/hora guardada como relógio local da barbearia (Y-m-d H:i:s).
     */
    function format_datetime_in_tenant_tz(?string $ymdHis, string $timezone): string
    {
        if ($ymdHis === null) {
            return '';
        }
        $ymdHis = trim($ymdHis);
        if ($ymdHis === '') {
            return '';
        }
        try {
            $tz = new \DateTimeZone($timezone);
        } catch (\Throwable) {
            $tz = new \DateTimeZone('America/Sao_Paulo');
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ymdHis, $tz);
        if ($dt === false) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $ymdHis, $tz);
        }
        if ($dt === false) {
            return $ymdHis;
        }

        return $dt->format('d/m/Y H:i');
    }
}
