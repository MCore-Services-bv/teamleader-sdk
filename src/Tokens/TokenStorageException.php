<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tokens;

use RuntimeException;

/**
 * The token store could not be read or written.
 *
 * Most often the teamleader_tokens table is missing or predates v3.0 — run
 * `php artisan migrate` — or the tokens were encrypted with an APP_KEY that is
 * no longer the application's key.
 */
final class TokenStorageException extends RuntimeException {}
