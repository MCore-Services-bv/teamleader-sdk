# Errors

Reference for all exceptions and HTTP status codes used by the Teamleader SDK.

## Overview

The SDK maps every API response and connection failure to a typed PHP exception. Each exception
extends `TeamleaderException`, so you can catch broadly or narrow down to specific conditions.

Whether exceptions are thrown is controlled by the `throw_exceptions` flag in `config/teamleader.php`. One exception to
this rule: `RateLimitExceededException` (429) **always** throws, regardless of that setting, because silently swallowing
a rate-limit failure returns empty data to the caller with no indication of why.

---

## Exception Reference

| HTTP Status | Exception Class              | Retryable  | Log Level |
|-------------|------------------------------|------------|-----------|
| 400         | `TeamleaderException`        | ❌          | warning   |
| 401         | `AuthenticationException`    | ❌          | error     |
| 403         | `AuthorizationException`     | ❌          | warning   |
| 404         | `NotFoundException`          | ❌          | info      |
| 422         | `ValidationException`        | ❌          | warning   |
| 429         | `RateLimitExceededException` | ✅ (manual) | warning   |
| 500         | `ServerException`            | ✅ (auto)   | critical  |
| 502         | `ServerException`            | ✅ (auto)   | critical  |
| 503         | `ServerException`            | ✅ (auto)   | critical  |
| 504         | `ServerException`            | ✅ (auto)   | critical  |
| 0           | `ConnectionException`        | ✅ (auto)   | error     |
| —           | `ConfigurationException`     | ❌          | critical  |

---

## Client Errors (4xx)

### 400 — Bad Request

**Exception:** `TeamleaderException`

The request was malformed. Common causes: missing required fields, wrong data types, invalid JSON, malformed dates.

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $company = Teamleader::companies()->create($data);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 400) {
        Log::error('Bad request', ['errors' => $e->getAllErrors()]);
    }
}
```

---

### 401 — Unauthorized

**Exception:** `AuthenticationException`

Token is missing, expired, or invalid. The SDK handles token refresh automatically before requests. If this exception is
thrown, the refresh itself has failed.

```php
use McoreServices\TeamleaderSDK\Exceptions\AuthenticationException;

try {
    $companies = Teamleader::companies()->list();
} catch (AuthenticationException $e) {
    // Re-authenticate via OAuth flow
    return redirect(Teamleader::authorize());
}
```

---

### 403 — Forbidden

**Exception:** `AuthorizationException`

The authenticated user does not have the required scopes or permissions for this operation.

```php
use McoreServices\TeamleaderSDK\Exceptions\AuthorizationException;

try {
    $invoice = Teamleader::invoices()->create($data);
} catch (AuthorizationException $e) {
    Log::warning('Insufficient permissions', ['message' => $e->getMessage()]);
}
```

---

### 404 — Not Found

**Exception:** `NotFoundException`

The requested resource does not exist, or the UUID is incorrect.

```php
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;

try {
    $contact = Teamleader::contacts()->info('contact-uuid');
} catch (NotFoundException $e) {
    // Resource does not exist — handle gracefully
    return null;
}
```

---

### 422 — Validation Failed

**Exception:** `ValidationException`

The API rejected the request due to invalid field values. Call `getAllErrors()` to retrieve the full list of validation
messages.

```php
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;

try {
    $deal = Teamleader::deals()->create($data);
} catch (ValidationException $e) {
    $errors = $e->getAllErrors(); // array of error strings
    return back()->withErrors($errors);
}
```

---

### 429 — Rate Limit Exceeded

**Exception:** `RateLimitExceededException`

**Always thrown**, regardless of the `throw_exceptions` configuration flag.

The exception carries:

- `getRetryAfter()` — seconds to wait before retrying (from `Retry-After` header, defaults to 60)
- `getResetTime()` — Unix timestamp when the rate limit resets (from `X-RateLimit-Reset` header)

The SDK's built-in retry logic does **not** automatically retry 429 responses. Sleeping for 60 seconds inside a queue
worker blocks the thread. Handle this at the job/queue level instead.

```php
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;

try {
    $invoices = Teamleader::invoices()->list();
} catch (RateLimitExceededException $e) {
    $retryAfter = $e->getRetryAfter(); // seconds
    
    // Delay and re-dispatch rather than sleeping inline
    dispatch(new FetchInvoicesJob())->delay(now()->addSeconds($retryAfter));
}
```

---

## Server Errors (5xx)

**Exception:** `ServerException`

Covers 500, 502, 503, and 504. These are transient Teamleader-side failures. The SDK automatically retries server errors
using exponential backoff.

```php
use McoreServices\TeamleaderSDK\Exceptions\ServerException;

try {
    $result = Teamleader::deals()->create($data);
} catch (ServerException $e) {
    // Retries exhausted — queue for later
    dispatch(new CreateDealJob($data))->delay(now()->addMinutes(5));
}
```

---

## Connection Errors

**Exception:** `ConnectionException`

Thrown when the HTTP request cannot be completed. Common causes: network unavailability, DNS failure, SSL handshake
failure, firewall blocking.

```php
use McoreServices\TeamleaderSDK\Exceptions\ConnectionException;

try {
    $companies = Teamleader::companies()->list();
} catch (ConnectionException $e) {
    Log::error('Teamleader unreachable', ['message' => $e->getMessage()]);
}
```

---

## Configuration Errors

**Exception:** `ConfigurationException`

Thrown during SDK initialisation when required credentials or settings are missing. Logged at `critical` level.

---

## Recommended Catch Order

Catch specific exceptions before broad ones. `TeamleaderException` is the base class for all SDK exceptions.

```php
use McoreServices\TeamleaderSDK\Exceptions\{
    AuthenticationException,
    ValidationException,
    RateLimitExceededException,
    NotFoundException,
    ServerException,
    ConnectionException,
    TeamleaderException
};

try {
    $result = Teamleader::companies()->create($data);

} catch (ValidationException $e) {
    // Fix input and retry — do not queue
    return back()->withErrors($e->getAllErrors());

} catch (AuthenticationException $e) {
    // Re-authenticate
    return redirect(Teamleader::authorize());

} catch (RateLimitExceededException $e) {
    // Re-dispatch after delay — do not sleep inline
    dispatch(new CreateCompanyJob($data))->delay(now()->addSeconds($e->getRetryAfter()));

} catch (NotFoundException $e) {
    // Resource gone — handle gracefully
    return null;

} catch (ServerException | ConnectionException $e) {
    // Transient — queue for retry
    dispatch(new CreateCompanyJob($data))->delay(now()->addMinutes(5));

} catch (TeamleaderException $e) {
    // Unexpected error
    Log::error('Teamleader error', ['code' => $e->getCode(), 'message' => $e->getMessage()]);
}
```

---

## Configuration

```php
// config/teamleader.php
'error_handling' => [
    'throw_exceptions' => true, // false = log only (except 429, which always throws)
],
```

---

## Related Resources

- [[Filtering]] — Filter parameters and validation
- [[Sideloading]] — Loading related data
- [[Usage]] — General SDK usage guide
