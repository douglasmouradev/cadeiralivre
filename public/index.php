<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/bootstrap.php';

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

$config = require $root . '/config/app.php';

$app = new Application($root, $config);
$request = Request::fromGlobals();

$csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com 'self'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
header('Content-Security-Policy: ' . $csp);

$response = $app->handle($request);
$response->send();
