<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var string $title */
/** @var string $csrf */
/** @var list<array{id: int, name: string, slug: string, city: ?string, state: ?string}> $tenants */

$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($title) ?> — <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card auth-card--wide auth-card--login">
        <h1>Escolha a barbearia</h1>
        <p class="muted auth-card__intro">Selecione onde deseja agendar. Você será levado à página de agendamento já logado.</p>
        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <p class="alert alert-success" role="status"><?= e($flashSuccess) ?></p>
        <?php endif; ?>
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <p class="alert alert-error" role="alert"><?= e($flashError) ?></p>
        <?php endif; ?>
        <?php if ($tenants === []): ?>
            <p class="muted auth-card__intro">Nenhuma barbearia disponível no momento.</p>
        <?php else: ?>
            <ul class="tenant-pick-list">
            <?php foreach ($tenants as $t): ?>
                <?php
                $loc = [];
                if (!empty($t['city'])) {
                    $loc[] = (string) $t['city'];
                }
                if (!empty($t['state'])) {
                    $loc[] = (string) $t['state'];
                }
                $locStr = $loc !== [] ? implode(' — ', $loc) : '';
                ?>
                <li class="tenant-pick-list__item">
                    <form method="post" action="/primeiro-acesso/barbearias" class="tenant-pick-form">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="tenant_slug" value="<?= e((string) $t['slug']) ?>">
                        <div class="tenant-pick-form__text">
                            <strong><?= e((string) $t['name']) ?></strong>
                            <?php if ($locStr !== ''): ?>
                                <span class="muted"><?= e($locStr) ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn secondary" type="submit">Agendar aqui</button>
                    </form>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p class="muted mt-1 auth-card__links"><a href="/primeiro-acesso/recomecar">Corrigir meus dados</a> · <a href="/login">Login da equipe</a></p>
    </div>
    <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
