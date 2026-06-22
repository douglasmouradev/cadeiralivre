<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $plans */
/** @var string $title */

$priceFmt = static fn (int $cents): string => format_money_cents($cents);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="Agendamento online multi-tenant para barbearias, nail designers e salões. Trial grátis, portal do cliente e painel completo.">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0c0c0c">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
</head>
<body class="landing-body">
<div class="landing-nav-overlay" id="landing-nav-overlay" aria-hidden="true"></div>
<header class="landing-header">
    <a href="/" class="landing-brand">
        <img src="/assets/img/cadeiralivre-logo.png" width="48" height="48" alt="<?= e(app_name()) ?>">
        <span><?= e(app_name()) ?></span>
    </a>
    <button type="button" class="landing-nav-toggle" id="landing-nav-toggle" aria-controls="landing-nav" aria-expanded="false" aria-label="Abrir menu">
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
    </button>
    <nav id="landing-nav" class="landing-nav">
        <a href="#planos">Planos</a>
        <a href="/status">Status</a>
        <a href="/login" class="btn secondary">Entrar</a>
        <a href="/cadastro" class="btn">Começar grátis</a>
    </nav>
</header>

<main>
    <section class="landing-hero">
        <div class="landing-hero__content">
            <p class="landing-eyebrow">SaaS de agendamento</p>
            <h1>Seu negócio com <em>agenda online</em> profissional</h1>
            <p class="landing-lead">Página pública por link, portal do cliente, equipe, relatórios e planos. Ideal para barbearias, nail designers e salões de beleza.</p>
            <div class="landing-cta">
                <a class="btn btn--lg" href="/cadastro">Criar minha loja — 14 dias grátis</a>
                <a class="btn secondary btn--lg" href="/login">Já tenho conta</a>
            </div>
        </div>
    </section>

    <section class="landing-features">
        <article class="landing-feature-card">
            <h3>Link de agendamento</h3>
            <p>Compartilhe <code>/agendar/sua-loja</code> no Instagram e WhatsApp.</p>
        </article>
        <article class="landing-feature-card">
            <h3>Portal do cliente</h3>
            <p>Clientes confirmam, cancelam e reagemendam sem ligar para o salão.</p>
        </article>
        <article class="landing-feature-card">
            <h3>Painel completo</h3>
            <p>Agenda, serviços, profissionais, clientes, relatórios e equipe.</p>
        </article>
    </section>

    <section class="landing-plans" id="planos">
        <h2>Planos</h2>
        <p class="landing-section-lead">Comece grátis por 14 dias. Escale quando sua equipe crescer.</p>
        <div class="landing-plans-grid">
            <?php foreach ($plans as $p): ?>
                <article class="landing-plan-card">
                    <h3><?= e((string) $p['name']) ?></h3>
                    <p class="landing-plan-price"><?= $priceFmt((int) $p['monthly_price_cents']) ?><span>/mês</span></p>
                    <ul class="landing-plan-features">
                        <li>Profissionais: <?= isset($p['max_barbers']) && $p['max_barbers'] !== null ? (int) $p['max_barbers'] : '∞' ?></li>
                        <li>Agend./mês: <?= isset($p['max_appointments_per_month']) && $p['max_appointments_per_month'] !== null ? (int) $p['max_appointments_per_month'] : '∞' ?></li>
                    </ul>
                    <a class="btn" href="/cadastro">Experimentar</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <p>© <?= e((string) date('Y')) ?> <?= e(app_name()) ?> · <a href="/privacidade">Privacidade</a> · <a href="/termos">Termos</a> · <a href="/lgpd">LGPD</a></p>
</footer>
<script src="<?= e(asset_version('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
