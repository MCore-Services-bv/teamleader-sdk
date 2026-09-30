<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

/**
 * A request is about to be sent to the Teamleader API.
 *
 * Fired once per attempt: a request that is retried after a server error fires
 * this again. Nothing is fired for a request the SDK refuses to send (a
 * client-side validation error, or no access token — see RequestFailed).
 */
final readonly class RequestSending
{
    /**
     * @param  string  $method  HTTP method, e.g. `POST`
     * @param  string  $endpoint  e.g. `companies.list`
     * @param  array<mixed>  $body  The request body, with tokens, secrets and
     *                              other sensitive keys redacted
     * @param  string  $connection  The Teamleader connection, `default` for a single account
     */
    public function __construct(
        public string $method,
        public string $endpoint,
        public array $body,
        public string $connection = 'default',
    ) {}
}
