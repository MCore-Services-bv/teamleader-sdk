<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Exceptions;

/**
 * The OAuth callback's `state` does not match one this session started.
 *
 * Either the callback was forged — another site trying to connect your
 * application to its own Teamleader account — or the session expired between
 * authorize() and the callback. Nothing was stored. Start again from
 * authorize().
 */
class OAuthStateException extends TeamleaderException {}
