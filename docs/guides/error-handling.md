# Error Handling

## Exceptions or error arrays

Whether a failed request throws is controlled by one setting:

```env
TEAMLEADER_THROW_EXCEPTIONS=true
```

| Setting | A failed request… |
|---|---|
| `true` (default since v3.0) | throws a typed exception — after retrying server and connection errors |
| `false` | is logged and returned as an array with `error => true`, without retries |

`false` was the default in 2.x. It is kept in 3.x to ease upgrading and will
be removed in a later major version.

These throw whatever the setting:

- **`RateLimitExceededException`**. Swallowing a 429 would hand back an empty
  result with no sign that anything went wrong.
- **`InvalidArgumentException`** from client-side [validation](validation.md).
  Nothing was sent, so there is no response to return.
- **`ConnectionNeedsReauthorizationException`** — the connection lost its
  refresh token; see [Authentication](../getting-started/authentication.md#when-teamleader-refuses-the-refresh-token).
- **`OAuthStateException`** and **`AccountMismatchException`** from
  `handleCallback()`.
- A failed page during `lazy()` / `cursor()` — see [Pagination](pagination.md).

The setting can also be changed at runtime:

```php
Teamleader::throwExceptions();        // on
Teamleader::throwExceptions(false);   // off
```

### With `throw_exceptions` off

```php
$result = Teamleader::companies()->info('company-uuid');

if ($result['error'] ?? false) {
    $result['status_code'];   // HTTP status, 0 for a connection failure
    $result['message'];       // the first error message
    $result['errors'];        // every error message from Teamleader
}
```

## Exception reference

Every exception extends `McoreServices\TeamleaderSDK\Exceptions\TeamleaderException`.

| Status | Exception | Retried by the SDK |
|---|---|---|
| 400, other 4xx | `TeamleaderException` | No |
| 401 | `AuthenticationException` | No |
| 403 | `AuthorizationException` | No |
| 404 | `NotFoundException` | No |
| 422 | `ValidationException` | No |
| 429 | `RateLimitExceededException` | No — see below |
| 500, 502, 503, 504 | `ServerException` | Yes |
| no response | `ConnectionException` | Yes |
| 401, connection flagged | `ConnectionNeedsReauthorizationException` (extends `AuthenticationException`) | No — nothing is sent |
| — | `ConfigurationException` | No |
| — | `OAuthStateException`, `AccountMismatchException` | No — from `handleCallback()` |

Server and connection errors are attempted three times in total
(`TEAMLEADER_API_RETRY_ATTEMPTS`), with exponential backoff between attempts —
1 second, then 2, then 4, capped at 30 — before the exception reaches you.
Retries happen only with `throw_exceptions` on.

### Methods

```php
$e->getMessage();      // the primary error message
$e->getStatusCode();   // HTTP status, or null
$e->getAllErrors();    // every error message Teamleader returned
$e->getContext();      // endpoint, request context, response data
```

`RateLimitExceededException` adds `getRetryAfter()` (seconds) and
`getResetTime()` (Unix timestamp, when known).

## A complete handler

```php
use McoreServices\TeamleaderSDK\Exceptions\{
    AuthenticationException,
    ConnectionException,
    NotFoundException,
    RateLimitExceededException,
    ServerException,
    TeamleaderException,
    ValidationException
};

try {
    $deal = Teamleader::deals()->create($data);
} catch (ValidationException $e) {
    // 422 — Teamleader rejected the values. Fix the input; retrying won't help.
    return back()->withErrors($e->getAllErrors());
} catch (NotFoundException $e) {
    // 404 — a referenced record does not exist
    return null;
} catch (RateLimitExceededException $e) {
    // 429 — hand the job back to the queue rather than sleeping
    $this->release($e->getRetryAfter());
} catch (AuthenticationException $e) {
    // 401 — the token could not be refreshed; the user must connect again
    return redirect('/teamleader/connect');
} catch (ServerException|ConnectionException $e) {
    // Retries exhausted — try again later
    $this->release(300);
} catch (TeamleaderException $e) {
    logger()->error('Teamleader error', ['status' => $e->getStatusCode(), 'errors' => $e->getAllErrors()]);
    throw $e;
}
```

`InvalidArgumentException` is left out on purpose: it points at a bug in the
calling code, and should fail loudly.

## Logging

Errors are logged whatever `throw_exceptions` is set to, through your
application's default logger, at a level matching the status: `info` for a 404,
`warning` for other 4xx and 429, `error` for 401 and connection failures, and
`critical` for 5xx. Tokens and credentials are redacted from the log context.
