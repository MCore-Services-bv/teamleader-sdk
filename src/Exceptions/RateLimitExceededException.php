<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Exceptions;

class RateLimitExceededException extends TeamleaderException
{
    protected int $retryAfter;

    protected ?int $resetTime;

    public function __construct(
        string $message = '',
        int $retryAfter = 60,
        ?int $resetTime = null,
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

    public function getResetTime(): ?int
    {
        return $this->resetTime;
    }
}
