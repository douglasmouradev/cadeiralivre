<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ValidationException extends AppException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
