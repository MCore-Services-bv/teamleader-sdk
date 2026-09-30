<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

/**
 * Refreshing the access token failed.
 *
 * When `$reauthorizationRequired` is true the refresh token itself was
 * refused — revoked, expired or missing — and the stored tokens were cleared.
 * Nothing recovers from that without a person connecting the account again
 * through OAuth, so it is the one event worth alerting on.
 *
 * Carries no token values.
 */
final readonly class TokenRefreshFailed
{
    /**
     * @param  string  $reason  What went wrong
     * @param  int|null  $statusCode  HTTP status from the token endpoint; null
     *                                when it was not reached or not called
     * @param  bool  $reauthorizationRequired  True when the account has to be
     *                                         connected again
     * @param  string  $connection  The Teamleader connection, `default` for a single account
     */
    public function __construct(
        public string $reason,
        public ?int $statusCode = null,
        public bool $reauthorizationRequired = false,
        public string $connection = 'default',
    ) {}
}
