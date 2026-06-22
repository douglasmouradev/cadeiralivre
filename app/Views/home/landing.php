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

$demoUrl = (string) ($demoBookingUrl ?? '/agendar/adriele-cardoso-nail-design');
$pageUrl = rtrim((string) ($baseUrl ?? ''), '/') ?: '';
$ogImage = $pageUrl !== '' ? $pageUrl . '/assets/img/cadeiralivre-logo.png' : '/assets/img/cadeiralivre-logo.png';
$waPhone = preg_replace('/\D+/', '', (string) ($supportWhatsApp ?? '5571997087082')) ?: '5571997087082';
$waMsg = rawurlencode('Olá! Tenho interesse no ' . app_name() . '.');
$waUrl = 'https://wa.me/' . $waPhone . '?text=' . $waMsg;
$tenantCount = max(0, (int) ($activeTenants ?? 0));

$planFeatureMap = [
    'free' => ['Portal do cliente', 'Lembretes por e-mail', 'Página pública personalizada'],
    'pro' => ['Lembretes WhatsApp', 'Relatórios e comissões', 'Exportação CSV', 'PWA no celular'],
    'enterprise' => ['Profissionais ilimitados', 'Agendamentos ilimitados', 'Relatórios completos', 'Equipe sem limites'],
];

