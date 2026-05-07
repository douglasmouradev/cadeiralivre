<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

final class MiddlewareStack
{
    /** @param list<string> $middlewareNames */
    public function __construct(
        private readonly Application $app,
        private readonly array $middlewareNames,
        private $core,
    ) {
    }

    public function handle(Request $request): Response
    {
        $pipeline = $this->core;
        $names = array_reverse($this->middlewareNames);

        $app = $this->app;
        foreach ($names as $name) {
            $class = 'App\\Middleware\\' . $name;
            if (!class_exists($class)) {
                throw new \RuntimeException('Middleware não encontrado: ' . $name);
            }
            $instance = new $class();
            if (!$instance instanceof MiddlewareInterface) {
                throw new \RuntimeException('Middleware inválido: ' . $name);
            }
            $pipeline = static function (Request $req) use ($instance, $app, $pipeline): Response {
                return $instance->handle($app, $req, $pipeline);
            };
        }

        return $pipeline($request);
    }
}
