<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Exceptions;

use Exception;

/**
 * Base Teamleader SDK exception
 */
class TeamleaderException extends Exception
{
    protected array $context;
    protected ?int $statusCode;
    protected array $errors;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = [],
        ?int $statusCode = null,
        array $errors = []
    ) {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getAllErrors(): array
    {
        return empty($this->errors) ? [$this->getMessage()] : $this->errors;
    }
}
