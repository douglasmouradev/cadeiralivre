<?php

declare(strict_types=1);

/** @var string $csrf */

ob_start();
?>
<section class="card" style="max-width: 520px;">
    <p class="muted mb-1">Cria uma nova loja na plataforma com dono e credenciais de acesso.</p>
    <form method="post" action="/saas/loja/nova" data-validate="1">
        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
        <div class="row">
            <label for="shop_name">Nome da loja</label>
            <input id="shop_name" name="shop_name" required>
        </div>
        <div class="row">
            <label for="slug">Identificador público (URL)</label>
            <input id="slug" name="slug" required pattern="[a-z0-9-]+" title="apenas minúsculas, números e hífen">
        </div>
        <div class="row">
            <label for="owner_name">Nome do dono</label>
            <input id="owner_name" name="owner_name" required>
        </div>
        <div class="row">
            <label for="email">E-mail do dono</label>
            <input id="email" name="email" type="email" required>
        </div>
        <div class="row">
            <label for="phone">Telefone</label>
            <input id="phone" name="phone">
        </div>
        <div class="row">
            <label for="password">Senha inicial (mín. 8)</label>
            <input id="password" name="password" type="password" minlength="8" required>
        </div>
        <button class="btn" type="submit">Criar loja</button>
    </form>
</section>
<?php
$content = ob_get_clean();
$currentNav = 'new_tenant';
require __DIR__ . '/../layouts/saas.php';
