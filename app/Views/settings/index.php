<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $tenant */
/** @var array<string, mixed>|null $user */
/** @var bool $canManageTeam */
/** @var list<array<string, mixed>> $administrativeStaff */

$canManageTeam = $canManageTeam ?? false;
$administrativeStaff = $administrativeStaff ?? [];

ob_start();
?>
<div class="grid two-col">
    <article class="card">
        <h3>Barbearia</h3>
        <form method="post" action="/configuracoes/tenant" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row"><label>Nome</label><input name="name" required value="<?= e((string) ($tenant['name'] ?? '')) ?>"></div>
            <div class="row"><label>E-mail</label><input name="email" type="email" required value="<?= e((string) ($tenant['email'] ?? '')) ?>"></div>
            <div class="row"><label>Telefone</label><input name="phone" value="<?= e((string) ($tenant['phone'] ?? '')) ?>"></div>
            <div class="row"><label>Endereço</label><input name="address" value="<?= e((string) ($tenant['address'] ?? '')) ?>"></div>
            <div class="grid-cols-2 mb-1">
                <div><label>Cidade</label><input name="city" value="<?= e((string) ($tenant['city'] ?? '')) ?>"></div>
                <div><label>Estado</label><input name="state" value="<?= e((string) ($tenant['state'] ?? '')) ?>"></div>
            </div>
            <div class="row"><label>Cor destaque</label><input name="primary_color" value="<?= e((string) ($tenant['primary_color'] ?? '#D4AF37')) ?>"></div>
            <div class="row"><label>Fuso</label><input name="timezone" value="<?= e((string) ($tenant['timezone'] ?? 'America/Sao_Paulo')) ?>"></div>
            <button class="btn" type="submit">Salvar</button>
        </form>
        <form method="post" action="/configuracoes/logo" enctype="multipart/form-data" class="mt-1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row"><label>Logo (PNG/JPG/WebP)</label><input type="file" name="logo" accept="image/png,image/jpeg,image/webp"></div>
            <button class="btn secondary" type="submit">Enviar logo</button>
        </form>
    </article>
    <article class="card">
        <h3>Seu perfil</h3>
        <form method="post" action="/configuracoes/perfil" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row"><label>Nome</label><input name="name" required value="<?= e((string) ($user['name'] ?? '')) ?>"></div>
            <div class="row"><label>Telefone</label><input name="phone" value="<?= e((string) ($user['phone'] ?? '')) ?>"></div>
            <button class="btn" type="submit">Salvar perfil</button>
        </form>
        <form method="post" action="/configuracoes/avatar" enctype="multipart/form-data" class="mt-1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row"><label>Foto</label><input type="file" name="avatar" accept="image/png,image/jpeg,image/webp"></div>
            <button class="btn secondary" type="submit">Enviar foto</button>
        </form>
    </article>
</div>
<?php if ($canManageTeam): ?>
<article class="card mt-1">
    <h3>Equipe administrativa</h3>
    <p class="muted mb-1">Crie contas para <strong>recepção</strong> ou outro <strong>administrador</strong> (co-dono com o mesmo acesso ao painel). Para cortes na agenda, use <a href="/barbeiros/novo">Barbeiros → Novo</a>.</p>
    <?php if ($administrativeStaff !== []): ?>
    <table class="table mb-1">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Função</th><th>Ativo</th></tr></thead>
        <tbody>
        <?php foreach ($administrativeStaff as $row): ?>
            <tr>
                <td><?= e((string) ($row['name'] ?? '')) ?></td>
                <td><?= e((string) ($row['email'] ?? '')) ?></td>
                <td><?= e(match ((string) ($row['role'] ?? '')) {
                    'owner' => 'Administrador',
                    'receptionist' => 'Recepcionista',
                    default => (string) ($row['role'] ?? ''),
                }) ?></td>
                <td><?= ((int) ($row['is_active'] ?? 0) === 1) ? 'Sim' : 'Não' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <h4 class="mt-1">Novo utilizador</h4>
    <form method="post" action="/configuracoes/equipe" data-validate="1">
        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
        <div class="grid-cols-2 mb-1">
            <div class="row"><label for="staff_name">Nome</label><input id="staff_name" name="staff_name" required autocomplete="name"></div>
            <div class="row"><label for="staff_email">E-mail (login)</label><input id="staff_email" name="staff_email" type="text" inputmode="email" autocomplete="email" required></div>
        </div>
        <div class="grid-cols-2 mb-1">
            <div class="row"><label for="staff_password">Senha inicial</label><input id="staff_password" name="staff_password" type="password" required minlength="8" autocomplete="new-password"></div>
            <div class="row"><label for="staff_role">Função</label>
                <select id="staff_role" name="staff_role" required>
                    <option value="receptionist">Recepcionista</option>
                    <option value="owner">Administrador (co-dono)</option>
                </select>
            </div>
        </div>
        <button class="btn" type="submit">Criar utilizador</button>
    </form>
</article>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
