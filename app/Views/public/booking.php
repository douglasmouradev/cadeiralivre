<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var array<string, mixed> $tenant */
/** @var list<array<string, mixed>> $services */
/** @var list<array<string, mixed>> $barbers */
/** @var string $slug */
/** @var array<string, mixed> $portal_client */

$brandHex = tenant_brand_hex(isset($tenant['primary_color']) ? (string) $tenant['primary_color'] : null);
$portalClient = $portal_client;
$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
$coverUrl = !empty($tenant['cover_path']) ? tenant_cover_url($slug) : null;
$location = trim(implode(' · ', array_filter([
    trim((string) ($tenant['address'] ?? '')),
    trim((string) ($tenant['city'] ?? '') . ' ' . (string) ($tenant['state'] ?? '')),
])));
$tagline = trim((string) ($tenant['public_tagline'] ?? ''));
$instagram = trim((string) ($tenant['instagram_url'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <?php require __DIR__ . '/../partials/public_tenant_head.php'; ?>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body public-theme booking-premium" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-page booking-premium__main" data-booking-main data-booking-slug="<?= e($slug) ?>">
    <header class="store-hero<?= $coverUrl ? ' store-hero--has-cover' : '' ?>"<?= $coverUrl ? ' style="--hero-cover: url(' . e($coverUrl) . ')"' : '' ?>>
        <div class="store-hero__overlay">
            <div class="store-hero__brand">
                <?php if (!empty($tenant['logo_path'])): ?>
                    <img class="store-hero__logo" src="<?= e(tenant_logo_url($slug)) ?>" alt="">
                <?php endif; ?>
                <div>
                    <h1 class="store-hero__title"><?= e((string) $tenant['name']) ?></h1>
                    <?php if ($tagline !== ''): ?>
                        <p class="store-hero__tagline"><?= e($tagline) ?></p>
                    <?php endif; ?>
                    <?php if ($location !== ''): ?>
                        <p class="store-hero__meta"><?= e($location) ?></p>
                    <?php endif; ?>
                    <?php if ($instagram !== ''): ?>
                        <p class="store-hero__meta"><a href="<?= e($instagram) ?>" target="_blank" rel="noopener">Instagram</a></p>
                    <?php endif; ?>
                </div>
            </div>
            <p class="store-hero__user muted">
                Olá, <strong><?= e((string) $portalClient['name']) ?></strong> —
                <a href="/agendar/<?= e($slug) ?>/meus-agendamentos">Meus agendamentos</a>
                ·
                <a href="/cliente/<?= e($slug) ?>/sair">Sair</a>
            </p>
        </div>
    </header>

    <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
        <div class="alert alert-success" role="status"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (is_string($flashError) && $flashError !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($flashError) ?></div>
    <?php endif; ?>

    <div class="stepper stepper--premium" id="steps" role="list" aria-label="Etapas do agendamento">
        <div class="step active" data-step="1" role="listitem"><span>1</span> Serviço</div>
        <div class="step" data-step="2" role="listitem"><span>2</span> Profissional</div>
        <div class="step" data-step="3" role="listitem"><span>3</span> Data</div>
        <div class="step" data-step="4" role="listitem"><span>4</span> Horário</div>
        <div class="step" data-step="5" role="listitem"><span>5</span> Confirmar</div>
    </div>

    <form id="booking-form" method="post" action="/agendar/<?= e($slug) ?>" data-validate="1" class="card card--premium booking-form" data-portal-client="1">
        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="start_datetime" id="f-start">
        <input type="hidden" name="barber_mode" id="booking-barber-mode" value="one">
        <input type="hidden" name="service_id" id="pub-service" value="">
        <input type="hidden" name="barber_id" id="pub-barber" value="">

        <section class="booking-panel" data-panel="1">
            <h3>Escolha o serviço</h3>
            <?php if ($services === []): ?>
                <p class="empty-state">Nenhum serviço disponível no momento. Volte em breve.</p>
            <?php else: ?>
                <div class="pick-grid pick-grid--services" role="listbox" aria-label="Serviços">
                    <?php foreach ($services as $s): ?>
                        <?php
                        $desc = trim((string) ($s['description'] ?? ''));
                        if ($desc === '') {
                            $desc = (int) $s['duration_minutes'] . ' minutos de atendimento';
                        }
                        ?>
                        <button type="button" class="pick-card pick-card--service" role="option" data-service-id="<?= (int) $s['id'] ?>" data-service-name="<?= e((string) $s['name']) ?>">
                            <span class="pick-card__eyebrow"><?= (int) $s['duration_minutes'] ?> min</span>
                            <strong class="pick-card__title"><?= e((string) $s['name']) ?></strong>
                            <span class="pick-card__desc"><?= e($desc) ?></span>
                            <span class="pick-card__price"><?= e(format_money_br((float) $s['price'])) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="booking-panel" data-panel="2" hidden>
            <h3>Profissional</h3>
            <div class="mode-toggle" role="radiogroup" aria-label="Modo de escolha do profissional">
                <label class="mode-toggle__item"><input type="radio" name="barber_mode_choice" value="one" checked> Escolher profissional</label>
                <label class="mode-toggle__item"><input type="radio" name="barber_mode_choice" value="any"> Qualquer disponível</label>
            </div>
            <div id="barber-select-wrap" class="pick-grid pick-grid--barbers" role="listbox" aria-label="Profissionais">
                <?php foreach ($barbers as $b): ?>
                    <?php
                    $avatar = barber_display_avatar_url(
                        isset($b['user_avatar']) ? (string) $b['user_avatar'] : null,
                        $slug,
                        !empty($tenant['logo_path']),
                    );
                    $avatarIsBrand = empty($b['user_avatar']) && !empty($tenant['logo_path']) && $avatar !== null;
                    $specItems = barber_specialties_list($b['specialties'] ?? null);
                    ?>
                    <button type="button" class="pick-card pick-card--barber" role="option" data-barber-id="<?= (int) $b['id'] ?>">
                        <?php if ($avatar): ?>
                            <img class="pick-card__avatar<?= $avatarIsBrand ? ' pick-card__avatar--brand' : '' ?>" src="<?= e($avatar) ?>" alt="" width="56" height="56" loading="lazy">
                        <?php else: ?>
                            <span class="pick-card__avatar pick-card__avatar--placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $b['user_name'], 0, 1))) ?></span>
                        <?php endif; ?>
                        <strong class="pick-card__title"><?= e((string) $b['user_name']) ?></strong>
                        <?php if ($specItems !== []): ?>
                            <span class="pick-card__tags">
                                <?php foreach ($specItems as $tag): ?>
                                    <span class="pick-card__tag"><?= e($tag) ?></span>
                                <?php endforeach; ?>
                            </span>
                        <?php elseif (!empty($b['bio'])): ?>
                            <span class="pick-card__desc pick-card__desc--clamp"><?= e(trim((string) $b['bio'])) ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary" data-next="1">Voltar</button>
                <button type="button" class="btn" data-next="3" id="barber-continue">Continuar</button>
            </div>
        </section>

        <section class="booking-panel" data-panel="3" hidden>
            <h3>Escolha a data</h3>
            <div class="booking-calendar" id="booking-calendar" aria-label="Calendário"></div>
            <input type="hidden" id="pub-date" required>
            <p class="booking-calendar__selected muted" id="date-label">Selecione um dia no calendário.</p>
            <div class="form-actions">
                <button type="button" class="btn secondary" data-next="2">Voltar</button>
                <button type="button" class="btn" data-next="4" id="date-continue">Ver horários</button>
            </div>
        </section>

        <section class="booking-panel" data-panel="4" hidden id="slot-section" aria-live="polite">
            <h3>Horário disponível</h3>
            <p class="booking-help muted">Toque em um horário para revisar e confirmar.</p>
            <div id="slot-skeleton" class="skeleton skeleton--block" aria-hidden="true"></div>
            <div id="slot-list" class="slot-grid slot-grid--premium" role="group" aria-label="Horários disponíveis"></div>
            <div class="form-actions">
                <button type="button" class="btn secondary" data-next="3">Voltar</button>
            </div>
        </section>

        <section class="booking-panel" data-panel="5" hidden>
            <h3>Confirmação</h3>
            <div class="booking-summary" id="booking-summary" aria-live="polite"></div>
            <p class="muted booking-help">Confira seus dados. Você pode atualizar o telefone abaixo.</p>
            <div class="row"><label for="c-name">Nome</label><input id="c-name" name="client_name" required autocomplete="name" readonly value="<?= e((string) $portalClient['name']) ?>"></div>
            <div class="row"><label for="c-email">E-mail</label><input id="c-email" name="client_email" type="email" autocomplete="email" readonly value="<?= e((string) ($portalClient['email'] ?? '')) ?>"></div>
            <div class="row"><label for="c-phone">Telefone</label><input id="c-phone" name="client_phone" type="tel" autocomplete="tel" value="<?= e((string) ($portalClient['phone'] ?? '')) ?>"></div>
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
                <label for="pay-note">Observações</label>
                <textarea id="pay-note" name="payment_note" rows="2" maxlength="800" placeholder="Ex.: prefiro Pix no dia do atendimento…"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary" data-next="4">Voltar</button>
                <button class="btn btn--lg" type="submit">Confirmar agendamento</button>
            </div>
        </section>
    </form>
</main>
<div id="toast-root" class="toast-root" aria-live="polite"></div>
<script type="application/json" id="booking-barbers-json"><?= json_encode(array_map(static fn ($b) => [
    'id' => (int) $b['id'],
    'name' => (string) $b['user_name'],
], $barbers), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="/assets/js/app.js" defer></script>
<script src="/assets/js/booking.js" defer></script>
</body>
</html>
