# SDK rate limiter does not prevent 429 responses in multi-process environments

## Summary

The `ApiRateLimiterService` uses an in-memory static array to track request counts. This works correctly in a single-process context, but fails entirely when the SDK is used inside a queue worker pool (e.g. Laravel Horizon) because each worker process maintains its own independent counter. Workers have no visibility into each other's request activity, so they collectively exceed Teamleader's 200 requests/minute limit and receive 429 responses the SDK's throttling was supposed to prevent.

Additionally, when a 429 does occur, the SDK returns it as an error array (`['error' => true, ...]`) rather than throwing a `RateLimitExceededException`. This means calling code receives a structurally normal response with no data, and has no reliable way to distinguish a rate limit error from any other API failure without string-matching the error message.

---

## Environment

- **Package:** `mcore-services/teamleader-sdk`
- **Laravel:** 12.x
- **Queue driver:** Redis (Laravel Horizon, multiple workers)
- **PHP:** 8.3

---

## Steps to reproduce

1. Configure Laravel Horizon with 2+ workers on the same queue.
2. Dispatch 20+ `ProcessInvoiceExportJob` jobs in quick succession.
3. Each job calls `Teamleader::invoices()->info($id)` plus 2–3 additional SDK calls for enrichment.
4. Observe 429 responses from Teamleader despite `TEAMLEADER_RATE_LIMITING_ENABLED=true`.

---

## Root cause

**Problem 1 — in-memory state is not shared across processes**

`ApiRateLimiterService::$rateLimitState` is a `private static array`. Static properties are scoped to the PHP process. With four Horizon workers running, each process believes it has made 0 requests at startup and independently throttles against the full 200/min budget. In practice, all four workers together can issue up to 800 requests/min before any single worker's counter triggers throttling.

```php
// src/Services/ApiRateLimiterService.php

private static array $rateLimitState = [
    'requests' => [],  // timestamps — only ever reflect this process's requests
    ...
];
```

**Problem 2 — 429 responses are not thrown as exceptions**

When the API returns a 429, the SDK sets `$result['error'] = true` and returns the array. Calling code that checks `empty($response['data'])` cannot distinguish this from a validation error, a 404, or any other failure without inspecting the error message as a string.

```php
// src/TeamleaderSDK.php — the request method returns an array, never throws on 429
$result = $this->makeRequest($method, $endpoint, $data);
$this->errorHandler->handleApiError($result, "{$method} {$endpoint}");
```

---

## Expected behaviour

1. The rate limiter should coordinate across all processes using a shared backend (Redis, database, or atomic cache) so that the combined request rate across all workers stays within Teamleader's limit.
2. A 429 response from the API should throw `RateLimitExceededException` consistently, regardless of which code path triggered it, so callers can catch and handle it explicitly.

---

## Suggested fix

### 1. Move rate limit state to Redis

Replace the static array with atomic Redis operations using the same sliding window logic. A sorted set of timestamps keyed by a shared name (e.g. `teamleader_sdk:rate_limit`) works well: `ZADD` to record a request, `ZREMRANGEBYSCORE` to evict expired entries, `ZCARD` to read current usage. This is safe across processes and hosts.

### 2. Throw `RateLimitExceededException` on 429

Ensure `handleApiError` (or the request method directly) raises `RateLimitExceededException` when the API responds with a 429 status code or an error payload containing "rate limit exceeded". This gives callers a typed exception they can catch without fragile string matching.

```php
// Desired calling-code behaviour after the fix:
try {
    $response = Teamleader::invoices()->info($invoiceId);
} catch (RateLimitExceededException $e) {
    // release job back to queue, wait, retry — not a permanent failure
}
```

---

## Current workaround

A `TeamleaderRateLimitException` is caught in the application layer by inspecting the error string returned from the SDK and re-throwing a typed exception. The job then calls `$this->release(60)` to defer rather than fail. This is fragile and should not be necessary.
