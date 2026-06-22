<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Helpers\Flash;

/** @var array<string, mixed> $tenant */
/** @var string $slug */
/** @var array<string, mixed> $portal_client */
/** @var list<array<string, mixed>> $appointments */
/** @var string $timezone */
/** @var string $csrf */

$brandHex = tenant_brand_hex(isset($tenant['primary_color']) ? (string) $tenant['primary_color'] : null);
$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
$tzId = $timezone;
$location = trim(implode(' · ', array_filter([
    trim((string) ($tenant['city'] ?? '')),
    trim((string) ($tenant['state'] ?? '')),
])));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <?php require __DIR__ . '/../partials/public_tenant_head.php'; ?>
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/booking.css')) ?>">
</head>
<body class="public-body public-theme booking-premium" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-page portal-page booking-premium__main">
    <header class="store-hero store-hero--compact">
        <div class="store-hero__overlay">
            <div class="store-hero__brand">
                <?php if (!empty($tenant['logo_path'])): ?>
                    <img class="store-hero__logo" src="<?= e(tenant_logo_url($slug)) ?>" alt="">
                <?php endif; ?>
                <div>
                    <h1 class="store-hero__title"><?= e((string) $tenant['name']) ?></h1>
                    <?php if ($location !== ''): ?>
                        <p class="store-hero__meta"><?= e($location) ?></p>
                    <?php endif; ?>
                    <p class="store-hero__user muted">
                        Olá, <strong><?= e((string) $portal_client['name']) ?></strong> —
                        <a href="/agendar/<?= e($slug) ?>">Agendar horário</a>
                        ·
                        <a href="/agendar/<?= e($slug) ?>/meus-agendamentos" aria-current="page">Meus agendamentos</a>
                        ·
                        <a href="/cliente/<?= e($slug) ?>/sair">Sair</a>
                    </p>
                </div>
            </div>
        </div>
    </header>

    <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
        <div class="alert alert-success" role="status"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (is_string($flashError) && $flashError !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($flashError) ?></div>
    <?php endif; ?>

    <div class="card card--premium card--compact">
        <h2 class="portal-card__title">Meus agendamentos</h2>
        <p class="muted portal-card__lead">Histórico nesta loja (fuso: <?= e($tzId) ?>).</p>

        <?php if ($appointments === []): ?>
            <div class="portal-empty">
                <p class="muted" style="margin:0">Você ainda não tem agendamentos aqui.</p>
                <p style="margin:0.75rem 0 0"><a href="/agendar/<?= e($slug) ?>">Agendar agora</a></p>
            </div>
        <?php else: ?>
            <div class="my-appointments-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data e hora</th>
                            <th>Serviço</th>
                            <th>Profissional</th>
                            <th>Status</th>
                            <th class="cell-nowrap">Valor</th>
                            <th class="my-appointments-actions-th">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $ap): ?>
                            <?php
                            $start = (string) ($ap['start_datetime'] ?? '');
                            $dtLabel = $start !== '' ? format_datetime_in_tenant_tz($start, $tzId) : '—';
                            $st = AppointmentStatus::tryFrom((string) ($ap['status'] ?? ''));
                            $stLabel = $st !== null ? $st->label() : (string) ($ap['status'] ?? '—');
                            $price = (float) ($ap['price'] ?? 0);
                            $statusRaw = (string) ($ap['status'] ?? '');
                            $appointmentId = (int) ($ap['id'] ?? 0);
                            $canConfirm = $statusRaw === AppointmentStatus::Pending->value;
                            $canCancel = in_array(
                                $statusRaw,
                                [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value],
                                true,
                            );
                            ?>
                            <tr>
                                <td><?= e($dtLabel) ?></td>
                                <td><?= e((string) ($ap['service_name'] ?? '—')) ?></td>
                                <td><?= e((string) ($ap['barber_name'] ?? '—')) ?></td>
                                <td><?= e($stLabel) ?></td>
                                <td class="cell-nowrap">R$ <?= e(number_format($price, 2, ',', '.')) ?></td>
                                <td class="my-appointment-actions">
                                    <?php if ($canConfirm): ?>
                                        <form method="post" action="/agendar/<?= e($slug) ?>/meus-agendamentos/confirmar" class="my-appointment-action-form" onsubmit="return confirm('Confirmar este agendamento?');">
                                            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                                            <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">
                                            <button type="submit" class="btn secondary">Confirmar</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($canCancel): ?>
                                        <a class="btn secondary" href="/agendar/<?= e($slug) ?>/meus-agendamentos/<?= $appointmentId ?>/reagendar">Reagendar</a>
                                        <form method="post" action="/agendar/<?= e($slug) ?>/meus-agendamentos/cancelar" class="my-appointment-action-form" onsubmit="return confirm('Cancelar este agendamento? Esta ação não pode ser desfeita.');">
                                            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                                            <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">
                                            <button type="submit" class="btn danger">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!$canConfirm && !$canCancel): ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/../partials/public_platform_footer.php'; ?>
</main>
<script src="<?= e(asset_version('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
