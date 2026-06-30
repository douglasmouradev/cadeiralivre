<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $description */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?> — <?= e(app_name()) ?></title>
    <?php require __DIR__ . '/../partials/site_favicons.php'; ?>
    <?php require __DIR__ . '/../partials/legal_head.php'; ?>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-page">
<div class="auth-card auth-card--login" style="max-width:40rem;text-align:left">
    <h1><?= e($title) ?></h1>
    <p class="muted">Última atualização: <?= e((string) date('Y-m-d')) ?>.</p>
    <p>Ao utilizar <?= e(app_name()) ?>, a equipe da barbearia e os clientes aceitam utilizar o serviço de forma lícita, sem abuso da plataforma nem tentativas de acesso não autorizado.</p>
    <p>O serviço é fornecido “no estado em que se encontra”. O operador da plataforma não se responsabiliza por indisponibilidades causadas por terceiros (hospedagem, DNS, e-mail).</p>
    <p>Planos pagos, quando ativados, estão sujeitos aos preços e condições comunicados no momento da assinatura e à lei aplicável no Brasil.</p>
    <p class="mt-1"><a href="/login">Voltar ao login</a> · <a href="/privacidade">Privacidade</a></p>
    <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
</div>
</body>
</html>
