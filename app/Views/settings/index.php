<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $tenant */
/** @var array<string, mixed>|null $user */

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
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