$faqItems = [
    ['Preciso de CNPJ para usar?', 'Não. Pessoa física ou jurídica pode usar o ' . app_name() . ' — ideal para profissionais autônomos e estabelecimentos.'],
    ['O trial limita funcionalidades?', 'Não. Durante os 14 dias de teste você usa o painel completo, conforme o plano escolhido.'],
    ['Meus profissionais acessam o sistema?', 'Sim. Cada um pode ter login próprio com permissões de profissional ou recepcionista.'],
    ['O cliente paga para agendar?', 'Não. O agendamento pelo link público é gratuito para o seu cliente.'],
    ['Posso mudar de plano depois?', 'Sim. Upgrade ou downgrade pelo painel, com cobrança proporcional via Stripe.'],
    ['Consigo exportar meus dados?', 'Sim. Relatórios e lista de clientes podem ser exportados em CSV a qualquer momento.'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="Agendamento online multi-tenant para barbearias, nail designers e salões. Trial grátis, portal do cliente e painel completo.">
    <link rel="canonical" href="<?= e($pageUrl !== '' ? $pageUrl . '/' : '/') ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="<?= e(app_name()) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="Agendamento online para barbearias e salões. Link público, portal do cliente, lembretes e painel completo. 14 dias grátis.">
    <meta property="og:url" content="<?= e($pageUrl !== '' ? $pageUrl . '/' : '/') ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="Agendamento online para barbearias e salões. 14 dias grátis.">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php require __DIR__ . '/../partials/site_favicons.php'; ?>
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
            <img src="/assets/img/cadeiralivre-logo.png" width="44" height="44" alt="<?= e(app_name()) ?>">
            <span class="landing-brand__name"><?= e(app_name()) ?></span>
        </a>
        <button type="button" class="landing-nav-toggle" id="landing-nav-toggle" aria-controls="landing-nav" aria-expanded="false" aria-label="Abrir menu">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </button>
        <nav id="landing-nav" class="landing-nav">
            <a href="#sobre">Sobre</a>
            <a href="#funcoes">Funções</a>
            <a href="#como-comecar">Como começar</a>
            <a href="#planos">Planos</a>
            <a href="#faq">FAQ</a>
            <a href="/status">Status</a>
            <a href="/login" class="landing-link-quiet">Entrar</a>
            <a href="/cadastro" class="btn landing-btn-primary">Teste grátis</a>
        </nav>
    </div>
</header>

<main>
    <section class="landing-hero">
        <div class="landing-hero__glow landing-hero__glow--a" aria-hidden="true"></div>
        <div class="landing-hero__glow landing-hero__glow--b" aria-hidden="true"></div>
        <div class="landing-shell landing-hero__grid">
            <div class="landing-hero__copy">
                <p class="landing-kicker"><span class="landing-kicker__dot" aria-hidden="true"></span>Agendamento para salões e barbearias</p>
                <h1>Sua cadeira.<br>Seu horário.<br><span class="landing-accent-line">Online.</span></h1>
                <p class="landing-lead">Um link para o cliente agendar, um painel para você comandar a equipe — sem planilha, sem troca infinita de mensagens.</p>
                <div class="landing-cta landing-cta--row">
                    <a class="btn landing-btn-primary landing-btn-primary--lg" href="/cadastro">Abrir minha loja</a>
                    <a class="btn landing-btn-outline landing-btn-outline--lg" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener">Ver demonstração</a>
                </div>
                <p class="landing-cta-note">14 dias grátis · sem cartão</p>
                <ul class="landing-hero__stats" aria-label="Destaques">
                    <li><strong>24h</strong><span>Agendamento online</span></li>
                    <li><strong>100%</strong><span>Na nuvem</span></li>
                    <li><strong>0</strong><span>Taxa para o cliente</span></li>
                </ul>
                <p class="landing-hero-foot">Já tem conta? <a href="/login">Entrar no painel</a></p>
            </div>
            <aside class="landing-preview">
                <div class="landing-preview__halo" aria-hidden="true"></div>
                <a class="landing-preview__link" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener" aria-label="Ver demonstração ao vivo — Adriele Nail Design">
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
                <span class="landing-preview__caption">Exemplo real · clique para agendar</span>
                </a>
            </aside>
        </div>
    </section>

    <section class="landing-proof" aria-label="Prova social">
        <div class="landing-shell landing-proof__inner">
            <article class="landing-proof__case">
                <img class="landing-proof__logo" src="/assets/img/brands/adriele-cardoso-logo.png" width="56" height="56" alt="Adriele Nail Design" loading="lazy">
                <div>
                    <p class="landing-proof__label">Loja em produção</p>
                    <h3>Adriele Nail Design</h3>
                    <p>Nail designer usando o <?= e(app_name()) ?> para receber agendamentos online com página própria e identidade visual.</p>
                    <a class="landing-proof__link" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener">Ver página de agendamento →</a>
                </div>
            </article>
            <?php if ($tenantCount > 0): ?>
            <ul class="landing-proof__stats">
                <li><strong><?= e((string) $tenantCount) ?></strong><span><?= $tenantCount === 1 ? 'Loja ativa' : 'Lojas ativas' ?></span></li>
                <li><strong>14</strong><span>Dias de trial</span></li>
                <li><strong>LGPD</strong><span>Conformidade</span></li>
            </ul>
            <?php else: ?>
            <ul class="landing-proof__stats">
                <li><strong>14</strong><span>Dias de trial</span></li>
                <li><strong>0</strong><span>Taxa p/ cliente</span></li>
                <li><strong>LGPD</strong><span>Conformidade</span></li>
            </ul>
            <?php endif; ?>
        </div>
    </section>

    <section class="landing-about" id="sobre">
        <div class="landing-shell landing-about__grid">
            <header class="landing-section-head">
                <p class="landing-kicker">Sobre o <?= e(app_name()) ?></p>
                <h2>Uma nova experiência para quem vive de agenda</h2>
                <p class="landing-section-lead">Sistema de gestão online para barbearias, nail designers e salões — com agendamento, portal do cliente e painel completo na nuvem.</p>
            </header>
            <div class="landing-about__modules">
                <article class="landing-module">
                    <span class="landing-module__icon" aria-hidden="true">01</span>
                    <span class="landing-module__tag">Painel web</span>
                    <h3>Gestão do estabelecimento</h3>
                    <p>Controle profissionais, serviços, clientes, horários e relatórios financeiros. Acesso seguro de qualquer lugar, com identidade visual da sua loja.</p>
                </article>
                <article class="landing-module">
                    <span class="landing-module__icon" aria-hidden="true">02</span>
                    <span class="landing-module__tag">Portal do cliente</span>
                    <h3>Agendamento sem fricção</h3>
                    <p>Seu cliente agenda pelo link público, confirma, cancela ou reagenda sozinho — com lembretes automáticos por e-mail e WhatsApp.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="landing-goals">
        <div class="landing-shell">
            <header class="landing-section-head landing-section-head--center">
                <p class="landing-kicker">Nosso objetivo</p>
                <h2>Mais tempo, mais clientes, mais faturamento</h2>
            </header>
            <div class="landing-goals__grid">
                <article class="landing-goal">
                    <span class="landing-goal__icon" aria-hidden="true">01</span>
                    <h3>Otimizar seu tempo</h3>
                    <p>Organize a agenda da equipe e reduza mensagens repetidas com agendamento online e confirmações automáticas.</p>
                </article>
                <article class="landing-goal">
                    <span class="landing-goal__icon" aria-hidden="true">02</span>
                    <h3>Fidelizar o cliente</h3>
                    <p>Portal próprio, lembretes antes do horário e avaliações pós-atendimento mantêm o cliente próximo da sua marca.</p>
                </article>
                <article class="landing-goal">
                    <span class="landing-goal__icon" aria-hidden="true">03</span>
                    <h3>Aumentar o movimento</h3>
                    <p>Horários disponíveis 24h no link público — o cliente agenda quando quiser, mesmo fora do expediente.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="landing-features" id="funcoes">
        <div class="landing-shell">
            <header class="landing-section-head landing-section-head--center">
                <p class="landing-kicker">Funcionalidades</p>
                <h2>Tudo que você precisa para comandar a casa</h2>
                <p class="landing-section-lead">Recursos pensados para o dia a dia do salão — do primeiro agendamento ao fechamento do mês.</p>
            </header>
            <div class="landing-features__grid">
                <article class="landing-feat">
                    <h3>Agendamento online</h3>
                    <p>Link público <span class="landing-inline-path">/agendar/sua-loja</span> com escolha de serviço, profissional e horário.</p>
                </article>
                <article class="landing-feat">
                    <h3>Lembretes automáticos</h3>
                    <p>E-mail e WhatsApp antes do horário para reduzir faltas e última hora de confirmação.</p>
                </article>
                <article class="landing-feat">
                    <h3>Portal do cliente</h3>
                    <p>Confirmação, cancelamento e reagendamento feitos pelo próprio cliente, sem ligar na recepção.</p>
                </article>
                <article class="landing-feat">
                    <h3>Página da loja</h3>
                    <p>Site de agendamento com logo, cores e dados do estabelecimento — pronto para compartilhar.</p>
                </article>
                <article class="landing-feat">
                    <h3>Gestão financeira</h3>
                    <p>Registro de pagamentos por atendimento, descontos e formas de pagamento no balcão.</p>
                </article>
                <article class="landing-feat">
                    <h3>Relatórios gerenciais</h3>
                    <p>Faturamento, ticket médio, clientes inativos e exportação CSV para análise externa.</p>
                </article>
                <article class="landing-feat">
                    <h3>Comissões</h3>
                    <p>Percentual por profissional com relatório de comissões no período que você escolher.</p>
                </article>
                <article class="landing-feat">
                    <h3>Avaliações</h3>
                    <p>Link automático após o atendimento para o cliente avaliar e você acompanhar a satisfação.</p>
                </article>
                <article class="landing-feat">
                    <h3>Agenda da equipe</h3>
                    <p>Visão por profissional, bloqueios de horário e horários especiais por data.</p>
                </article>
                <article class="landing-feat">
                    <h3>Clientes e histórico</h3>
                    <p>Cadastro, busca, paginação e exportação da base — com histórico de visitas.</p>
                </article>
                <article class="landing-feat">
                    <h3>Equipe e permissões</h3>
                    <p>Dono, recepcionista e profissional com acessos diferentes ao painel.</p>
                </article>
                <article class="landing-feat">
                    <h3>PWA no celular</h3>
                    <p>Instale o painel na tela inicial do smartphone para consultar a agenda em movimento.</p>
                </article>
                <article class="landing-feat landing-feat--soon">
                    <span class="landing-feat__badge">Em breve</span>
                    <h3>Programa de fidelidade</h3>
                    <p>Pontos e recompensas para clientes frequentes — em desenvolvimento.</p>
                </article>
                <article class="landing-feat landing-feat--soon">
                    <span class="landing-feat__badge">Em breve</span>
                    <h3>Lista de espera</h3>
                    <p>Cliente entra na fila quando não há horário e recebe aviso quando abrir vaga.</p>
                </article>
                <article class="landing-feat landing-feat--soon">
                    <span class="landing-feat__badge">Em breve</span>
                    <h3>Pacotes de serviços</h3>
                    <p>Combos com desconto e controle de sessões vendidas antecipadamente.</p>
                </article>
                <article class="landing-feat landing-feat--soon">
                    <span class="landing-feat__badge">Em breve</span>
                    <h3>Pagamento online</h3>
                    <p>Cobrança antecipada ou sinal no momento do agendamento pelo portal.</p>
                </article>
            </div>
            <p class="landing-features__cta">
                <a class="btn landing-btn-outline" href="/cadastro">Experimentar grátis por 14 dias</a>
            </p>
        </div>
    </section>

    <section class="landing-steps" id="como-comecar">
        <div class="landing-shell">
            <header class="landing-section-head landing-section-head--center">
                <p class="landing-kicker">Como começar</p>
                <h2>Três passos e sua loja no ar</h2>
            </header>
            <ol class="landing-steps__list">
                <li class="landing-step">
                    <span class="landing-step__num">1</span>
                    <div class="landing-step__card">
                        <h3>Faça o cadastro</h3>
                        <p>Crie sua conta informando nome da loja, contato e e-mail de acesso. Sem cartão no trial.</p>
                    </div>
                </li>
                <li class="landing-step">
                    <span class="landing-step__num">2</span>
                    <div class="landing-step__card">
                        <h3>Configure o básico</h3>
                        <p>Cadastre serviços, profissionais e horário de funcionamento no onboarding guiado.</p>
                    </div>
                </li>
                <li class="landing-step">
                    <span class="landing-step__num">3</span>
                    <div class="landing-step__card">
                        <h3>Compartilhe o link</h3>
                        <p>Envie sua página de agendamento no Instagram, WhatsApp ou QR Code na recepção.</p>
                    </div>
                </li>
            </ol>
            <p class="landing-steps__foot">
                <a class="btn landing-btn-primary landing-btn-primary--lg" href="/cadastro">Cadastrar minha loja</a>
            </p>
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
                    $slug = (string) ($p['slug'] ?? '');
                    $extras = $planFeatureMap[$slug] ?? ['Portal do cliente', 'Lembretes automáticos', 'Relatórios básicos'];
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
                        <?php foreach ($extras as $feat): ?>
                        <li class="landing-plan__feat-extra"><span><?= e($feat) ?></span><strong aria-hidden="true">✓</strong></li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="btn <?= $isFeatured ? 'landing-btn-primary' : 'landing-btn-outline' ?>" href="/cadastro">
                        <?= (int) $p['monthly_price_cents'] === 0 ? 'Começar grátis' : 'Experimentar' ?>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="landing-faq" id="faq">
        <div class="landing-shell landing-faq__grid">
            <header class="landing-section-head">
                <p class="landing-kicker">Perguntas frequentes</p>
                <h2>Dúvidas comuns</h2>
            </header>
            <div class="landing-faq__list">
                <?php foreach ($faqItems as $faq): ?>
                <details class="landing-faq__item">
                    <summary><?= e($faq[0]) ?></summary>
                    <p><?= e($faq[1]) ?></p>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="landing-final-cta">
        <div class="landing-shell">
            <div class="landing-final-cta__card">
                <div class="landing-final-cta__inner">
                    <p class="landing-kicker landing-kicker--light">Comece hoje</p>
                    <h2>Pronto para encher a agenda?</h2>
                    <p>14 dias grátis · sem cartão · cancele quando quiser</p>
                    <a class="btn landing-btn-primary landing-btn-primary--lg" href="/cadastro">Começar agora</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="landing-shell landing-footer__inner">
        <p class="landing-footer__brand"><?= e(app_name()) ?></p>
        <nav class="landing-footer__links">
            <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener">WhatsApp</a>
            <a href="/privacidade">Privacidade</a>
            <a href="/termos">Termos</a>
            <a href="/lgpd">LGPD</a>
            <a href="/status">Status</a>
        </nav>
        <p class="landing-footer__copy">© <?= e((string) date('Y')) ?> · Feito para quem vive de agenda cheia</p>
    </div>
</footer>

<div class="landing-sticky-cta" id="landing-sticky-cta" hidden>
    <a class="btn landing-btn-primary" href="/cadastro">Teste grátis — 14 dias</a>
    <a class="btn landing-btn-outline landing-btn-outline--compact" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener">Demo</a>
</div>

<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => app_name(),
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'BRL', 'description' => 'Trial de 14 dias'],
    'description' => 'Agendamento online para barbearias, nail designers e salões.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(static fn (array $item): array => [
        '@type' => 'Question',
        'name' => $item[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
    ], $faqItems),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= e(asset_version('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
