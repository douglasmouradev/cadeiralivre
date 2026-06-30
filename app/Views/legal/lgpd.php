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
    <p>Para dados tratados <strong>no âmbito de uma barbearia</strong> (clientes, agendamentos), o responsável pelo tratamento é o dono da barbearia. Utilize os contatos da barbearia ou o suporte indicado por ela.</p>
    <p>Para dados da <strong>conta de acesso à plataforma</strong> (operador do SaaS), pode solicitar pelo canal de suporte do serviço: acesso, correção ou exclusão, conforme aplicável.</p>
    <p>Donos de barbearia podem exportar lista de clientes em <strong>Clientes → Exportar</strong> quando disponível, para portabilidade interna.</p>
    <p class="mt-1"><a href="/privacidade">Política de privacidade</a> · <a href="/login">Login</a></p>
    <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
</div>
</body>
</html>
