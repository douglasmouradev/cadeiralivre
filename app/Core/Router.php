<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;
use Closure;

final class Router
{
    /** @var list<array{methods: list<string>, pattern: string, regex: string, paramNames: list<string>, handler: string, middleware: list<string>}> */
    private array $routes = [];

    /**
     * @param list<string>|string $methods
     * @param list<string> $middleware
     */
    public function add(array|string $methods, string $pattern, string $handler, array $middleware = []): void
    {
        $methodList = is_array($methods) ? $methods : [$methods];
        $methodList = array_map(strtoupper(...), $methodList);

        $paramNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m) use (&$paramNames): string {
            $paramNames[] = $m[1];

            return '([^/]+)';
        }, $pattern);

        if (!is_string($regex)) {
            throw new \InvalidArgumentException('Padrão de rota inválido.');
        }

        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'methods' => $methodList,
            'pattern' => $pattern,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * @param Closure(Request): Response $fallback
     */
    public function dispatch(Request $request, Closure $fallback): Response
    {
        $path = $request->path();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($route['paramNames'] as $i => $name) {
                $params[$name] = $matches[$i + 1] ?? null;
            }

            return $fallback($request, $route['handler'], $route['middleware'], $params);
        }

        throw new NotFoundException('Página não encontrada.');
    }
}
