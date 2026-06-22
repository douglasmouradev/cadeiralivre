<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $plans */
/** @var string $title */

$priceFmt = static fn (int $cents): string => format_money_cents($cents);
$planCount = count($plans);
$featuredPlanIndex = null;
foreach ($plans as $i => $p) {
    if (stripos((string) $p['name'], 'prof') !== false) {
        $featuredPlanIndex = $i;
        break;
    }
}
if ($featuredPlanIndex === null && $planCount >= 2) {
    $featuredPlanIndex = (int) floor($planCount / 2);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="Agendamento online multi-tenant para barbearias, nail designers e salões. Trial grátis, portal do cliente e painel completo.">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#f7f4ef">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/landing.css')) ?>">
</head>
<body class="landing-body">
<div class="landing-nav-overlay" id="landing-nav-overlay" aria-hidden="true"></div>

<header class="landing-header">
    <div class="landing-shell landing-header__inner">
        <a href="/" class="landing-brand">
            <img src="/assets/img/cadeiralivre-logo.png" width="44" height="44" alt="">
            <span class="landing-brand__name"><?= e(app_name()) ?></span>
        </a>
        <button type="button" class="landing-nav-toggle" id="landing-nav-toggle" aria-controls="landing-nav" aria-expanded="false" aria-label="Abrir menu">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </button>
        <nav id="landing-nav" class="landing-nav">
            <a href="#recursos">Recursos</a>
            <a href="#planos">Planos</a>
            <a href="/status">Status</a>
            <a href="/login" class="landing-link-quiet">Entrar</a>
            <a href="/cadastro" class="btn landing-btn-primary">Começar grátis</a>
        </nav>
    </div>
</header>

<main>
    <section class="landing-hero">
        <div class="landing-shell landing-hero__grid">
            <div class="landing-hero__copy">
                <p class="landing-kicker">Agendamento para salões e barbearias</p>
                <h1>Sua cadeira.<br>Seu horário.<br><span class="landing-accent-line">Online.</span></h1>
                <p class="landing-lead">Um link para o cliente agendar, um painel para você comandar a equipe — sem planilha, sem troca infinita de mensagens.</p>
                <div class="landing-cta">
                    <a class="btn landing-btn-primary landing-btn-primary--lg" href="/cadastro">Abrir minha loja</a>
                    <p class="landing-cta-note">14 dias grátis · sem cartão</p>
                </div>
                <p class="landing-hero-foot">Já tem conta? <a href="/login">Entrar no painel</a></p>
            </div>
            <aside class="landing-preview" aria-hidden="true">
                <div class="landing-preview__frame">
                    <div class="landing-preview__bar">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="landing-preview__body">
                        <p class="landing-preview__shop">Adriele Nail Design</p>
                        <p class="landing-preview__date">Segunda, 22 jun · 4 horários</p>
                        <ul class="landing-preview__slots">
                            <li><span>09:00</span><em>Alongamento gel</em></li>
                            <li class="is-busy"><span>11:30</span><em>Manutenção</em></li>
                            <li><span>14:00</span><em>Blindagem</em></li>
                            <li class="is-free"><span>16:30</span><em>Disponível</em></li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="landing-benefits" id="recursos">
        <div class="landing-shell">
            <header class="landing-section-head">
                <p class="landing-kicker">Recursos</p>
                <h2>Tudo que o salão precisa num só lugar</h2>
            </header>
            <div class="landing-benefits__grid">
                <article class="landing-benefit">
                    <span class="landing-benefit__idx">01</span>
                    <h3>Link público</h3>
                    <p>Compartilhe sua página <span class="landing-inline-path">/agendar/sua-loja</span> no Instagram, WhatsApp ou QR Code na recepção.</p>
                </article>
                <article class="landing-benefit">
                    <span class="landing-benefit__idx">02</span>
                    <h3>Portal do cliente</h3>
                    <p>Confirmação, cancelamento e reagendamento feitos pelo cliente — menos ligações no meio do atendimento.</p>
                </article>
                <article class="landing-benefit">
                    <span class="landing-benefit__idx">03</span>
                    <h3>Painel da casa</h3>
                    <p>Agenda, profissionais, serviços, clientes e relatórios. Cada loja com sua identidade visual.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="landing-plans" id="planos">
        <div class="landing-shell">
            <header class="landing-section-head landing-section-head--row">
                <div>
                    <p class="landing-kicker">Planos</p>
                    <h2>Comece hoje, cresça no seu ritmo</h2>
                </div>
                <p class="landing-section-aside">Trial de 14 dias em qualquer plano pago. Downgrade ou cancelamento quando quiser.</p>
            </header>
            <div class="landing-plans__grid">
                <?php foreach ($plans as $i => $p):
                    $isFeatured = $featuredPlanIndex !== null && $i === $featuredPlanIndex;
                    $cardClass = 'landing-plan' . ($isFeatured ? ' landing-plan--featured' : '');
                    ?>
                <article class="<?= e($cardClass) ?>">
                    <?php if ($isFeatured): ?>
                        <span class="landing-plan__badge">Mais escolhido</span>
                    <?php endif; ?>
                    <h3 class="landing-plan__name"><?= e((string) $p['name']) ?></h3>
                    <p class="landing-plan__price">
                        <span class="landing-plan__amount"><?= $priceFmt((int) $p['monthly_price_cents']) ?></span>
                        <span class="landing-plan__period">/ mês</span>
                    </p>
                    <ul class="landing-plan__features">
                        <li><span>Profissionais</span><strong><?= isset($p['max_barbers']) && $p['max_barbers'] !== null ? (int) $p['max_barbers'] : '∞' ?></strong></li>
                        <li><span>Agendamentos/mês</span><strong><?= isset($p['max_appointments_per_month']) && $p['max_appointments_per_month'] !== null ? (int) $p['max_appointments_per_month'] : '∞' ?></strong></li>
                    </ul>
                    <a class="btn <?= $isFeatured ? 'landing-btn-primary' : 'landing-btn-outline' ?>" href="/cadastro">
                        <?= (int) $p['monthly_price_cents'] === 0 ? 'Começar grátis' : 'Experimentar' ?>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="landing-shell landing-footer__inner">
        <p class="landing-footer__brand"><?= e(app_name()) ?></p>
        <nav class="landing-footer__links">
            <a href="/privacidade">Privacidade</a>
            <a href="/termos">Termos</a>
            <a href="/lgpd">LGPD</a>
            <a href="/status">Status</a>
        </nav>
        <p class="landing-footer__copy">© <?= e((string) date('Y')) ?> · Feito para quem vive de agenda cheia</p>
    </div>
</footer>
<script src="<?= e(asset_version('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
