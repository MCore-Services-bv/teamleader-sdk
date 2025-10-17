<?php

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;

class RateLimiterTest extends TestCase
{
    private ApiRateLimiterService $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new ApiRateLimiterService();
    }

    /** @test */
    public function it_allows_requests_within_limit(): void
    {
        $result = $this->rateLimiter->checkAndThrottle();

        $this->assertTrue($result['can_proceed']);
        $this->assertEquals(0, $result['delay_applied']);
    }

    /** @test */
    public function it_records_requests(): void
    {
        $stats = $this->rateLimiter->getStatistics();
        $initialCount = $stats['requests_made'];

        $this->rateLimiter->recordRequest();

        $stats = $this->rateLimiter->getStatistics();
        $this->assertEquals($initialCount + 1, $stats['requests_made']);
    }

    /** @test */
    public function it_calculates_usage_percentage(): void
    {
        // Make some requests
        for ($i = 0; $i < 50; $i++) {
            $this->rateLimiter->recordRequest();
        }

        $stats = $this->rateLimiter->getStatistics();
        $this->assertGreaterThan(0, $stats['usage_percentage']);
        $this->assertLessThanOrEqual(100, $stats['usage_percentage']);
    }

    /** @test */
    public function it_respects_rate_limit_headers(): void
    {
        $headers = [
            'X-RateLimit-Remaining' => ['50'],
            'X-RateLimit-Limit' => ['200'],
        ];

        $this->rateLimiter->updateFromResponseHeaders($headers);

        $stats = $this->rateLimiter->getStatistics();
        $this->assertEquals(50, $stats['remaining']);
    }
}
