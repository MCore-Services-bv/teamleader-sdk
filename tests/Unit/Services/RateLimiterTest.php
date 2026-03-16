<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Rate limiter tests.
 *
 * These tests require a Redis connection. The test suite uses the 'default'
 * Redis connection configured in config/database.php. In CI this resolves to
 * the Redis service defined in .github/workflows. Locally, ensure Redis is
 * running on 127.0.0.1:6379 or set REDIS_HOST in your .env.testing.
 *
 * Each test calls reset() in setUp() to flush the shared Redis keys, so tests
 * are isolated even though they share a Redis instance.
 *
 * DB 15 is used to avoid colliding with application data in DB 0.
 */
#[Group('redis')]
class RateLimiterTest extends TestCase
{
    private ApiRateLimiterService $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new ApiRateLimiterService;
        // Flush shared Redis state so each test starts clean
        $this->rateLimiter->reset();
    }

    public function test_allows_requests_within_limit(): void
    {
        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertTrue($result['can_proceed']);
        $this->assertEquals(0, $result['delay_applied']);
    }

    public function test_records_requests(): void
    {
        $stats = $this->rateLimiter->getStatistics();
        $initialCount = $stats['total_requests'];

        $this->rateLimiter->recordRequest();

        $stats = $this->rateLimiter->getStatistics();
        $this->assertEquals($initialCount + 1, $stats['total_requests']);
    }

    public function test_calculates_usage_percentage(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $stats = $this->rateLimiter->getStatistics();
        $this->assertGreaterThan(0, $stats['usage_percentage']);
        $this->assertLessThanOrEqual(100, $stats['usage_percentage']);
    }

    public function test_sliding_window_tracks_requests_in_redis(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $stats = $this->rateLimiter->getStatistics();
        $this->assertEquals(20, $stats['current_usage']);
        $this->assertEquals(180, $stats['remaining']);
    }

    public function test_reset_clears_redis_state(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $this->rateLimiter->reset();

        $stats = $this->rateLimiter->getStatistics();
        $this->assertEquals(0, $stats['current_usage']);
        $this->assertEquals(200, $stats['remaining']);
        $this->assertEquals(0, $stats['total_requests']);
    }

    public function test_respects_rate_limit_headers(): void
    {
        // getStatistics() derives 'remaining' from the live sorted set, not from
        // the header value stored in Redis. After recording 50 requests:
        // current_usage = 50, remaining = 200 - 50 = 150.
        // The header value (50) is stored in Redis for cross-process awareness
        // but does not override the calculated remaining in getStatistics().
        for ($i = 0; $i < 50; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $headers = [
            'X-RateLimit-Remaining' => ['50'],
            'X-RateLimit-Limit' => ['200'],
        ];

        $this->rateLimiter->updateFromResponseHeaders($headers);

        $stats = $this->rateLimiter->getStatistics();
        $this->assertEquals(150, $stats['remaining']);
    }

    public function test_is_throttled_above_70_percent(): void
    {
        // Below 70% — not throttled
        for ($i = 0; $i < 100; $i++) {
            $this->rateLimiter->recordRequest();
        }
        $this->assertFalse($this->rateLimiter->isThrottled());

        // At 70% — throttled
        for ($i = 0; $i < 40; $i++) {
            $this->rateLimiter->recordRequest();
        }
        $this->assertTrue($this->rateLimiter->isThrottled());
    }

    public function test_applies_progressive_throttle_delay(): void
    {
        // Record 160 requests (80% usage) to trigger medium throttling
        for ($i = 0; $i < 160; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertTrue($result['can_proceed']);
        $this->assertGreaterThanOrEqual(500, $result['delay_applied']);
    }

    public function test_handle_429_clears_window_and_stores_reset_time(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $retryAfter = $this->rateLimiter->handle429Response([
            'Retry-After' => ['30'],
        ]);

        $this->assertEquals(30, $retryAfter);

        // Sorted set should be cleared
        $stats = $this->rateLimiter->getStatistics();
        $this->assertEquals(0, $stats['current_usage']);
    }

    public function test_multiple_instances_share_state_via_redis(): void
    {
        // Simulate two worker processes by creating two separate service instances
        $workerA = new ApiRateLimiterService;
        $workerB = new ApiRateLimiterService;
        $workerA->reset();

        // Worker A records 10 requests
        for ($i = 0; $i < 10; $i++) {
            $workerA->recordRequest();
        }

        // Worker B should see all 10 requests recorded by Worker A
        $statsB = $workerB->getStatistics();
        $this->assertEquals(10, $statsB['current_usage']);
    }
}
