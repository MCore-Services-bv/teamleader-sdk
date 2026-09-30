<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Exceptions;

/**
 * Teamleader refused this connection's refresh token — it was revoked, or
 * expired unused — so nothing can be sent until a person connects the account
 * again through OAuth.
 *
 * Thrown for every request on such a connection, whatever
 * `throw_exceptions` says, instead of the generic 401 each request would
 * otherwise produce. Extends AuthenticationException, so existing handlers for
 * that still catch it.
 */
class ConnectionNeedsReauthorizationException extends AuthenticationException
{
    public function __construct(public readonly string $connection)
    {
        parent::__construct(
            "Teamleader connection '{$connection}' needs to be connected again: Teamleader refused its refresh token. "
            .'Send a user through authorize() for this connection. Until then, no request is sent.',
            401,
            null,
            ['connection' => $connection],
            401
        );
    }
}
