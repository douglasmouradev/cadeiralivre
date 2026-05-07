<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /**
     * @param array<string, string|array<int, string>> $headers
     */
    public function __construct(
        private int $status = 200,
        private array $headers = [],
        private string $body = '',
    ) {
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html; charset=UTF-8'], $html);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self($status, ['Location' => $url], '');
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function csv(string $filename, string $content): self
    {
        return new self(200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], $content);
    }

    public static function file(string $absolutePath, string $mime): self
    {
        if (!is_readable($absolutePath)) {
            return new self(404, ['Content-Type' => 'text/plain'], 'Não encontrado');
        }
        $body = file_get_contents($absolutePath);

        return new self(200, ['Content-Type' => $mime], is_string($body) ? $body : '');
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header($name . ': ' . $v);
                }
            } else {
                header($name . ': ' . $value);
            }
        }
        echo $this->body;
    }

    public function body(): string
    {
        return $this->body;
    }
}
