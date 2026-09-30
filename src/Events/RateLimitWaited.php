<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

/**
 * The SDK held a request back to stay inside Teamleader's rate limit.
 *
 * Fired once per request that waited, with the total time held — the wait for
 * a free slot plus any progressive throttling delay. When the wait runs out
 * (`rate_limiting.max_wait_ms`), `$gaveUp` is true and a RequestFailed with a
 * RateLimitExceededException follows.
 */
final readonly class RateLimitWaited
{
    /**
     * @param  int  $waitedMs  Total time the request was held
     * @param  float  $usagePercentage  How full the window was, 0 – 100
     * @param  string  $endpoint  The endpoint that waited
     * @param  bool  $gaveUp  True when the wait ran out and the request was not sent
     * @param  string  $connection  The Teamleader connection, `default` for a single account
     */
    public function __construct(
        public int $waitedMs,
        public float $usagePercentage,
        public string $endpoint,
        public bool $gaveUp = false,
        public string $connection = 'default',
    ) {}
}
