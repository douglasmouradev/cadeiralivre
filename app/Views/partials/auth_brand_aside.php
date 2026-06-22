<?php

declare(strict_types=1);

/** @var string|null $authAsideTitle */
/** @var string|null $authAsideLead */

$asideTitle = $authAsideTitle ?? 'Sua cadeira. Seu horário. Online.';
$asideLead = $authAsideLead ?? 'Agendamento, portal do cliente e painel completo — feito para barbearias, nails e salões.';
?>
<aside class="auth-shell__brand" aria-hidden="false">
    <a href="/" class="auth-shell__brand-link">
        <img src="/assets/img/cadeiralivre-logo.png" width="72" height="72" alt="<?= e(app_name()) ?>">
        <span class="auth-shell__brand-name"><?= e(app_name()) ?></span>
    </a>
    <h2 class="auth-shell__brand-title"><?= e($asideTitle) ?></h2>
    <p class="auth-shell__brand-lead"><?= e($asideLead) ?></p>
    <ul class="auth-shell__brand-points">
        <li>Link público de agendamento</li>
        <li>Lembretes por WhatsApp</li>
        <li>14 dias grátis para testar</li>
    </ul>
</aside>
