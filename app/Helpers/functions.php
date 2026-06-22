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

if (!function_exists('tenant_logo_url')) {
    /**
     * URL pública da logo do tenant (estático em public/ ou rota PHP).
     */
    function tenant_logo_url(string $slug): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug) ?? '';
        if ($safe === '') {
            return '/loja-logo/' . rawurlencode($slug);
        }
        $root = dirname(__DIR__, 2);
        $static = $root . '/public/assets/tenant-logos/' . $safe . '.png';
        if (is_file($static)) {
            return '/assets/tenant-logos/' . rawurlencode($safe) . '.png';
        }

        return '/loja-logo/' . rawurlencode($slug);
    }
}

if (!function_exists('tenant_logo_publish')) {
    /**
     * Copia a logo para storage/uploads e public/assets/tenant-logos/{slug}.png.
     *
     * @return non-empty-string Caminho relativo em storage (ex.: logos/arquivo.png)
     */
    function tenant_logo_publish(string $projectRoot, string $slug, string $sourceAbsolutePath, ?string $storageBasename = null): string
    {
        if (!is_readable($sourceAbsolutePath)) {
            throw new \RuntimeException('Arquivo de logo ilegível: ' . $sourceAbsolutePath);
        }
        $safeSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug) ?? '';
        if ($safeSlug === '') {
            throw new \InvalidArgumentException('Slug inválido para logo.');
        }
        $basename = $storageBasename ?? ($safeSlug . '.png');
        $basename = basename(str_replace(['..', '\\'], '', $basename));

        $storageDir = $projectRoot . '/storage/uploads/logos';
        if (!is_dir($storageDir) && !mkdir($storageDir, 0770, true) && !is_dir($storageDir)) {
            throw new \RuntimeException('Não foi possível criar ' . $storageDir);
        }
        $storagePath = $storageDir . '/' . $basename;
        if (!copy($sourceAbsolutePath, $storagePath)) {
            throw new \RuntimeException('Não foi possível copiar logo para storage.');
        }

        $publicDir = $projectRoot . '/public/assets/tenant-logos';
        if (!is_dir($publicDir) && !mkdir($publicDir, 0755, true) && !is_dir($publicDir)) {
            throw new \RuntimeException('Não foi possível criar ' . $publicDir);
        }
        $publicPath = $publicDir . '/' . $safeSlug . '.png';
        if (!copy($sourceAbsolutePath, $publicPath)) {
            throw new \RuntimeException('Não foi possível publicar logo estática.');
        }

        return 'logos/' . $basename;
    }
}

if (!function_exists('tenant_cover_url')) {
    function tenant_cover_url(string $slug): ?string
    {
        $safe = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug) ?? '';
        if ($safe === '') {
            return null;
        }
        $root = dirname(__DIR__, 2);
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $static = $root . '/public/assets/tenant-covers/' . $safe . '.' . $ext;
            if (is_file($static)) {
                return '/assets/tenant-covers/' . rawurlencode($safe) . '.' . $ext;
            }
        }

        return '/loja-capa/' . rawurlencode($slug);
    }
}

if (!function_exists('tenant_cover_publish')) {
    function tenant_cover_publish(string $projectRoot, string $slug, string $sourceAbsolutePath): string
    {
        if (!is_readable($sourceAbsolutePath)) {
            throw new \RuntimeException('Arquivo de capa ilegível.');
        }
        $safeSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug) ?? '';
        if ($safeSlug === '') {
            throw new \InvalidArgumentException('Slug inválido.');
        }
        $ext = strtolower(pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }
        $basename = $safeSlug . '-cover.' . $ext;
        $storageDir = $projectRoot . '/storage/uploads/covers';
        if (!is_dir($storageDir) && !mkdir($storageDir, 0770, true) && !is_dir($storageDir)) {
            throw new \RuntimeException('Não foi possível criar diretório de capas.');
        }
        $storagePath = $storageDir . '/' . $basename;
        if (!copy($sourceAbsolutePath, $storagePath)) {
            throw new \RuntimeException('Não foi possível copiar capa.');
        }
        $publicDir = $projectRoot . '/public/assets/tenant-covers';
        if (!is_dir($publicDir) && !mkdir($publicDir, 0755, true) && !is_dir($publicDir)) {
            throw new \RuntimeException('Não foi possível criar diretório público de capas.');
        }
        $publicPath = $publicDir . '/' . $safeSlug . '.' . $ext;
        if (!copy($sourceAbsolutePath, $publicPath)) {
            throw new \RuntimeException('Não foi possível publicar capa.');
        }

        return 'covers/' . $basename;
    }
}

