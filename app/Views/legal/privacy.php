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
    <p>O <?= e(app_name()) ?> trata dados pessoais para prestar o serviço de agendamento (identificação de contas, marcação de horários, comunicações por e-mail). Conservamos os dados enquanto a conta estiver ativa ou conforme obrigação legal.</p>
    <p><strong>Base legal:</strong> execução de contrato e legítimo interesse em operar a plataforma em segurança.</p>
    <p><strong>Seus direitos (LGPD):</strong> confirmação de tratamento, acesso, correção, anonimização, portabilidade, eliminação e informação sobre compartilhamentos. Para exercê-los, entre em contato com o responsável pela barbearia (controlador do respectivo tenant) ou o suporte da plataforma.</p>
    <p><strong>Cookies e sessão:</strong> utilizamos cookies de sessão necessários à autenticação e proteção CSRF.</p>
    <p class="mt-1"><a href="/login">Voltar ao login</a></p>
    <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
</div>
</body>
</html>
