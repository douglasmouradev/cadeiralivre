<?php

declare(strict_types=1);

namespace App\Exceptions;

final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Não autorizado.')
    {
        parent::__construct($message, 403);
    }
}
