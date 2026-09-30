<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

/**
 * A Teamleader account was connected through OAuth and its tokens stored.
 *
 * Carries no token values.
 */
final readonly class ConnectionAuthorized
{
    /**
     * @param  string  $connection  The connection that was authorised
     * @param  string|null  $accountId  The Teamleader account id, from users.me
     * @param  string|null  $accountName  The account's first department name
     */
    public function __construct(
        public string $connection,
        public ?string $accountId,
        public ?string $accountName,
    ) {}
}
