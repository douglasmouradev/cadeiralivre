<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $tenant */
/** @var array<string, mixed>|null $user */
/** @var bool $canManageTeam */
/** @var list<array<string, mixed>> $administrativeStaff */

$canManageTeam = $canManageTeam ?? false;
$administrativeStaff = $administrativeStaff ?? [];
$slug = is_array($tenant) ? (string) ($tenant['slug'] ?? '') : '';
$brandHex = tenant_brand_hex(is_array($tenant) ? (string) ($tenant['primary_color'] ?? '') : null);
$previewUrl = $slug !== '' ? '/agendar/' . rawurlencode($slug) : '#';

ob_start();
?>
<p class="mb-1 settings-actions">
    <a class="btn secondary" href="/configuracoes/assinatura">Assinatura e limites do plano</a>
    <?php if ($slug !== ''): ?>
        <a class="btn secondary" href="<?= e($previewUrl) ?>" target="_blank" rel="noopener">Ver página pública</a>
    <?php endif; ?>
</p>
<div class="grid two-col">
    <article class="card">
        <h3>Sua loja</h3>
        <form method="post" action="/configuracoes/tenant" data-validate="1" id="tenant-brand-form">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row"><label>Nome</label><input name="name" required value="<?= e((string) ($tenant['name'] ?? '')) ?>"></div>
            <div class="row"><label>Frase de destaque (página pública)</label><input name="public_tagline" maxlength="160" placeholder="Ex.: Nail design com atendimento exclusivo" value="<?= e((string) ($tenant['public_tagline'] ?? '')) ?>"></div>
            <div class="row"><label>E-mail</label><input name="email" type="email" required value="<?= e((string) ($tenant['email'] ?? '')) ?>"></div>
            <div class="row"><label>Telefone</label><input name="phone" value="<?= e((string) ($tenant['phone'] ?? '')) ?>"></div>
            <div class="row"><label>Instagram (URL ou @usuario)</label><input name="instagram_url" placeholder="https://instagram.com/sualoja" value="<?= e((string) ($tenant['instagram_url'] ?? '')) ?>"></div>
            <div class="row"><label>Endereço</label><input name="address" value="<?= e((string) ($tenant['address'] ?? '')) ?>"></div>
            <div class="grid-cols-2 mb-1">
                <div><label>Cidade</label><input name="city" value="<?= e((string) ($tenant['city'] ?? '')) ?>"></div>
                <div><label>Estado</label><input name="state" value="<?= e((string) ($tenant['state'] ?? '')) ?>"></div>
            </div>
            <div class="row color-picker-row">
                <label for="primary_color">Cor destaque</label>
                <div class="color-picker">
                    <input type="color" id="primary_color_picker" value="<?= e($brandHex) ?>" aria-label="Selecionar cor">
                    <input name="primary_color" id="primary_color" value="<?= e($brandHex) ?>" pattern="^#[0-9A-Fa-f]{6}$">
                </div>
            </div>
            <div class="brand-preview-pill" id="brand-preview" style="--preview-accent: <?= e($brandHex) ?>">
                <span class="brand-preview-pill__swatch"></span>
                Prévia da cor nos botões da página pública
            </div>
            <div class="row"><label>Fuso</label><input name="timezone" value="<?= e((string) ($tenant['timezone'] ?? 'America/Sao_Paulo')) ?>"></div>
            <button class="btn" type="submit">Salvar</button>
        </form>
        <form method="post" action="/configuracoes/logo" enctype="multipart/form-data" class="mt-1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row"><label>Logo (PNG/JPG/WebP)</label><input type="file" name="logo" accept="image/png,image/jpeg,image/webp"></div>
            <button class="btn secondary" type="submit">Enviar logo</button>
        </form>
        <form method="post" action="/configuracoes/capa" enctype="multipart/form-data" class="mt-1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row"><label>Capa da página pública</label><input type="file" name="cover" accept="image/png,image/jpeg,image/webp"></div>
            <p class="muted" style="font-size:0.88rem;margin:0 0 0.5rem">Recomendado: 1200×400 px ou proporção panorâmica.</p>
            <button class="btn secondary" type="submit">Enviar capa</button>
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
    <p class="muted mb-1">Crie contas para <strong>recepção</strong> ou outro <strong>administrador</strong> (co-dono com o mesmo acesso ao painel). Para atendimentos na agenda, use <a href="/barbeiros/novo">Profissionais → Novo</a>.</p>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
  const picker = document.getElementById('primary_color_picker');
  const text = document.getElementById('primary_color');
  const preview = document.getElementById('brand-preview');
  if (!picker || !text) return;
  const sync = (fromPicker) => {
    const v = fromPicker ? picker.value : text.value;
    if (/^#[0-9A-Fa-f]{6}$/.test(v)) {
      picker.value = v;
      text.value = v;
      if (preview) preview.style.setProperty('--preview-accent', v);
    }
  };
  picker.addEventListener('input', () => sync(true));
  text.addEventListener('input', () => sync(false));
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
