<?php

namespace McoreServices\TeamleaderSDK\Services;

use Carbon\Carbon;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ApiRateLimiterService
{
    /**
     * Teamleader API rate limit (requests per sliding minute)
     */
    private const RATE_LIMIT = 200;

    /**
     * Sliding window duration in seconds
     */
    private const WINDOW_DURATION = 60;

    /**
     * Redis key for the sliding window sorted set
     */
    private const SORTED_SET_KEY = 'teamleader_sdk:rate_limit';

    /**
     * Redis key for the last-known remaining value from response headers
     */
    private const REMAINING_KEY = 'teamleader_sdk:remaining';

    /**
     * Redis key for the rate limit reset timestamp from response headers
     */
    private const RESET_TIME_KEY = 'teamleader_sdk:reset_time';

    /**
     * Conservative throttling thresholds for sliding window
     */
    private const THROTTLE_THRESHOLDS = [
        70 => 200,   // 70-79% usage: 200ms delay
        80 => 500,   // 80-89% usage: 500ms delay
        90 => 1000,  // 90-94% usage: 1000ms delay
        95 => 2000,  // 95%+ usage: 2000ms delay + wait for oldest request to expire
    ];

    /**
     * Per-process diagnostic statistics.
     *
     * These are intentionally in-memory: they track what this process has done
     * in the current session and are not used for throttling decisions. All
     * throttling decisions use Redis so they are consistent across workers.
     */
    private static array $processStats = [
        'total_requests' => 0,
        'throttled_requests' => 0,
        'total_delay_time' => 0,
        'last_response_headers' => [],
    ];

    private LoggerInterface $logger;

    private string $redisConnection;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger;
        $this->redisConnection = config('teamleader.rate_limiting.redis_connection', 'default');
    }

    /**
     * Check if a request can be made and apply throttling if needed
     *
     * Gates on whichever of the two signals is more conservative: the local
     * sliding window, and the `X-RateLimit-Remaining` value the API last
     * reported. The header value matters whenever the two diverge — another
     * integration on the same account, a second application, a manual API
     * client, or requests made before this window was populated. Before v2.1.2
     * the header value was stored in Redis by updateFromResponseHeaders() and
     * then never read, which made the most authoritative number available
     * purely decorative.
     */
    public function checkAndThrottle(): array
    {
        $this->cleanupOldRequests();

        $currentUsage = $this->getCurrentUsage();
        $localRemaining = self::RATE_LIMIT - $currentUsage;

        $headerRemaining = $this->getHeaderRemaining();

        $effectiveRemaining = $headerRemaining !== null
            ? min($localRemaining, $headerRemaining)
            : $localRemaining;

        // Express the effective remaining as usage so the progressive throttle
        // thresholds react to the API's number too, not just the local count.
        $effectiveUsage = self::RATE_LIMIT - $effectiveRemaining;
        $usagePercentage = ($effectiveUsage / self::RATE_LIMIT) * 100;

        $throttleInfo = [
            'can_proceed' => true,
            'current_usage' => $currentUsage,
            'usage_percentage' => round($usagePercentage, 1),
            'remaining' => max(0, $effectiveRemaining),
            'local_remaining' => $localRemaining,
            'header_remaining' => $headerRemaining,
            'delay_applied' => 0,
            'reason' => '',
            'reset_time' => $this->getNextSlotAvailableTime(),
            'throttle_level' => $this->getThrottleLevel($usagePercentage),
        ];

        // Check if we're at or over the limit
        if ($effectiveRemaining <= 0) {
            $waitTime = $this->getSecondsUntilSlotFree();

            if ($waitTime > 0) {
                $throttleInfo['can_proceed'] = false;
                $throttleInfo['delay_applied'] = (int) ceil($waitTime * 1000); // Convert to milliseconds
                $throttleInfo['reason'] = $headerRemaining !== null && $headerRemaining <= 0
                    ? 'API reported no remaining requests, waiting for reset'
                    : 'Sliding window rate limit exceeded, waiting for slot';

                $this->logThrottling('sliding_window_exceeded', $throttleInfo);

                return $throttleInfo;
            }
        }

        // Apply progressive throttling based on usage
        $delay = $this->calculateDelay($usagePercentage);

        if ($delay > 0) {
            $throttleInfo['delay_applied'] = $delay;
            $throttleInfo['reason'] = $this->getThrottleReason($usagePercentage);

            self::$processStats['throttled_requests']++;
            self::$processStats['total_delay_time'] += $delay;

            $this->logThrottling('throttling_applied', $throttleInfo);
        }

        return $throttleInfo;
    }

    /**
     * Read the last-known remaining count reported by the API
     *
     * Written by updateFromResponseHeaders() and by handle429Response(), which
     * sets it to zero for the duration of Retry-After so every worker backs off.
     *
     * @return int|null Null when no header value has been seen recently
     */
    private function getHeaderRemaining(): ?int
    {
        $value = $this->redis()->get(self::REMAINING_KEY);

        return $value === null || $value === false ? null : (int) $value;
    }

    /**
     * Seconds until a slot is expected to free up
     *
     * Prefers the reset timestamp the API gave us, since that is authoritative
     * when the block came from the API's count rather than the local window.
     * Falls back to the oldest request ageing out of the sliding window.
     */
    private function getSecondsUntilSlotFree(): float
    {
        $resetAt = $this->redis()->get(self::RESET_TIME_KEY);

        if ($resetAt !== null && $resetAt !== false) {
            $secondsUntilReset = (int) $resetAt - time();

            if ($secondsUntilReset > 0) {
                return (float) $secondsUntilReset;
            }
        }

        $oldestRequestTime = $this->getOldestRequestTime();

        if ($oldestRequestTime) {
            // Wait until the oldest request falls out of the sliding window
            return max(0, self::WINDOW_DURATION - (microtime(true) - $oldestRequestTime));
        }

        // Fallback: wait the full window
        return (float) self::WINDOW_DURATION;
    }

    /**
     * Record an API request in the shared Redis sliding window
     *
     * Called before the request is dispatched, not after it succeeds.
     * Teamleader counts every request against the budget, including the ones
     * that return 4xx and the 429s themselves, so recording only successes made
     * the window drift optimistic exactly when errors were already occurring.
     */
    public function recordRequest(): void
    {
        $now = microtime(true);
        // Each member must be unique so concurrent processes don't overwrite each other
        $member = uniqid('req_', true);

        $redis = $this->redis();
        $redis->zadd(self::SORTED_SET_KEY, $now, $member);
        // Keep the key alive well beyond the window so short gaps don't lose the set
        $redis->expire(self::SORTED_SET_KEY, self::WINDOW_DURATION * 2);

        self::$processStats['total_requests']++;

        $currentUsage = $this->getCurrentUsage();

        $this->logger->debug('API request recorded', [
            'current_usage' => $currentUsage,
            'remaining' => max(0, self::RATE_LIMIT - $currentUsage),
            'total_requests' => self::$processStats['total_requests'],
        ]);
    }

    /**
     * Update rate limit state from API response headers
     */
    public function updateFromResponseHeaders(array $headers): void
    {
        $headerMap = [
            'x-ratelimit-limit' => 'limit',
            'x-ratelimit-remaining' => 'remaining',
            'x-ratelimit-reset' => 'reset',
        ];

        $rateLimitData = [];

        foreach ($headers as $headerName => $headerValue) {
            $normalizedHeader = strtolower($headerName);

            if (isset($headerMap[$normalizedHeader])) {
                $rateLimitData[$headerMap[$normalizedHeader]] = is_array($headerValue)
                    ? $headerValue[0]
                    : $headerValue;
            }
        }

        if (! empty($rateLimitData)) {
            $redis = $this->redis();

            if (isset($rateLimitData['remaining'])) {
                $headerRemaining = (int) $rateLimitData['remaining'];
                $localUsage = $this->getCurrentUsage();
                $localRemaining = self::RATE_LIMIT - $localUsage;

                // Store the more conservative estimate so all workers benefit from it
                $redis->setex(self::REMAINING_KEY, self::WINDOW_DURATION, min($headerRemaining, $localRemaining));
            }

            if (isset($rateLimitData['reset'])) {
                $resetValue = (int) $rateLimitData['reset'];
                $redis->setex(self::RESET_TIME_KEY, self::WINDOW_DURATION * 2, $resetValue);
            }

            self::$processStats['last_response_headers'] = $rateLimitData;

            $this->logger->debug('Rate limit headers processed', [
                'headers' => $rateLimitData,
                'local_usage' => $this->getCurrentUsage(),
                'local_remaining' => self::RATE_LIMIT - $this->getCurrentUsage(),
            ]);
        }
    }

    /**
     * Handle 429 Too Many Requests response
     */
    public function handle429Response(array $headers): int
    {
        $retryAfter = 60; // Default to 1 minute

        foreach ($headers as $headerName => $headerValue) {
            if (strtolower($headerName) === 'retry-after') {
                $retryAfter = is_array($headerValue) ? (int) $headerValue[0] : (int) $headerValue;
                break;
            }
        }

        // Clear the sliding window and mark the limit as exhausted for all workers
        $redis = $this->redis();
        $redis->del(self::SORTED_SET_KEY);
        $redis->setex(self::REMAINING_KEY, $retryAfter + 5, 0);
        $redis->setex(self::RESET_TIME_KEY, $retryAfter + 5, time() + $retryAfter);

        $this->logger->warning('Rate limit exceeded (429 response)', [
            'retry_after' => $retryAfter,
            'reset_time' => Carbon::now()->addSeconds($retryAfter)->toISOString(),
            'current_usage' => $this->getCurrentUsage(),
        ]);

        return $retryAfter;
    }

    /**
     * Get comprehensive rate limit statistics
     */
    public function getStatistics(): array
    {
        $currentUsage = $this->getCurrentUsage();
        $usagePercentage = ($currentUsage / self::RATE_LIMIT) * 100;

        $resetTimeValue = $this->redis()->get(self::RESET_TIME_KEY);
        $resetTime = $resetTimeValue
            ? Carbon::createFromTimestamp((int) $resetTimeValue)->toISOString()
            : null;

        return [
            'current_usage' => $currentUsage,
            'rate_limit' => self::RATE_LIMIT,
            'usage_percentage' => round($usagePercentage, 1),
            'remaining' => max(0, self::RATE_LIMIT - $currentUsage),
            'reset_time' => $resetTime,
            'seconds_until_reset' => $this->getSecondsUntilOldestExpires(),
            'throttle_level' => $this->getThrottleLevel($usagePercentage),
            'total_requests' => self::$processStats['total_requests'],
            'throttled_requests' => self::$processStats['throttled_requests'],
            'total_delay_time' => self::$processStats['total_delay_time'],
            'efficiency' => self::$processStats['total_requests'] > 0
                ? round((1 - (self::$processStats['throttled_requests'] / self::$processStats['total_requests'])) * 100, 1)
                : 100,
            'sliding_window_requests' => $currentUsage,
            'oldest_request_age' => $this->getOldestRequestAge(),
            'last_headers' => self::$processStats['last_response_headers'],
        ];
    }

    /**
     * Reset rate limit state — clears Redis keys and in-memory stats.
     * Useful for testing or manual operator reset.
     */
    public function reset(): void
    {
        $redis = $this->redis();
        $redis->del(self::SORTED_SET_KEY);
        $redis->del(self::REMAINING_KEY);
        $redis->del(self::RESET_TIME_KEY);

        self::$processStats = [
            'total_requests' => 0,
            'throttled_requests' => 0,
            'total_delay_time' => 0,
            'last_response_headers' => [],
        ];
    }

    /**
     * Check if we're currently being throttled
     */
    public function isThrottled(): bool
    {
        return $this->getCurrentUsage() >= (self::RATE_LIMIT * 0.7);
    }

    /**
     * Get time until oldest request expires from sliding window
     */
    public function getTimeUntilReset(): int
    {
        return $this->getSecondsUntilOldestExpires();
    }

    /**
     * Get recommended delay for next request
     */
    public function getRecommendedDelay(): int
    {
        $usage = $this->getCurrentUsage();
        $usagePercentage = ($usage / self::RATE_LIMIT) * 100;

        return $this->calculateDelay($usagePercentage);
    }

    /**
     * Resolve the configured Redis connection
     */
    private function redis(): Connection
    {
        return Redis::connection($this->redisConnection);
    }

    /**
     * Get current API usage in the sliding minute window (from Redis)
     */
    private function getCurrentUsage(): int
    {
        $this->cleanupOldRequests();

        return (int) $this->redis()->zcard(self::SORTED_SET_KEY);
    }

    /**
     * Remove requests older than the sliding window from Redis
     */
    private function cleanupOldRequests(): void
    {
        $cutoff = microtime(true) - self::WINDOW_DURATION;
        $this->redis()->zremrangebyscore(self::SORTED_SET_KEY, '-inf', $cutoff);
    }

    /**
     * Calculate delay based on usage percentage and configured thresholds
     */
    private function calculateDelay(float $usagePercentage): int
    {
        $delay = 0;

        foreach (array_reverse(self::THROTTLE_THRESHOLDS, true) as $threshold => $delayMs) {
            if ($usagePercentage >= $threshold) {
                $delay = $delayMs;
                break;
            }
        }

        return $delay;
    }

    /**
     * Get throttle level label for the given usage percentage
     */
    private function getThrottleLevel(float $usagePercentage): string
    {
        return match (true) {
            $usagePercentage >= 95 => 'critical',
            $usagePercentage >= 90 => 'high',
            $usagePercentage >= 80 => 'medium',
            $usagePercentage >= 70 => 'low',
            default => 'none',
        };
    }

    /**
     * Get human-readable throttle reason for the given usage percentage
     */
    private function getThrottleReason(float $usagePercentage): string
    {
        return match (true) {
            $usagePercentage >= 95 => 'Critical throttling: 95%+ usage',
            $usagePercentage >= 90 => 'High throttling: 90%+ usage',
            $usagePercentage >= 80 => 'Medium throttling: 80%+ usage',
            $usagePercentage >= 70 => 'Low throttling: 70%+ usage',
            default => '',
        };
    }

    /**
     * Log a throttling event using PSR-3 logger interface
     */
    private function logThrottling(string $event, array $data): void
    {
        $this->logger->info("Rate limiting: {$event}", [
            'event' => $event,
            'rate_limit_data' => $data,
            'sliding_window_requests' => $this->getCurrentUsage(),
        ]);
    }

    /**
     * Get the score (timestamp) of the oldest request in the sorted set
     */
    private function getOldestRequestTime(): ?float
    {
        $members = $this->redis()->zrange(self::SORTED_SET_KEY, 0, 0);

        if (empty($members)) {
            return null;
        }

        $score = $this->redis()->zscore(self::SORTED_SET_KEY, $members[0]);

        return $score !== null ? (float) $score : null;
    }

    /**
     * Get seconds until the oldest request expires from the sliding window
     */
    private function getSecondsUntilOldestExpires(): int
    {
        $oldestTime = $this->getOldestRequestTime();

        if (! $oldestTime) {
            return 0;
        }

        $expiresAt = $oldestTime + self::WINDOW_DURATION;

        return max(0, (int) ($expiresAt - microtime(true)));
    }

    /**
     * Get age of oldest request in seconds
     */
    private function getOldestRequestAge(): int
    {
        $oldestTime = $this->getOldestRequestTime();

        if (! $oldestTime) {
            return 0;
        }

        return (int) (microtime(true) - $oldestTime);
    }

    /**
     * Get the time when the next slot will be available
     */
    private function getNextSlotAvailableTime(): ?Carbon
    {
        if ($this->getCurrentUsage() < self::RATE_LIMIT) {
            return Carbon::now(); // Slot available now
        }

        $oldestTime = $this->getOldestRequestTime();

        if (! $oldestTime) {
            return Carbon::now()->addMinute(); // Fallback
        }

        return Carbon::createFromTimestamp($oldestTime + self::WINDOW_DURATION);
    }
}
