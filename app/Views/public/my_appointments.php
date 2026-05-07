<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Helpers\Flash;

/** @var array<string, mixed> $tenant */
/** @var string $slug */
/** @var array<string, mixed> $portal_client */
/** @var list<array<string, mixed>> $appointments */
/** @var string $timezone */

$brandHex = tenant_brand_hex(isset($tenant['primary_color']) ? (string) $tenant['primary_color'] : null);
$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
$tzId = $timezone;
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
<main class="public-page">
    <header class="public-header">
        <?php if (!empty($tenant['logo_path'])): ?>
            <img src="/media/logo/<?= e($slug) ?>" alt="">
        <?php endif; ?>
        <div>
            <h1 class="public-header__title"><?= e((string) $tenant['name']) ?></h1>
            <p class="muted"><?= e(trim((string) ($tenant['city'] ?? '') . ' ' . (string) ($tenant['state'] ?? ''))) ?></p>
            <p class="booking-portal-links muted">
                Olá, <strong><?= e((string) $portal_client['name']) ?></strong> —
                <a href="/agendar/<?= e($slug) ?>">Agendar horário</a>
                ·
                <a href="/agendar/<?= e($slug) ?>/meus-agendamentos" aria-current="page">Meus agendamentos</a>
                ·
                <a href="/cliente/<?= e($slug) ?>/sair">Sair da conta</a>
            </p>
        </div>
    </header>

    <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
        <div class="alert alert-success" role="status"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (is_string($flashError) && $flashError !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($flashError) ?></div>
    <?php endif; ?>

    <div class="card card--compact">
        <h2 style="margin:0 0 0.5rem;font-size:1.15rem;font-family:'DM Serif Display',Georgia,serif;font-weight:400">Meus agendamentos</h2>
        <p class="muted" style="margin:0;font-size:0.88rem">Histórico nesta barbearia (fuso: <?= e($tzId) ?>).</p>

        <?php if ($appointments === []): ?>
            <p class="muted" style="margin:1rem 0 0">Você ainda não tem agendamentos aqui. <a href="/agendar/<?= e($slug) ?>">Agendar agora</a></p>
        <?php else: ?>
            <div class="my-appointments-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data e hora</th>
                            <th>Serviço</th>
                            <th>Profissional</th>
                            <th>Status</th>
                            <th>Valor</th>
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
                            ?>
                            <tr>
                                <td><?= e($dtLabel) ?></td>
                                <td><?= e((string) ($ap['service_name'] ?? '—')) ?></td>
                                <td><?= e((string) ($ap['barber_name'] ?? '—')) ?></td>
                                <td><?= e($stLabel) ?></td>
                                <td>R$ <?= e(number_format($price, 2, ',', '.')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
