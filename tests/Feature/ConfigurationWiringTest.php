<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;

/**
 * v3.0 (B3): every key in config/teamleader.php is read by the SDK.
 *
 * Until 2.3 the published file carried sideloading, caching, development and
 * several rate-limiting, logging and error-handling keys that nothing read, and
 * `base_url`, `auth_url` and `api.retry_delay` were documented but ignored.
 * The dead keys are gone; the three that are useful are wired up.
 */
final class ConfigurationWiringTest extends TestCase
{
    // -- base_url / auth_url ---------------------------------------------------

    public function test_the_production_hosts_are_the_default(): void
    {
        $sdk = new TeamleaderSDK;

        $this->assertSame('https://api.focus.teamleader.eu', $this->property($sdk, 'baseUrl'));
        $this->assertStringStartsWith('https://focus.teamleader.eu/oauth2/authorize?', $sdk->getAuthorizationUrl());
    }

    public function test_base_url_and_auth_url_are_read_from_config(): void
    {
        config([
            'teamleader.base_url' => 'https://proxy.example.test/teamleader/',
            'teamleader.auth_url' => 'https://auth.example.test',
        ]);

        $sdk = new TeamleaderSDK;

        // A trailing slash is trimmed, so the endpoint path joins cleanly
        $this->assertSame('https://proxy.example.test/teamleader', $this->property($sdk, 'baseUrl'));
        $this->assertStringStartsWith('https://auth.example.test/oauth2/authorize?', $sdk->getAuthorizationUrl());
    }

    public function test_an_empty_host_falls_back_to_production(): void
    {
        config(['teamleader.base_url' => '', 'teamleader.auth_url' => null]);

        $sdk = new TeamleaderSDK;

        $this->assertSame('https://api.focus.teamleader.eu', $this->property($sdk, 'baseUrl'));
        $this->assertSame('https://focus.teamleader.eu', $this->property($sdk, 'authUrl'));
    }

    // -- api.retry_delay -------------------------------------------------------

    public function test_retry_delay_is_the_backoff_base(): void
    {
        config(['teamleader.api.retry_delay' => 250]);

        // attempt 1 → base, attempt 3 → base × 4; up to 100 ms jitter on top
        $this->assertDelayBetween(250, 350, 1);
        $this->assertDelayBetween(1000, 1100, 3);
    }

    public function test_retry_delay_defaults_to_one_second(): void
    {
        config(['teamleader.api.retry_delay' => null]);

        $this->assertDelayBetween(1000, 1100, 1);
    }

    public function test_retry_delay_is_capped_at_thirty_seconds(): void
    {
        config(['teamleader.api.retry_delay' => 20000]);

        $this->assertDelayBetween(30000, 30000, 3);
    }

    // -- removed keys ----------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function removedKeys(): array
    {
        return array_combine($keys = [
            'sideloading',
            'caching',
            'development',
            'rate_limiting.requests_per_minute',
            'rate_limiting.throttle_threshold',
            'rate_limiting.aggressive_throttling',
            'rate_limiting.respect_retry_after',
            'logging.enabled',
            'logging.sanitize_logs',
            'logging.log_rate_limits',
            'logging.log_token_refresh',
            'error_handling.log_errors',
            'error_handling.include_stack_trace',
            'error_handling.parse_teamleader_errors',
        ], array_map(fn ($key) => [$key], $keys));
    }

    #[DataProvider('removedKeys')]
    public function test_the_published_config_no_longer_has(string $key): void
    {
        $published = require __DIR__.'/../../config/teamleader.php';

        $this->assertFalse($this->hasKey($published, $key), "config/teamleader.php still declares {$key}.");
    }

    // -- helpers ---------------------------------------------------------------

    private function assertDelayBetween(int $min, int $max, int $attempt): void
    {
        $method = new ReflectionMethod(TeamleaderErrorHandler::class, 'calculateRetryDelay');
        $delay = $method->invoke(new TeamleaderErrorHandler, new TeamleaderException('test'), $attempt);

        $this->assertGreaterThanOrEqual($min, $delay);
        $this->assertLessThanOrEqual($max, $delay);
    }

    private function property(object $object, string $name): mixed
    {
        return (new ReflectionProperty($object, $name))->getValue($object);
    }

    private function hasKey(array $config, string $key): bool
    {
        $segments = explode('.', $key);
        $last = array_pop($segments);

        foreach ($segments as $segment) {
            if (! is_array($config[$segment] ?? null)) {
                return false;
            }

            $config = $config[$segment];
        }

        return array_key_exists($last, $config);
    }
}
