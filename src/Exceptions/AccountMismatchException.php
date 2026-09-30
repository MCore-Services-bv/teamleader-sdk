<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Exceptions;

/**
 * The OAuth flow connected a different Teamleader account than the
 * connection's `expected_account_id`.
 *
 * Usually the browser was logged into another Teamleader account while
 * connecting. Nothing was stored: log into the right account and connect again.
 */
class AccountMismatchException extends TeamleaderException
{
    public function __construct(
        public readonly string $connection,
        public readonly string $expectedAccountId,
        public readonly ?string $actualAccountId,
    ) {
        parent::__construct(
            "Connection '{$connection}' expects Teamleader account {$expectedAccountId}, but "
            .($actualAccountId === null ? 'the connected account could not be identified' : "account {$actualAccountId} was connected")
            .'. Nothing was stored. Log into the right Teamleader account in your browser and connect again.'
        );
    }
}
