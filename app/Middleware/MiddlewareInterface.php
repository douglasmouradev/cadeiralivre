<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

interface MiddlewareInterface
{
    /** @param callable(Request): Response $next */
    public function handle(Application $app, Request $request, callable $next): Response;
}
