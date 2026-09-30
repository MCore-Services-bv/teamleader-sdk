<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

/**
 * The Teamleader API answered — with any status, success or not.
 *
 * An error status also fires RequestFailed, after this event. A request that
 * never got an answer (a connection failure or timeout) fires only
 * RequestFailed.
 */
final readonly class ResponseReceived
{
    /**
     * @param  string  $method  HTTP method, e.g. `POST`
     * @param  string  $endpoint  e.g. `companies.list`
     * @param  int  $statusCode  HTTP status
     * @param  float  $durationMs  From sending the request to reading the body
     * @param  array<mixed>|null  $body  The decoded response body, with sensitive
     *                                   keys redacted; null when it was not JSON
     */
    public function __construct(
        public string $method,
        public string $endpoint,
        public int $statusCode,
        public float $durationMs,
        public ?array $body = null,
    ) {}

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
