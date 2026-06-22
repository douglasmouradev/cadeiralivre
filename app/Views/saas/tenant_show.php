<?php

declare(strict_types=1);

/** @var array<string, mixed> $tenant */
/** @var array<string, mixed>|null $plan */
/** @var array<string, mixed> $stats */
/** @var array<string, mixed>|null $owner */
/** @var list<array<string, mixed>> $plans */
/** @var list<array<string, mixed>> $auditLogs */
/** @var string $publicUrl */
/** @var string $csrf */

$id = (int) ($tenant['id'] ?? 0);
$slug = (string) ($tenant['slug'] ?? '');
$sub = (string) ($tenant['subscription_status'] ?? 'none');
$stripeCustomer = stripe_customer_url(isset($tenant['billing_customer_id']) ? (string) $tenant['billing_customer_id'] : null);
$stripeSub = stripe_subscription_url(isset($tenant['billing_subscription_id']) ? (string) $tenant['billing_subscription_id'] : null);
$isSuspended = (string) ($tenant['status'] ?? '') === 'suspended';

ob_start();
?>
<p class="mb-1"><a href="/saas/tenants">&larr; Voltar às lojas</a></p>

<section class="grid stats-grid saas-stats">
    <article class="card stat-card">
        <h3>Agendamentos (mês)</h3>
        <p class="stat-value"><?= (int) ($stats['appointments_month'] ?? 0) ?></p>
    </article>
    <article class="card stat-card">
        <h3>Hoje</h3>
        <p class="stat-value"><?= (int) ($stats['appointments_today'] ?? 0) ?></p>
    </article>
    <article class="card stat-card">
        <h3>Clientes</h3>
        <p class="stat-value"><?= (int) ($stats['clients_total'] ?? 0) ?></p>
    </article>
    <article class="card stat-card">
        <h3>Profissionais</h3>
        <p class="stat-value"><?= (int) ($stats['barbers_total'] ?? 0) ?></p>
    </article>
</section>

