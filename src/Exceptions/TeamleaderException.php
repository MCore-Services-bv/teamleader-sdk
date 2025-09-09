<?php

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

/**
 * Authentication failed exception
 */
class AuthenticationException extends TeamleaderException {}

/**
 * Authorization/permission denied exception
 */
class AuthorizationException extends TeamleaderException {}

/**
 * Resource not found exception
 */
class NotFoundException extends TeamleaderException {}

/**
 * Validation failed exception
 */
class ValidationException extends TeamleaderException {}

/**
 * Rate limit exceeded exception
 */
class RateLimitExceededException extends TeamleaderException
{
    protected int $retryAfter;
    protected ?string $resetTime;

    public function __construct(
        string $message = '',
        int $retryAfter = 60,
        ?string $resetTime = null,
        array $context = []
    ) {
        parent::__construct($message, 429, null, $context, 429);
        $this->retryAfter = $retryAfter;
        $this->resetTime = $resetTime;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    public function getResetTime(): ?string
    {
        return $this->resetTime;
    }
}

/**
 * Server error exception (5xx responses)
 */
class ServerException extends TeamleaderException {}

/**
 * Network/connection exception
 */
class ConnectionException extends TeamleaderException {}

/**
 * Configuration error exception
 */
class ConfigurationException extends TeamleaderException {}
