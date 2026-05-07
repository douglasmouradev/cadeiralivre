<?php

declare(strict_types=1);

namespace App\Exceptions;

class HttpException extends AppException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