<div class="grid saas-detail-grid">
    <section class="card">
        <h2>Dados da loja</h2>
        <dl class="detail-list">
            <dt>Nome</dt><dd><?= e((string) ($tenant['name'] ?? '')) ?></dd>
            <dt>Slug</dt><dd><code><?= e($slug) ?></code></dd>
            <dt>E-mail</dt><dd><?= e((string) ($tenant['email'] ?? '')) ?></dd>
            <dt>Telefone</dt><dd><?= e((string) ($tenant['phone'] ?? '—')) ?></dd>
            <dt>Cidade</dt><dd><?= e(trim((string) ($tenant['city'] ?? '') . ' ' . (string) ($tenant['state'] ?? '')) ?: '—') ?></dd>
            <dt>Estado</dt>
            <dd>
                <?php if ($isSuspended): ?>
                    <span class="pill pill--danger">Suspensa</span>
                <?php else: ?>
                    <span class="pill pill--ok">Ativa</span>
                <?php endif; ?>
            </dd>
            <dt>Criada em</dt><dd><?= e(format_datetime_in_tenant_tz((string) ($tenant['created_at'] ?? ''), 'America/Sao_Paulo')) ?></dd>
            <?php if (!empty($tenant['trial_ends_at'])): ?>
            <dt>Trial até</dt><dd><?= e(format_datetime_in_tenant_tz((string) $tenant['trial_ends_at'], 'America/Sao_Paulo')) ?></dd>
            <?php endif; ?>
            <?php if (!empty($stats['last_appointment_at'])): ?>
            <dt>Último agendamento</dt><dd><?= e(format_datetime_in_tenant_tz((string) $stats['last_appointment_at'], (string) ($tenant['timezone'] ?? 'America/Sao_Paulo'))) ?></dd>
            <?php endif; ?>
        </dl>

        <div class="saas-actions mt-1">
            <?php if ($publicUrl !== ''): ?>
                <a class="btn secondary" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">Ver loja pública</a>
                <button type="button" class="btn secondary" data-copy="<?= e($publicUrl) ?>">Copiar link</button>
            <?php endif; ?>
            <form method="post" action="/saas/tenants/<?= $id ?>/impersonar" class="form-inline">
                <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                <button type="submit" class="btn">Abrir painel</button>
            </form>
            <?php if (!$isSuspended): ?>
                <form method="post" action="/saas/tenants/<?= $id ?>/suspender" class="form-inline" data-confirm="Suspender <?= e((string) ($tenant['name'] ?? 'esta loja')) ?>? Agendamentos públicos e acesso ao painel serão bloqueados.">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn secondary danger">Suspender</button>
                </form>
            <?php else: ?>
                <form method="post" action="/saas/tenants/<?= $id ?>/reativar" class="form-inline">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn secondary">Reativar</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <h2>Dono da loja</h2>
        <?php if ($owner === null): ?>
            <p class="muted">Nenhum dono registado.</p>
        <?php else: ?>
        <dl class="detail-list">
            <dt>Nome</dt><dd><?= e((string) ($owner['name'] ?? '')) ?></dd>
            <dt>E-mail</dt><dd><?= e((string) ($owner['email'] ?? '')) ?></dd>
            <dt>Telefone</dt><dd><?= e((string) ($owner['phone'] ?? '—')) ?></dd>
            <dt>Conta</dt>
            <dd><?= (bool) ($owner['is_active'] ?? false) ? 'Ativa' : 'Inativa' ?></dd>
        </dl>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Plano e assinatura</h2>
        <dl class="detail-list">
            <dt>Plano atual</dt>
            <dd><?= e(is_array($plan) ? (string) ($plan['name'] ?? '—') : (string) ($tenant['plan'] ?? '—')) ?></dd>
            <?php if (is_array($plan) && isset($plan['monthly_price_cents'])): ?>
            <dt>Preço mensal</dt><dd><?= e(format_money_cents((int) $plan['monthly_price_cents'])) ?></dd>
            <?php endif; ?>
            <dt>Assinatura</dt>
            <dd><span class="pill <?= $sub === 'past_due' ? 'pill--danger' : ($sub === 'active' ? 'pill--ok' : '') ?>"><?= e(subscription_status_label($sub)) ?></span></dd>
            <?php if ($stripeCustomer !== null): ?>
            <dt>Stripe cliente</dt><dd><a href="<?= e($stripeCustomer) ?>" target="_blank" rel="noopener">Abrir no Stripe</a></dd>
            <?php endif; ?>
            <?php if ($stripeSub !== null): ?>
            <dt>Stripe assinatura</dt><dd><a href="<?= e($stripeSub) ?>" target="_blank" rel="noopener">Abrir no Stripe</a></dd>
            <?php endif; ?>
        </dl>

        <form method="post" action="/saas/tenants/<?= $id ?>/plano" class="saas-plan-form mt-1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label for="plan_definition_id">Alterar plano</label>
                <select id="plan_definition_id" name="plan_definition_id">
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int) ($p['id'] ?? 0) ?>"<?= (int) ($tenant['plan_definition_id'] ?? 0) === (int) ($p['id'] ?? 0) ? ' selected' : '' ?>>
                            <?= e((string) ($p['name'] ?? '')) ?> — <?= e(format_money_cents((int) ($p['monthly_price_cents'] ?? 0))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <label for="subscription_status">Status da assinatura</label>
                <select id="subscription_status" name="subscription_status">
                    <?php foreach (['none', 'trialing', 'active', 'past_due', 'canceled'] as $opt): ?>
                        <option value="<?= e($opt) ?>"<?= $sub === $opt ? ' selected' : '' ?>><?= e(subscription_status_label($opt)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Guardar plano</button>
        </form>
    </section>
</div>

<section class="card mt-1">
    <h2>Histórico de ações</h2>
    <?php if ($auditLogs === []): ?>
        <p class="muted">Sem registos para esta loja.</p>
    <?php else: ?>
    <div class="table-scroll">
        <table class="table table--compact">
            <thead>
            <tr><th>Quando</th><th>Quem</th><th>Ação</th></tr>
            </thead>
            <tbody>
            <?php foreach ($auditLogs as $log): ?>
                <tr>
                    <td class="muted"><?= e(format_datetime_in_tenant_tz((string) ($log['created_at'] ?? ''), 'America/Sao_Paulo')) ?></td>
                    <td><?= e((string) ($log['actor_name'] ?? '')) ?></td>
                    <td><?= e(saas_audit_action_label((string) ($log['action'] ?? ''))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$currentNav = 'tenants';
require __DIR__ . '/../layouts/saas.php';
