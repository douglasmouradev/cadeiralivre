<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $appointment */
/** @var string $token */
/** @var string $csrf */
/** @var array<string, mixed>|null $tenant */

$brandHex = tenant_brand_hex(is_array($tenant ?? null) ? (string) ($tenant['primary_color'] ?? '') : null);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body public-theme" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-centered">
    <div class="auth-card">
        <?php if ($appointment === null): ?>
            <h1>Link inválido</h1>
            <p class="muted">Somente agendamentos concluídos podem ser avaliados.</p>
        <?php else: ?>
            <h1>Avaliar atendimento</h1>
            <p class="muted">Sua opinião ajuda a barbearia a melhorar.</p>
            <form method="post" action="/avaliar" data-validate="1">
                <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="row">
                    <label for="rating">Nota (1 a 5)</label>
                    <select id="rating" name="rating" required>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>"><?= $i ?> estrelas</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="row">
                    <label for="comment">Comentário</label>
                    <textarea id="comment" name="comment" rows="3" placeholder="Opcional"></textarea>
                </div>
                <div class="form-actions mt-1">
                    <button class="btn" type="submit">Enviar</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
<div id="toast-root" class="toast-root" aria-live="polite"></div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
