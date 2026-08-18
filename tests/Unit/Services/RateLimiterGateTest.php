<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression tests for the v2.1.2 rate limiter gate fixes.
 *
 * Four defects, all contributing to 429s the proactive limiter was supposed to
 * prevent:
 *
 * 1. request() stored the result of its second checkAndThrottle() and never
 *    checked it, so a still-full window dispatched anyway.
 * 2. The wait was sleep((int) $delayMs / 1000), truncating any sub-second delay
 *    to zero — so even a corrected loop would have spun without waiting.
 * 3. recordRequest() ran only on success, so 429s and other errors went
 *    uncounted and the window drifted optimistic exactly when it mattered.
 * 4. checkAndThrottle() gated purely on the local sorted set, ignoring the
 *    X-RateLimit-Remaining value updateFromResponseHeaders() had carefully
 *    stored in Redis.
 *
 * These cover the limiter itself. The gate loop in TeamleaderSDK::request()
 * needs a full window to exercise and is left to integration testing.
 */
#[Group('redis')]
final class RateLimiterGateTest extends TestCase
{
    private ApiRateLimiterService $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rateLimiter = new ApiRateLimiterService;
        $this->rateLimiter->reset();
    }

    // ---------------------------------------------------------------------
    // Defect 4 — the API's own count is now consulted
    // ---------------------------------------------------------------------

    public function test_a_low_header_remaining_blocks_even_when_the_local_window_is_empty(): void
    {
        // Nothing recorded locally, but the API says we are out. This is the
        // divergence case: another integration on the same account has spent
        // the budget and our local window knows nothing about it.
        $this->rateLimiter->updateFromResponseHeaders([
            'X-RateLimit-Remaining' => ['0'],
            'X-RateLimit-Limit' => ['200'],
        ]);

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertFalse($result['can_proceed']);
        $this->assertGreaterThan(0, $result['delay_applied']);
    }

    public function test_the_header_remaining_is_reported_alongside_the_local_count(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $this->rateLimiter->updateFromResponseHeaders([
            'X-RateLimit-Remaining' => ['150'],
        ]);

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertSame(190, $result['local_remaining']);
        $this->assertNotNull($result['header_remaining']);
        $this->assertSame($result['header_remaining'], $result['remaining']);
    }

    public function test_the_more_conservative_of_the_two_signals_wins(): void
    {
        // Local window says 195 left; the API says less. The API wins.
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $this->rateLimiter->updateFromResponseHeaders([
            'X-RateLimit-Remaining' => ['20'],
        ]);

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertSame(20, $result['remaining']);
        $this->assertGreaterThan(0, $result['delay_applied'], 'A near-exhausted budget must throttle.');
    }

    public function test_a_missing_header_value_falls_back_to_the_local_window(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertNull($result['header_remaining']);
        $this->assertSame(170, $result['remaining']);
        $this->assertTrue($result['can_proceed']);
    }

    // ---------------------------------------------------------------------
    // A 429 blocks every worker for the Retry-After duration
    // ---------------------------------------------------------------------

    public function test_a_429_blocks_subsequent_checks(): void
    {
        // handle429Response() clears the window but sets remaining to 0, so the
        // cleared window must not read as "plenty of headroom".
        $this->rateLimiter->handle429Response(['Retry-After' => ['30']]);

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertFalse(
            $result['can_proceed'],
            'After a 429 the limiter must block, even though the sliding window was cleared.'
        );
    }

    public function test_a_429_wait_is_derived_from_the_reset_time(): void
    {
        $this->rateLimiter->handle429Response(['Retry-After' => ['30']]);

        $result = $this->rateLimiter->checkAndThrottle();

        // Roughly Retry-After, not the full 60s window.
        $this->assertGreaterThan(20_000, $result['delay_applied']);
        $this->assertLessThanOrEqual(31_000, $result['delay_applied']);
    }

    public function test_a_second_limiter_instance_sees_the_block(): void
    {
        $this->rateLimiter->handle429Response(['Retry-After' => ['30']]);

        $otherWorker = new ApiRateLimiterService;

        $this->assertFalse($otherWorker->checkAndThrottle()['can_proceed']);
    }

    // ---------------------------------------------------------------------
    // Defect 2 — the delay is usable as a wait
    // ---------------------------------------------------------------------

    public function test_a_blocked_check_reports_at_least_one_full_second(): void
    {
        // The old gate did sleep((int) ($delayMs / 1000)), so anything under a
        // second became sleep(0) and the loop spun. Whatever the limiter
        // reports must survive integer conversion as a real wait.
        $this->rateLimiter->handle429Response(['Retry-After' => ['5']]);

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertFalse($result['can_proceed']);
        $this->assertGreaterThanOrEqual(1000, $result['delay_applied']);
    }

    // ---------------------------------------------------------------------
    // Unchanged behaviour
    // ---------------------------------------------------------------------

    public function test_an_empty_window_proceeds_without_delay(): void
    {
        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertTrue($result['can_proceed']);
        $this->assertSame(0, $result['delay_applied']);
        $this->assertSame(200, $result['remaining']);
    }

    public function test_progressive_throttling_still_applies(): void
    {
        for ($i = 0; $i < 160; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertTrue($result['can_proceed']);
        $this->assertGreaterThanOrEqual(500, $result['delay_applied']);
    }

    public function test_a_full_local_window_blocks(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertFalse($result['can_proceed']);
        $this->assertSame(0, $result['remaining']);
    }

    public function test_reset_clears_the_header_derived_block_too(): void
    {
        $this->rateLimiter->handle429Response(['Retry-After' => ['30']]);
        $this->rateLimiter->reset();

        $this->assertTrue($this->rateLimiter->checkAndThrottle()['can_proceed']);
    }
}
