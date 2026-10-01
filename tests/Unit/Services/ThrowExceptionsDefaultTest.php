<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\Tests\TestCase;

/**
 * v3.0 (B2): failures throw by default; `false` keeps the 2.x error arrays.
 */
final class ThrowExceptionsDefaultTest extends TestCase
{
    private const NOT_FOUND = ['error' => true, 'status_code' => 404, 'message' => 'Not found'];

    public function test_the_published_config_defaults_to_true(): void
    {
        // env() reads $_ENV and $_SERVER before getenv(): clear all three, so
        // a TEAMLEADER_THROW_EXCEPTIONS set in the shell cannot decide this
        $key = 'TEAMLEADER_THROW_EXCEPTIONS';
        $saved = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);

        try {
            $config = require __DIR__.'/../../../config/teamleader.php';
        } finally {
            [$env, $server, $process] = $saved;
            $env === null ?: $_ENV[$key] = $env;
            $server === null ?: $_SERVER[$key] = $server;
            $process === false ?: putenv("{$key}={$process}");
        }

        $this->assertTrue($config['error_handling']['throw_exceptions']);
    }

    public function test_a_truthy_env_value_turns_exceptions_on(): void
    {
        config(['teamleader.error_handling.throw_exceptions' => '1']);

        $this->expectException(NotFoundException::class);

        (new TeamleaderErrorHandler)->handleApiError(self::NOT_FOUND);
    }

    public function test_an_unset_key_throws(): void
    {
        config(['teamleader.error_handling' => []]);

        $this->expectException(NotFoundException::class);

        (new TeamleaderErrorHandler)->handleApiError(self::NOT_FOUND);
    }

    public function test_false_returns_the_error_array_as_in_2x(): void
    {
        config(['teamleader.error_handling.throw_exceptions' => false]);

        (new TeamleaderErrorHandler)->handleApiError(self::NOT_FOUND);

        $this->addToAssertionCount(1);
    }
}
