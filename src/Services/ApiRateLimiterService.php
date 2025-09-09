<?php

namespace McoreServices\TeamleaderSDK\Services;

use Carbon\Carbon;
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
     * Conservative throttling thresholds for sliding window
     */
    private const THROTTLE_THRESHOLDS = [
        70 => 200,   // 70-79% usage: 200ms delay
        80 => 500,   // 80-89% usage: 500ms delay
        90 => 1000,  // 90-94% usage: 1000ms delay
        95 => 2000,  // 95%+ usage: 2000ms delay + wait for oldest request to expire
    ];

    /**
     * In-memory storage for rate limit state
     */
    private static array $rateLimitState = [
        'requests' => [],           // Array of timestamps for requests in current window
        'remaining' => self::RATE_LIMIT,
        'reset_time' => null,
        'last_response_headers' => [],
        'total_requests' => 0,
        'throttled_requests' => 0,
        'total_delay_time' => 0,
    ];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Check if a request can be made and apply throttling if needed
     */
    public function checkAndThrottle(): array
    {
        $this->cleanupOldRequests();

        $currentUsage = $this->getCurrentUsage();
        $usagePercentage = ($currentUsage / self::RATE_LIMIT) * 100;

        $throttleInfo = [
            'can_proceed' => true,
            'current_usage' => $currentUsage,
            'usage_percentage' => round($usagePercentage, 1),
            'remaining' => self::RATE_LIMIT - $currentUsage,
            'delay_applied' => 0,
            'reason' => '',
            'reset_time' => $this->getNextSlotAvailableTime(),
            'throttle_level' => $this->getThrottleLevel($usagePercentage),
        ];

        // Check if we're at or over the limit
        if ($currentUsage >= self::RATE_LIMIT) {
            $oldestRequestTime = $this->getOldestRequestTime();
            $waitTime = 0;

            if ($oldestRequestTime) {
                // Wait until the oldest request falls out of the sliding window
                $waitTime = max(0, self::WINDOW_DURATION - (time() - $oldestRequestTime));
            } else {
                // Fallback: wait 60 seconds
                $waitTime = self::WINDOW_DURATION;
            }

            if ($waitTime > 0) {
                $throttleInfo['can_proceed'] = false;
                $throttleInfo['delay_applied'] = $waitTime * 1000; // Convert to milliseconds
                $throttleInfo['reason'] = 'Sliding window rate limit exceeded, waiting for slot';

                $this->logThrottling('sliding_window_exceeded', $throttleInfo);
                return $throttleInfo;
            }
        }

        // Apply progressive throttling based on usage
        $delay = $this->calculateDelay($usagePercentage);

        if ($delay > 0) {
            $throttleInfo['delay_applied'] = $delay;
            $throttleInfo['reason'] = $this->getThrottleReason($usagePercentage);

            self::$rateLimitState['throttled_requests']++;
            self::$rateLimitState['total_delay_time'] += $delay;

            $this->logThrottling('throttling_applied', $throttleInfo);
        }

        return $throttleInfo;
    }

    /**
     * Record a successful API request
     */
    public function recordRequest(): void
    {
        $now = time();

        self::$rateLimitState['requests'][] = $now;
        self::$rateLimitState['total_requests']++;

        // Clean up old requests to maintain accurate count
        $this->cleanupOldRequests();

        // Update remaining count
        self::$rateLimitState['remaining'] = max(0, self::RATE_LIMIT - $this->getCurrentUsage());

        $this->logger->debug('API request recorded', [
            'current_usage' => $this->getCurrentUsage(),
            'remaining' => self::$rateLimitState['remaining'],
            'total_requests' => self::$rateLimitState['total_requests']
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

        if (!empty($rateLimitData)) {
            if (isset($rateLimitData['remaining'])) {
                $headerRemaining = (int) $rateLimitData['remaining'];
                $localUsage = $this->getCurrentUsage();
                $localRemaining = self::RATE_LIMIT - $localUsage;

                // Use the more conservative estimate
                self::$rateLimitState['remaining'] = min($headerRemaining, $localRemaining);
            }

            if (isset($rateLimitData['reset'])) {
                // Handle both Unix timestamp and seconds-from-now formats
                $resetValue = (int) $rateLimitData['reset'];

                if ($resetValue > 1000000000) { // Unix timestamp
                    self::$rateLimitState['reset_time'] = Carbon::createFromTimestamp($resetValue);
                } else { // Seconds from now
                    self::$rateLimitState['reset_time'] = Carbon::now()->addSeconds($resetValue);
                }
            }

            self::$rateLimitState['last_response_headers'] = $rateLimitData;

            $this->logger->debug('Rate limit headers processed', [
                'headers' => $rateLimitData,
                'local_usage' => $this->getCurrentUsage(),
                'local_remaining' => self::RATE_LIMIT - $this->getCurrentUsage()
            ]);
        }
    }

    /**
     * Handle 429 Too Many Requests response
     */
    public function handle429Response(array $headers): int
    {
        $retryAfter = 60; // Default to 1 minute

        // Check for Retry-After header
        foreach ($headers as $headerName => $headerValue) {
            if (strtolower($headerName) === 'retry-after') {
                $retryAfter = is_array($headerValue) ? (int) $headerValue[0] : (int) $headerValue;
                break;
            }
        }

        // Update our state to reflect we're at the limit
        self::$rateLimitState['remaining'] = 0;
        self::$rateLimitState['reset_time'] = Carbon::now()->addSeconds($retryAfter);

        $this->logger->warning("Rate limit exceeded (429 response)", [
            'retry_after' => $retryAfter,
            'reset_time' => self::$rateLimitState['reset_time']->toISOString(),
            'current_usage' => $this->getCurrentUsage(),
            'sliding_window_requests' => count(self::$rateLimitState['requests'])
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

        return [
            'current_usage' => $currentUsage,
            'rate_limit' => self::RATE_LIMIT,
            'usage_percentage' => round($usagePercentage, 1),
            'remaining' => max(0, self::RATE_LIMIT - $currentUsage),
            'reset_time' => self::$rateLimitState['reset_time']?->toISOString(),
            'seconds_until_reset' => $this->getSecondsUntilOldestExpires(),
            'throttle_level' => $this->getThrottleLevel($usagePercentage),
            'total_requests' => self::$rateLimitState['total_requests'],
            'throttled_requests' => self::$rateLimitState['throttled_requests'],
            'total_delay_time' => self::$rateLimitState['total_delay_time'],
            'efficiency' => self::$rateLimitState['total_requests'] > 0
                ? round((1 - (self::$rateLimitState['throttled_requests'] / self::$rateLimitState['total_requests'])) * 100, 1)
                : 100,
            'sliding_window_requests' => count(self::$rateLimitState['requests']),
            'oldest_request_age' => $this->getOldestRequestAge(),
            'last_headers' => self::$rateLimitState['last_response_headers'],
        ];
    }

    /**
     * Reset rate limit state (useful for testing or manual reset)
     */
    public function reset(): void
    {
        self::$rateLimitState = [
            'requests' => [],
            'remaining' => self::RATE_LIMIT,
            'reset_time' => null,
            'last_response_headers' => [],
            'total_requests' => 0,
            'throttled_requests' => 0,
            'total_delay_time' => 0,
        ];
    }

    /**
     * Get current API usage in the sliding minute window
     */
    private function getCurrentUsage(): int
    {
        $this->cleanupOldRequests();
        return count(self::$rateLimitState['requests']);
    }

    /**
     * Remove requests older than 60 seconds (sliding window)
     */
    private function cleanupOldRequests(): void
    {
        $cutoff = time() - self::WINDOW_DURATION;

        self::$rateLimitState['requests'] = array_filter(
            self::$rateLimitState['requests'],
            fn($timestamp) => $timestamp > $cutoff
        );

        // Re-index array to prevent memory issues
        self::$rateLimitState['requests'] = array_values(self::$rateLimitState['requests']);
    }

    /**
     * Calculate delay based on current usage percentage
     */
    private function calculateDelay(float $usagePercentage): int
    {
        foreach (self::THROTTLE_THRESHOLDS as $threshold => $delay) {
            if ($usagePercentage >= $threshold) {
                // Add some jitter to prevent thundering herd
                $jitter = rand(0, (int)($delay * 0.1));
                return $delay + $jitter;
            }
        }

        return 0;
    }

    /**
     * Get throttle level description (more conservative for sliding window)
     */
    private function getThrottleLevel(float $usagePercentage): string
    {
        if ($usagePercentage >= 95) return 'critical';
        if ($usagePercentage >= 90) return 'high';
        if ($usagePercentage >= 80) return 'moderate';
        if ($usagePercentage >= 70) return 'low';
        return 'none';
    }

    /**
     * Get human-readable throttle reason
     */
    private function getThrottleReason(float $usagePercentage): string
    {
        if ($usagePercentage >= 95) return 'Sliding window critical - approaching limit';
        if ($usagePercentage >= 90) return 'Sliding window high usage';
        if ($usagePercentage >= 80) return 'Sliding window moderate usage';
        if ($usagePercentage >= 70) return 'Sliding window preventive throttling';
        return 'Normal operation';
    }

    /**
     * Log throttling events using PSR-3 logger interface
     */
    private function logThrottling(string $event, array $data): void
    {
        $this->logger->info("Rate limiting: {$event}", [
            'event' => $event,
            'rate_limit_data' => $data,
            'sliding_window_requests' => count(self::$rateLimitState['requests'])
        ]);
    }

    /**
     * Check if we're currently being throttled
     */
    public function isThrottled(): bool
    {
        $usage = $this->getCurrentUsage();
        return $usage >= (self::RATE_LIMIT * 0.7); // 70% threshold for sliding window
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
     * Get the timestamp of the oldest request in the sliding window
     */
    private function getOldestRequestTime(): ?int
    {
        if (empty(self::$rateLimitState['requests'])) {
            return null;
        }

        return min(self::$rateLimitState['requests']);
    }

    /**
     * Get seconds until the oldest request expires from the sliding window
     */
    private function getSecondsUntilOldestExpires(): int
    {
        $oldestTime = $this->getOldestRequestTime();

        if (!$oldestTime) {
            return 0;
        }

        $expiresAt = $oldestTime + self::WINDOW_DURATION;
        return max(0, $expiresAt - time());
    }

    /**
     * Get age of oldest request in seconds
     */
    private function getOldestRequestAge(): int
    {
        $oldestTime = $this->getOldestRequestTime();

        if (!$oldestTime) {
            return 0;
        }

        return time() - $oldestTime;
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

        if (!$oldestTime) {
            return Carbon::now()->addMinute(); // Fallback
        }

        return Carbon::createFromTimestamp($oldestTime + self::WINDOW_DURATION);
    }
}
