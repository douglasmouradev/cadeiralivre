<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\Controller;
use App\Exceptions\AppException;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Middleware\MiddlewareStack;
use Closure;
use Throwable;

final class Application
{
    private Router $router;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly string $root,
        private readonly array $config,
    ) {
        $this->router = new Router();
        $routesFile = $this->root . '/routes.php';
        if (!is_file($routesFile)) {
            throw new \RuntimeException('Arquivo routes.php não encontrado.');
        }
        $register = require $routesFile;
        if (!is_callable($register)) {
            throw new \RuntimeException('routes.php deve retornar um callable.');
        }
        $register($this->router);
    }

    public function root(): string
    {
        return $this->root;
    }

    /** @return array<string, mixed> */
    public function config(): array
    {
        return $this->config;
    }

    public function handle(Request $request): Response
    {
        $dispatch = function (
            Request $req,
            string $handler,
            array $middlewareNames,
            array $params,
        ): Response {
            $stack = new MiddlewareStack($this, $middlewareNames, function (Request $r) use ($handler, $params): Response {
                return $this->callController($r, $handler, $params);
            });

            return $stack->handle($req);
        };

        try {
            return $this->router->dispatch($request, $dispatch);
        } catch (NotFoundException $e) {
            return Response::html($this->renderError(404, $e->getMessage()), 404);
        } catch (HttpException $e) {
            return Response::html($this->renderError($e->statusCode(), $e->getMessage()), $e->statusCode());
        } catch (AppException $e) {
            return Response::html($this->renderError(400, $e->getMessage()), 400);
        } catch (Throwable $e) {
            $debug = (bool) ($this->config['debug'] ?? false);
            $msg = $debug ? $e->getMessage() : 'Erro interno do servidor.';

            return Response::html($this->renderError(500, $msg), 500);
        }
    }

    /** @param array<string, mixed> $params */
    private function callController(Request $request, string $handler, array $params): Response
    {
        if (!str_contains($handler, '@')) {
            throw new \InvalidArgumentException('Handler inválido: ' . $handler);
        }
        [$class, $action] = explode('@', $handler, 2);
        $fqcn = 'App\\Controllers\\' . $class;
        if (!class_exists($fqcn)) {
            throw new NotFoundException('Controlador não encontrado.');
        }
        $controller = new $fqcn($this, $request);
        if (!$controller instanceof Controller) {
            throw new \RuntimeException('Controlador deve estender Controller.');
        }
        if (!method_exists($controller, $action)) {
            throw new NotFoundException('Ação não encontrada.');
        }

        return $controller->{$action}(...array_values($params));
    }

    private function renderError(int $code, string $message): string
    {
        $h = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $title = match ($code) {
            404 => 'Não encontrado',
            403 => 'Acesso negado',
            419 => 'Sessão expirada',
            default => 'Erro',
        };

        return '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>' . $title . '</title>'
            . '<style>body{font-family:system-ui,sans-serif;background:#f2efe8;color:#1c1917;padding:2rem;line-height:1.5}a{color:#8b6914}</style></head><body>'
            . '<h1>' . $title . '</h1><p>' . $h . '</p><p><a href="/">Voltar</a></p></body></html>';
    }
}