if (!function_exists('user_avatar_url')) {
    function user_avatar_url(?string $avatarPath): ?string
    {
        if ($avatarPath === null || $avatarPath === '') {
            return null;
        }
        $safe = str_replace(['..', '\\'], '', $avatarPath);

        return '/media/avatar?f=' . rawurlencode($safe);
    }
}

if (!function_exists('barber_display_avatar_url')) {
    /**
     * Avatar do profissional no agendamento: foto do usuário ou logo da loja.
     */
    function barber_display_avatar_url(?string $userAvatar, string $tenantSlug, bool $tenantHasLogo): ?string
    {
        $avatar = user_avatar_url($userAvatar);
        if ($avatar !== null) {
            return $avatar;
        }
        if ($tenantHasLogo) {
            return tenant_logo_url($tenantSlug);
        }

        return null;
    }
}

if (!function_exists('asset_version')) {
    /** URL de asset estático com cache bust (?v=filemtime). */
    function asset_version(string $publicPath): string
    {
        $root = dirname(__DIR__, 2);
        $file = $root . '/public' . $publicPath;
        $v = is_file($file) ? (string) filemtime($file) : '1';

        return $publicPath . '?v=' . rawurlencode($v);
    }
}

if (!function_exists('barber_specialties_list')) {
    /**
     * @return list<string>
     */
    function barber_specialties_list(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === '[]') {
            return [];
        }
        $items = null;
        if (is_array($raw)) {
            $items = $raw;
        } elseif (is_string($raw)) {
            $candidates = [$raw, stripslashes($raw)];
            foreach ($candidates as $candidate) {
                $decoded = json_decode($candidate, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
                if (is_array($decoded)) {
                    $items = $decoded;
                    break;
                }
            }
            if ($items === null && preg_match('/^\s*\[/', $raw) === 1) {
                if (preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/u', $raw, $matches) > 0) {
                    $items = [];
                    foreach ($matches[1] as $piece) {
                        $label = json_decode('"' . $piece . '"', true);
                        $items[] = is_string($label) ? $label : $piece;
                    }
                }
            }
        }
        if ($items === null) {
            return [];
        }
        $out = [];
        foreach ($items as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $out[] = $text;
            }
        }

        return $out;
    }
}

if (!function_exists('barber_specialties_text')) {
    /**
     * Especialidades do profissional (JSON ou array) em texto legível.
     */
    function barber_specialties_text(mixed $raw): string
    {
        $items = barber_specialties_list($raw);

        return $items === [] ? '' : implode(' · ', $items);
    }
}

if (!function_exists('format_money_br')) {
    function format_money_br(float|string $value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('appointment_status_label')) {
    function appointment_status_label(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendente',
            'confirmed' => 'Confirmado',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            'no_show' => 'Não compareceu',
            default => $status,
        };
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        $base = trim((string) ($_ENV['APP_URL'] ?? ''));

        return rtrim($base !== '' ? $base : '', '/');
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

if (!function_exists('subscription_status_label')) {
    function subscription_status_label(string $status): string
    {
        return match ($status) {
            'none' => 'Sem assinatura',
            'trialing' => 'Em trial',
            'active' => 'Ativa',
            'past_due' => 'Pagamento pendente',
            'canceled' => 'Cancelada',
            default => $status,
        };
    }
}

if (!function_exists('format_money_cents')) {
    function format_money_cents(int $cents): string
    {
        return 'R$ ' . number_format($cents / 100, 2, ',', '.');
    }
}

if (!function_exists('stripe_customer_url')) {
    function stripe_customer_url(?string $customerId): ?string
    {
        if ($customerId === null || $customerId === '') {
            return null;
        }

        return 'https://dashboard.stripe.com/customers/' . rawurlencode($customerId);
    }
}

if (!function_exists('stripe_subscription_url')) {
    function stripe_subscription_url(?string $subscriptionId): ?string
    {
        if ($subscriptionId === null || $subscriptionId === '') {
            return null;
        }

        return 'https://dashboard.stripe.com/subscriptions/' . rawurlencode($subscriptionId);
    }
}

if (!function_exists('saas_impersonating')) {
    function saas_impersonating(): bool
    {
        return !empty($_SESSION['saas_impersonating']) && ($_SESSION['tenant_id'] ?? null) !== null;
    }
}

if (!function_exists('saas_audit_action_label')) {
    function saas_audit_action_label(string $action): string
    {
        return match ($action) {
            'tenant_create' => 'Loja criada',
            'tenant_suspend' => 'Loja suspensa',
            'tenant_activate' => 'Loja reativada',
            'tenant_plan_update' => 'Plano da loja atualizado',
            'plan_update' => 'Plano da plataforma editado',
            'impersonate_start' => 'Acesso ao painel da loja',
            'impersonate_stop' => 'Saída do painel da loja',
            default => $action,
        };
    }
}
