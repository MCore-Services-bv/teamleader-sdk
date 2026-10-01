# Rate Limiting

Teamleader allows **200 requests per minute**, measured over a sliding window.
The SDK tracks your usage in Redis and slows down before the window fills, so
requests are spent on data rather than on 429 responses.

## How it works

1. Every request is recorded in a Redis sorted set covering the last 60 seconds.
2. From 70% usage the SDK adds a delay before each request: 200 ms from 70%,
   500 ms from 80%, 1 second from 90% and 2 seconds from 95%.
3. When the window is full, the SDK waits for a slot, for up to `max_wait_ms`
   (5 seconds by default).
4. If no slot frees up in time, it throws `RateLimitExceededException` instead of
   sending a request that can only fail.

Because the count lives in Redis, every PHP process and queue worker shares
one window.

## Handling the limit in queue jobs

```php
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;

public function handle(): void
{
    try {
        $deals = Teamleader::deals()->list(['status' => ['open']]);
    } catch (RateLimitExceededException $e) {
        $this->release($e->getRetryAfter());

        return;
    }

    // ...
}
```

`RateLimitExceededException` is never retried by the SDK and always thrown,
whatever `TEAMLEADER_THROW_EXCEPTIONS` is set to. Releasing the job frees the
worker instead of holding it for up to a minute.

## Long-running scripts

For a CLI import, where blocking is fine, let the SDK sit out a full window
itself:

```env
TEAMLEADER_RATE_LIMIT_MAX_WAIT_MS=65000
```

Keep the default for web requests: a page that hangs for a minute is worse
than an error.

## Settings

| Variable | Default | |
|---|---|---|
| `TEAMLEADER_RATE_LIMITING_ENABLED` | `true` | Turn tracking and throttling off entirely |
| `TEAMLEADER_RATE_LIMIT_REDIS_CONNECTION` | `default` | Redis connection from `config/database.php` |
| `TEAMLEADER_RATE_LIMIT_MAX_WAIT_MS` | `5000` | Longest wait for a free slot before throwing |

The limit (200 per 60 seconds) and the throttle steps are fixed to Teamleader's
published limit. With rate limiting disabled, Redis is not needed, but nothing
stops a busy application from running into 429s.

## Watching usage

```php
$stats = Teamleader::getRateLimitStats();

$stats['remaining'];            // requests left in the current window
$stats['usage_percentage'];     // 0–100
$stats['seconds_until_reset'];  // until the oldest request leaves the window
$stats['throttled_requests'];   // requests delayed in this process
```

`php artisan teamleader:status` shows the same figures.
