<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $appointment */
/** @var string $slug */
/** @var array<string, mixed>|null $tenant */

$brandHex = tenant_brand_hex(is_array($tenant ?? null) ? (string) ($tenant['primary_color'] ?? '') : null);
$ap = is_array($appointment) ? $appointment : null;
$tzId = is_array($tenant ?? null) ? (string) ($tenant['timezone'] ?? 'America/Sao_Paulo') : 'America/Sao_Paulo';
$dtLabel = '';
if ($ap !== null) {
    $dtLabel = format_datetime_in_tenant_tz((string) ($ap['start_datetime'] ?? ''), $tzId);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body public-theme" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-centered">
    <div class="auth-card">
        <h1>Obrigado!</h1>
        <?php if ($ap !== null): ?>
            <p>Seu agendamento foi registrado. Confira seu e-mail com o código de confirmação.</p>
            <dl class="thanks-summary muted" style="margin-top:1rem;font-size:0.95rem">
                <dt style="font-weight:600;color:var(--ink,#1c1917);margin-top:0.5rem">Data e hora</dt>
                <dd style="margin:0.15rem 0 0 0"><?= e($dtLabel) ?><?php if ($dtLabel !== ''): ?><span class="muted" style="display:block;font-size:0.85rem;margin-top:0.25rem">Fuso da barbearia: <?= e($tzId) ?>.</span><?php endif; ?></dd>
                <dt style="font-weight:600;color:var(--ink,#1c1917);margin-top:0.5rem">Serviço</dt>
                <dd style="margin:0.15rem 0 0 0"><?= e((string) ($ap['service_name'] ?? '—')) ?></dd>
                <dt style="font-weight:600;color:var(--ink,#1c1917);margin-top:0.5rem">Profissional</dt>
                <dd style="margin:0.15rem 0 0 0"><?= e((string) ($ap['barber_name'] ?? '—')) ?></dd>
                <dt style="font-weight:600;color:var(--ink,#1c1917);margin-top:0.5rem">Status</dt>
                <dd style="margin:0.15rem 0 0 0"><?= e((string) ($ap['status'] ?? '')) ?></dd>
                <?php
                $notes = trim((string) ($ap['notes'] ?? ''));
                if ($notes !== ''): ?>
                    <dt style="font-weight:600;color:var(--ink,#1c1917);margin-top:0.5rem">Observações</dt>
                    <dd style="margin:0.15rem 0 0 0;white-space:pre-wrap"><?= e($notes) ?></dd>
                <?php endif; ?>
            </dl>
            <div class="form-actions mt-1">
                <a class="btn" href="/agendar/<?= e($slug) ?>/confirmar?token=<?= e((string) $ap['public_token']) ?>">Confirmar com código</a>
            </div>
        <?php else: ?>
            <p>Agendamento recebido.</p>
        <?php endif; ?>
        <p class="muted mt-1"><a href="/agendar/<?= e($slug) ?>">Fazer outro agendamento</a></p>
    </div>
</main>
</body>
</html>
