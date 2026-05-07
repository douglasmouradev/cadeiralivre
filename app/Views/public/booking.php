<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var array<string, mixed> $tenant */
/** @var list<array<string, mixed>> $services */
/** @var list<array<string, mixed>> $barbers */
/** @var string $slug */
/** @var array<string, mixed>|null $portal_client */

$brandHex = tenant_brand_hex(isset($tenant['primary_color']) ? (string) $tenant['primary_color'] : null);
$portalClient = $portal_client ?? null;
$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body public-theme" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-page" data-booking-main data-booking-slug="<?= e($slug) ?>">
    <header class="public-header">
        <?php if (!empty($tenant['logo_path'])): ?>
            <img src="/media/logo/<?= e($slug) ?>" alt="">
        <?php endif; ?>
        <div>
            <h1 class="public-header__title"><?= e((string) $tenant['name']) ?></h1>
            <p class="muted"><?= e(trim((string) ($tenant['city'] ?? '') . ' ' . (string) ($tenant['state'] ?? ''))) ?></p>
            <p class="booking-portal-links muted">
                <?php if ($portalClient !== null): ?>
                    Olá, <strong><?= e((string) $portalClient['name']) ?></strong> —
                    <a href="/cliente/<?= e($slug) ?>/sair">Sair da conta</a>
                <?php else: ?>
                    <a href="/cliente/<?= e($slug) ?>/entrar">Entrar como cliente</a>
                    ·
                    <a href="/cliente/<?= e($slug) ?>/cadastro">Criar conta</a>
                <?php endif; ?>
            </p>
        </div>
    </header>

    <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
        <div class="alert alert-success" role="status"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (is_string($flashError) && $flashError !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($flashError) ?></div>
    <?php endif; ?>

    <div class="stepper" id="steps" role="list" aria-label="Etapas do agendamento">
        <div class="step active" data-step="1" role="listitem">Serviço</div>
        <div class="step" data-step="2" role="listitem">Barbeiro</div>
        <div class="step" data-step="3" role="listitem">Data</div>
        <div class="step" data-step="4" role="listitem">Horário</div>
        <div class="step" data-step="5" role="listitem">Seus dados</div>
    </div>

    <form id="booking-form" method="post" action="/agendar/<?= e($slug) ?>" data-validate="1" class="card card--compact booking-form"<?= $portalClient !== null ? ' data-portal-client="1"' : '' ?>>
        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="start_datetime" id="f-start">
        <input type="hidden" name="barber_mode" id="booking-barber-mode" value="one">

        <section class="booking-panel" data-panel="1">
            <h3>Escolha o serviço</h3>
            <div class="row">
                <label for="pub-service">Serviço</label>
                <select name="service_id" id="pub-service" required>
                    <option value="">Selecione…</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e((string) $s['name']) ?> (<?= (int) $s['duration_minutes'] ?> min)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn" data-next="2">Continuar</button>
            </div>
        </section>

        <section class="booking-panel" data-panel="2" hidden>
            <h3>Profissional</h3>
            <div class="choice-stack" role="radiogroup" aria-label="Modo de escolha do barbeiro">
                <label><input type="radio" name="barber_mode_choice" value="one" checked> Escolher barbeiro</label>
                <label><input type="radio" name="barber_mode_choice" value="any"> Qualquer disponível</label>
            </div>
            <div class="row" id="barber-select-wrap">
                <label for="pub-barber">Barbeiro</label>
                <select name="barber_id" id="pub-barber" required>
                    <option value="">Selecione…</option>
                    <?php foreach ($barbers as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"><?= e((string) $b['user_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary" data-next="1">Voltar</button>
                <button type="button" class="btn" data-next="3">Continuar</button>
            </div>
        </section>

        <section class="booking-panel" data-panel="3" hidden>
            <h3>Data</h3>
            <div class="row">
                <label for="pub-date">Dia do atendimento</label>
                <input type="date" id="pub-date" required autocomplete="off">
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary" data-next="2">Voltar</button>
                <button type="button" class="btn" data-next="4">Ver horários</button>
            </div>
        </section>

        <section class="booking-panel" data-panel="4" hidden id="slot-section" aria-live="polite">
            <h3>Horário</h3>
            <p class="booking-help muted">Toque em um horário livre para seguir com seus dados.</p>
            <div id="slot-skeleton" class="skeleton skeleton--block" aria-hidden="true"></div>
            <div id="slot-list" class="slot-grid" role="group" aria-label="Horários disponíveis"></div>
            <div class="form-actions">
                <button type="button" class="btn secondary" data-next="3">Voltar</button>
            </div>
        </section>

        <section class="booking-panel" data-panel="5" hidden>
            <h3>Seus dados</h3>
            <?php if ($portalClient !== null): ?>
                <p class="muted booking-help">Os dados da sua conta são usados neste agendamento. Você pode atualizar o telefone abaixo.</p>
                <div class="row"><label for="c-name">Nome</label><input id="c-name" name="client_name" required autocomplete="name" readonly value="<?= e((string) $portalClient['name']) ?>"></div>
                <div class="row"><label for="c-email">E-mail</label><input id="c-email" name="client_email" type="email" autocomplete="email" readonly value="<?= e((string) ($portalClient['email'] ?? '')) ?>"></div>
                <div class="row"><label for="c-phone">Telefone</label><input id="c-phone" name="client_phone" type="tel" autocomplete="tel" value="<?= e((string) ($portalClient['phone'] ?? '')) ?>"></div>
            <?php else: ?>
                <div class="row"><label for="c-name">Nome</label><input id="c-name" name="client_name" required autocomplete="name"></div>
                <div class="row"><label for="c-email">E-mail</label><input id="c-email" name="client_email" type="email" autocomplete="email" required></div>
                <div class="row"><label for="c-phone">Telefone</label><input id="c-phone" name="client_phone" type="tel" autocomplete="tel"></div>
            <?php endif; ?>
            <div class="row">
                <label for="pay-method">Forma de pagamento (preferência)</label>
                <select id="pay-method" name="payment_method">
                    <option value="">— Prefiro não informar agora —</option>
                    <option value="pix">Pix</option>
                    <option value="cash">Dinheiro</option>
                    <option value="card">Cartão (crédito ou débito)</option>
                    <option value="on_site">Pagar no local</option>
                    <option value="unsure">A definir depois</option>
                </select>
            </div>
            <div class="row">
                <label for="pay-note">Observações sobre pagamento ou outras informações</label>
                <textarea id="pay-note" name="payment_note" rows="2" maxlength="800" placeholder="Ex.: posso pagar em Pix no dia; preciso de nota fiscal…"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary" data-next="4">Voltar</button>
                <button class="btn" type="submit">Confirmar agendamento</button>
            </div>
        </section>
    </form>
</main>
<div id="toast-root" class="toast-root" aria-live="polite"></div>
<script src="/assets/js/app.js" defer></script>
<script src="/assets/js/booking.js" defer></script>
</body>
</html>
