<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $appointment */
/** @var string $slug */
/** @var array<string, mixed>|null $tenant */

$brandHex = tenant_brand_hex(is_array($tenant ?? null) ? (string) ($tenant['primary_color'] ?? '') : null);
$ap = is_array($appointment) ? $appointment : null;
$tzId = is_array($tenant ?? null) ? (string) ($tenant['timezone'] ?? 'America/Sao_Paulo') : 'America/Sao_Paulo';
$dtLabel = '';
$startRaw = '';
$endRaw = '';
if ($ap !== null) {
    $startRaw = (string) ($ap['start_datetime'] ?? '');
    $endRaw = (string) ($ap['end_datetime'] ?? '');
    $dtLabel = format_datetime_in_tenant_tz($startRaw, $tzId);
}
$storeName = is_array($tenant) ? (string) ($tenant['name'] ?? '') : '';
$location = is_array($tenant) ? trim((string) ($tenant['address'] ?? '') . ', ' . (string) ($tenant['city'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <?php if (is_array($tenant) && $slug !== ''): require __DIR__ . '/../partials/public_tenant_head.php'; endif; ?>
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/booking.css')) ?>">
</head>
<body class="public-body public-theme booking-premium" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-centered booking-premium__main">
    <div class="auth-card auth-card--success">
        <div class="success-icon" aria-hidden="true">✓</div>
        <h1>Agendamento confirmado</h1>
        <?php if ($ap !== null): ?>
            <p class="muted">Seu horário na <strong><?= e($storeName) ?></strong> foi registrado com sucesso.</p>
            <dl class="thanks-summary">
                <dt>Data e hora</dt>
                <dd><?= e($dtLabel) ?></dd>
                <dt>Serviço</dt>
                <dd><?= e((string) ($ap['service_name'] ?? '—')) ?></dd>
                <dt>Profissional</dt>
                <dd><?= e((string) ($ap['barber_name'] ?? '—')) ?></dd>
                <dt>Status</dt>
                <dd><span class="pill"><?= e(appointment_status_label((string) ($ap['status'] ?? ''))) ?></span></dd>
                <?php
                $notes = trim((string) ($ap['notes'] ?? ''));
                if ($notes !== ''): ?>
                    <dt>Observações</dt>
                    <dd class="thanks-summary__notes"><?= e($notes) ?></dd>
                <?php endif; ?>
            </dl>
            <?php if ($startRaw !== ''): ?>
            <?php if ($startRaw !== '' && is_array($tenant) && !empty($tenant['phone'])):
                $waMsg = 'Olá! Confirmo meu agendamento em ' . $storeName . ' em ' . $dtLabel . '.';
                $waUrl = whatsapp_link((string) $tenant['phone'], $waMsg);
                if ($waUrl !== ''): ?>
                <a class="btn secondary" href="<?= e($waUrl) ?>" target="_blank" rel="noopener">Confirmar no WhatsApp</a>
            <?php endif; endif; ?>
            <div class="form-actions thanks-actions">
                <button type="button" class="btn secondary" id="btn-add-calendar">Adicionar ao calendário</button>
                <a class="btn" href="/agendar/<?= e($slug) ?>/meus-agendamentos">Ver meus agendamentos</a>
            </div>
            <script>
            document.getElementById('btn-add-calendar')?.addEventListener('click', () => {
              const start = <?= json_encode($startRaw, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
              const end = <?= json_encode($endRaw !== '' ? $endRaw : $startRaw, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
              const title = <?= json_encode('Atendimento — ' . $storeName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
              const loc = <?= json_encode($location, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
              const fmt = (s) => s.replace(/[-: ]/g, '').slice(0, 15);
              const ics = [
                'BEGIN:VCALENDAR', 'VERSION:2.0', 'BEGIN:VEVENT',
                'DTSTART:' + fmt(start), 'DTEND:' + fmt(end),
                'SUMMARY:' + title, 'LOCATION:' + loc,
                'END:VEVENT', 'END:VCALENDAR'
              ].join('\r\n');
              const blob = new Blob([ics], { type: 'text/calendar;charset=utf-8' });
              const a = document.createElement('a');
              a.href = URL.createObjectURL(blob);
              a.download = 'agendamento.ics';
              a.click();
            });
            </script>
            <?php endif; ?>
        <?php else: ?>
            <p>Agendamento recebido.</p>
        <?php endif; ?>
        <p class="muted mt-1"><a href="/agendar/<?= e($slug) ?>">Fazer outro agendamento</a></p>
    </div>
    <?php require __DIR__ . '/../partials/public_platform_footer.php'; ?>
</main>
</body>
</html>
