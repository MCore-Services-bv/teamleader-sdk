<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use Throwable;

/**
 * One row that was refused — by validation before sending, or by the API.
 */
final readonly class BulkFailure
{
    /**
     * @param  int|string  $index  The row's key in the input
     * @param  mixed  $payload  The row as given
     * @param  bool  $beforeSending  True when validation refused it and nothing was sent
     */
    public function __construct(
        public int|string $index,
        public mixed $payload,
        public Throwable $exception,
        public bool $beforeSending = false,
    ) {}

    public function message(): string
    {
        return $this->exception->getMessage();
    }

    /** The HTTP status, when the API answered */
    public function statusCode(): ?int
    {
        return $this->exception instanceof TeamleaderException ? $this->exception->getStatusCode() : null;
    }
}
