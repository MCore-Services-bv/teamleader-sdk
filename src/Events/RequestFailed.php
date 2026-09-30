<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

use Throwable;

/**
 * A request to the Teamleader API did not succeed.
 *
 * Fired for an error status from the API, for a request that got no answer,
 * for a request that could not be sent because there is no access token, and
 * when the rate-limit wait runs out. It fires whether or not
 * `throw_exceptions` is on, so a listener sees every failure either way.
 */
final readonly class RequestFailed
{
    /**
     * @param  string  $method  HTTP method, e.g. `POST`
     * @param  string  $endpoint  e.g. `companies.list`
     * @param  int|null  $statusCode  HTTP status; null when the API was not reached
     * @param  string  $message  The first error Teamleader reported, or why the
     *                           request failed
     * @param  Throwable|null  $exception  Set when the failure raised one: a
     *                                     transport error, or the rate-limit wait
     *                                     running out
     */
    public function __construct(
        public string $method,
        public string $endpoint,
        public ?int $statusCode,
        public string $message,
        public ?Throwable $exception = null,
    ) {}
}
