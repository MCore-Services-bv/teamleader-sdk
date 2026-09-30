<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

/**
 * The access token was refreshed and the new token pair stored.
 *
 * Carries no token values.
 */
final readonly class TokenRefreshed
{
    /**
     * @param  int|null  $expiresIn  Lifetime of the new access token in seconds,
     *                               as Teamleader reported it
     * @param  string  $connection  The Teamleader connection, `default` for a single account
     */
    public function __construct(
        public ?int $expiresIn = null,
        public string $connection = 'default',
    ) {}
}
