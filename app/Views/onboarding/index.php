<?php

declare(strict_types=1);

/** @var array<string, mixed> $tenant */
/** @var bool $hasLogo */
/** @var bool $hasServices */
/** @var bool $hasHours */
/** @var string $bookingUrl */
/** @var string $csrf */

ob_start();
$slug = (string) ($tenant['slug'] ?? '');
?>
<div class="toolbar">
    <h2 class="toolbar__title">Primeiros passos</h2>
</div>
<p class="muted mb-1">Configure sua loja em poucos minutos. Você pode voltar a esta página em <a href="/onboarding">/onboarding</a> até concluir.</p>

<div class="onboarding-checklist">
    <article class="card onboarding-step <?= $hasLogo ? 'onboarding-step--done' : '' ?>">
        <h3>1. Logo e identidade</h3>
        <p class="muted">Envie a logo e defina a cor da sua marca.</p>
        <a class="btn secondary" href="/configuracoes">Ir para configurações</a>
    </article>
    <article class="card onboarding-step <?= $hasServices ? 'onboarding-step--done' : '' ?>">
        <h3>2. Serviços</h3>
        <p class="muted">Cadastre os serviços que os clientes podem agendar.</p>
        <a class="btn secondary" href="/servicos">Gerenciar serviços</a>
    </article>
    <article class="card onboarding-step <?= $hasHours ? 'onboarding-step--done' : '' ?>">
        <h3>3. Horários</h3>
        <p class="muted">Defina quando sua equipe atende.</p>
        <a class="btn secondary" href="/agenda">Configurar agenda</a>
    </article>
    <article class="card onboarding-step">
        <h3>4. Compartilhar link</h3>
        <?php if ($bookingUrl !== ''): ?>
            <p class="muted">Copie e envie para seus clientes:</p>
            <input type="text" readonly value="<?= e($bookingUrl) ?>" class="onboarding-link-input" onclick="this.select()">
        <?php else: ?>
            <p class="muted">Defina o identificador da loja nas configurações.</p>
        <?php endif; ?>
    </article>
</div>

<form method="post" action="/onboarding/concluir" class="mt-1">
    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
    <button type="submit" class="btn">Concluir configuração inicial</button>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
