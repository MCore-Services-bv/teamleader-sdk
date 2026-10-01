# Upgrading

## From 2.3 to 3.0

Most applications need four steps. Do them in this order:

1. **Check PHP** — 3.0 needs PHP 8.4 or higher. See [Requirements](#requirements).
2. **Update the package and run the migrations:**

   ```bash
   composer require mcore-services/teamleader-sdk:^3.0
   php artisan migrate
   ```

   The migration upgrades your existing token table in place; you stay
   connected. See [Run the migrations](#run-the-migrations).
3. **Decide on exceptions.** They are on by default now. Either add
   `TEAMLEADER_THROW_EXCEPTIONS=false` to `.env`, or replace your
   `['error']` checks with `catch` blocks. See
   [Exceptions are on by default](#exceptions-are-on-by-default).
4. **Run the Laravel scheduler**, so tokens are renewed before they expire.
   See [Token renewal](#token-renewal).

Then check `php artisan teamleader:health` and
`php artisan teamleader:status`. The remaining sections only matter if your
code uses what they describe.

New in 3.0, nothing to change for: [multiple connections](../guides/multiple-connections.md),
[events and logging](../guides/events-and-logging.md),
[`lazy()` pagination](../guides/pagination.md#fetching-everything),
[bulk operations](../guides/bulk-operations.md) and the
[command line](../guides/cli.md).

### Requirements

**PHP 8.4 or higher.** 3.0 drops PHP 8.2 and 8.3. Laravel 12 and 13 are both
still supported.

| | 2.3 | 3.0 |
|---|---|---|
| PHP | 8.2 – 8.5 | 8.4 – 8.5 |
| Laravel | 12, 13 | 12, 13 |

Check your version with `php -v`. If you are on 8.2 or 8.3, stay on
`^2.3` until you have upgraded PHP; 2.x receives security fixes for three
months after 3.0 is released.

### Run the migrations

```bash
php artisan migrate
```

2.x created the `teamleader_tokens` table itself on first use. 3.0 ships
migrations instead, and one of them upgrades your existing table in place: its
row becomes the `default` connection, and the tokens you have keep working.
**Without this step the SDK cannot read its tokens**; `teamleader:health`
reports it.

The tokens are then encrypted with `APP_KEY` — in the table the first time the
SDK reads them, and in the cache under new keys (`teamleader:default:tokens`).
The old plain-text cache keys are removed. If you ever rotate `APP_KEY`, see
[Token storage and security](../guides/token-storage-and-security.md).

### Exceptions are on by default

**The change most likely to affect your code.** `TEAMLEADER_THROW_EXCEPTIONS`
now defaults to `true`: a failed request throws a typed exception instead of
returning an array with `error => true`.

If you never set it, choose one:

**Keep 2.x behaviour for now** — add one line to `.env`, and upgrade nothing
else yet:

```dotenv
TEAMLEADER_THROW_EXCEPTIONS=false
```

**Or move to exceptions.** Search for `['error']` and `status_code` in your
code; each check becomes a `catch`:

```php
// 2.x
$result = Teamleader::companies()->info($id);

if ($result['error'] ?? false) {
    if ($result['status_code'] === 404) {
        return null;
    }

    Log::warning('Teamleader failed', ['message' => $result['message']]);

    return null;
}

return $result['data'];
```

```php
// 3.0
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    return Teamleader::companies()->info($id)['data'];
} catch (NotFoundException) {
    return null;
} catch (TeamleaderException $e) {
    Log::warning('Teamleader failed', ['message' => $e->getMessage()]);

    return null;
}
```

| 2.x `status_code` | 3.0 exception |
|---|---|
| 401 | `AuthenticationException` |
| 403 | `AuthorizationException` |
| 404 | `NotFoundException` |
| 422 | `ValidationException` |
| 429 | `RateLimitExceededException` (threw in 2.x as well) |
| 500, 502, 503, 504 | `ServerException` |
| 0 (no response) | `ConnectionException` |
| any other | `TeamleaderException` |

All extend `TeamleaderException`, so one `catch (TeamleaderException $e)`
covers everything; `$e->getStatusCode()` and `$e->getAllErrors()` give what
`status_code` and `errors` gave.

With exceptions on, server and connection errors are **retried** before the
exception reaches you — three attempts with backoff. In 2.x's default they
were not retried at all.

If you already had `TEAMLEADER_THROW_EXCEPTIONS=true`, nothing changes.

`false` stays supported throughout 3.x.

### Token renewal

- **Run the Laravel scheduler.** The package schedules
  `teamleader:tokens:refresh` every ten minutes. Without the scheduler nothing
  breaks — tokens are still refreshed when a request needs one — but an idle
  connection can expire unnoticed. `TEAMLEADER_TOKENS_AUTO_REFRESH=false`
  turns the schedule off.
- **A refused refresh token no longer deletes the tokens.** The connection is
  marked `needs_reauthorization`, and requests throw
  `ConnectionNeedsReauthorizationException` (a subclass of
  `AuthenticationException`) instead of returning a 401 error array. Code that
  detected a lost connection by an empty `getTokenInfo()` should check
  `needsReauthorization()` or the `status` key.

### Everything else

In rough order of how many applications they affect.

#### OAuth

- **`authorize()` without an argument now generates the `state`**, remembers
  it in the session, and `handleCallback()` checks it. If you generated and
  checked a state yourself, you can delete that code — or keep passing your
  own state to `authorize($state)`, which works as in 2.x.
- `getAuthorizationUrl()` without an argument generates a state too.
- `handleCallback()` returns the connected `TeamleaderSDK` instance instead of
  `true` (still `false` on failure). `if (Teamleader::handleCallback(...))`
  keeps working; `=== true` does not.
- A callback with a state this session did not issue throws
  `OAuthStateException`.

#### Configuration

If you published `config/teamleader.php`, compare it with the package's copy
(`vendor/mcore-services/teamleader-sdk/config/teamleader.php`). The keys below
were in the file but had no effect; they are removed. Deleting them from your
copy and from `.env` changes nothing about how the SDK behaves.

| Removed key | `.env` variable | Why |
|---|---|---|
| `sideloading.*` | `TEAMLEADER_SIDELOADING_ENABLED`, `TEAMLEADER_VALIDATE_INCLUDES`, `TEAMLEADER_MAX_INCLUDES` | Include validation is always on |
| `caching.*` | `TEAMLEADER_CACHING_ENABLED`, `TEAMLEADER_CACHE_TTL`, `TEAMLEADER_CACHE_STORE` | The SDK caches no API responses. The flag only cached the boot-time validation result, which nothing read |
| `development.*` | `TEAMLEADER_SANDBOX_MODE`, `TEAMLEADER_MOCK_RESPONSES`, `TEAMLEADER_DEBUG_MODE`, `TEAMLEADER_LOG_ALL_REQUESTS` | Only the configuration validator mentioned them |
| `rate_limiting.requests_per_minute`, `throttle_threshold`, `aggressive_throttling`, `respect_retry_after` | `TEAMLEADER_RATE_LIMIT`, `TEAMLEADER_THROTTLE_THRESHOLD`, `TEAMLEADER_AGGRESSIVE_THROTTLING`, `TEAMLEADER_RESPECT_RETRY_AFTER` | The limit is Teamleader's; the throttle steps are fixed |
| `logging.enabled`, `sanitize_logs`, `log_rate_limits`, `log_token_refresh` | `TEAMLEADER_LOGGING_ENABLED`, `TEAMLEADER_SANITIZE_LOGS`, `TEAMLEADER_LOG_RATE_LIMITS`, `TEAMLEADER_LOG_TOKEN_REFRESH` | Sanitising is always on; silence the SDK through its log channel |
| `error_handling.log_errors`, `include_stack_trace`, `parse_teamleader_errors` | `TEAMLEADER_LOG_ERRORS`, `TEAMLEADER_INCLUDE_STACK_TRACE`, `TEAMLEADER_PARSE_TL_ERRORS` | Only shown by the health check, never applied |

Three keys that were documented but ignored **now work**. Check your `.env`
for them before upgrading — a value you set long ago takes effect:

| Key | `.env` variable | Effect |
|---|---|---|
| `base_url` | `TEAMLEADER_BASE_URL` | API host |
| `auth_url` | `TEAMLEADER_AUTH_URL` | OAuth host for authorize, code exchange and token refresh |
| `api.retry_delay` | `TEAMLEADER_API_RETRY_DELAY` | First retry delay in ms (was fixed at 1000) |

`logging.channel` defaults to `null` (your default channel) instead of
`config('logging.default')`.

#### Removed methods and resource keys

Everything deprecated during the 2.2.x audit is gone. Each of these now fails
with "Call to undefined method" (or, for a resource key, an exception naming
the new key). Search your code for the left-hand column:

| Removed | Use instead |
|---|---|
| `users()->getWeekSchedule($id)` | `userSchedules()->forUser($id, $from, $until)` — at most seven days |
| `plannableItems()->active()` | `plannableItems()->list()`; filter on `completion_statuses` / `planned_time_statuses` |
| `invoices()->draft()` | `invoices()->listDrafts()` |
| `lostReasons()->search($ids)` | `lostReasons()->byIds($ids)`, or `all()` without ids |
| `companies()->byName($name)` | `companies()->search($name)` — the `term` filter |
| `quotations()->byStatus($status)` | `quotations()->list()`, filtered client-side on `data[].status` |
| `creditNotes()->paid()`, `unpaid()` | `creditNotes()->list()`, filtered client-side on `data[].paid` |
| `products()->withCustomFields()` | nothing — `info()` always returns custom fields |
| `deals()->withCustomer()`, `withResponsibleUser()`, `withDepartment()`, `withCurrentPhase()`, `withSource()`, `withAll()` | nothing — the data is returned on every deal |
| `calenderEvents()` | `calendarEvents()` |
| `creditnotes()` | `creditNotes()` |
| `payment_methods()` | `paymentMethods()` |
| `payment_terms()` | `paymentTerms()` |
| `external_parties()` | `externalParties()` |
| `plannable_items()` | `plannableItems()` |
| `user_availability()` | `userAvailability()` |
| `$sdk->getDeprecatedResourceAliases()` | nothing — there are no aliases left |

In 2.3 all of these still ran, most of them with an `E_USER_DEPRECATED`
notice in your log. If you upgrade to 2.3 first and clear those notices, this
step is a no-op.

#### Connections

Single-account applications need no change: the flat `client_id`,
`client_secret` and `redirect_uri` are the `default` connection.

- `new TeamleaderSDK(...)` takes a fifth argument, `?ConnectionConfig
  $connection`. Resolve instances through the container or
  `Teamleader::connection()` rather than constructing them.
- The SDK reads the credentials when an instance is built, not on every
  request. Code that changes `config('teamleader.client_id')` at runtime should
  define a connection with `Teamleader::extend()` instead.
- The rate-limit window moved to Redis keys per client ID. Its count starts at
  zero on the first request after the upgrade — the window is a minute long.
- Configuration errors name the connection:
  `Teamleader connection 'default' is missing: client_secret.` instead of
  `Missing required configuration: teamleader.client_secret`.

#### Token storage

- `TokenService` stores through a `TokenStore` (the table, by default) and
  takes the connection name: `new TokenService($store, 'default')`. Calling
  `new TokenService` without arguments still works.
- `getTokenInfo()` has new keys: `connection`, `status`, `account_id`,
  `account_name`, `last_refreshed_at`.
- `storeTokens()` takes a second argument, `bool $refreshed`, used by the
  refresh.

#### Logging and the call log

- `TEAMLEADER_LOG_CHANNEL`, `TEAMLEADER_LOG_REQUESTS` and
  `TEAMLEADER_LOG_RESPONSES` now work. If you set them in 2.x, where they did
  nothing, check the values: `LOG_REQUESTS=true` writes every request body to
  your log. See [Events and Logging](../guides/events-and-logging.md).
- `TeamleaderSDK::getApiCalls()` keeps the last 100 calls instead of every call
  since the process started, and no longer includes `request_data` or
  `headers`. Listen to `RequestSending` / `ResponseReceived` if you used them.
- The refresh token is no longer logged, not even its first 20 characters.

#### Health and validation commands

- `teamleader:health` checks your default cache store — the one tokens live
  in — instead of a `caching.store` setting. `--fix` no longer runs
  `Cache::flush()`, which cleared your **whole** application cache.
- `teamleader:config:validate` no longer suggests enabling caching or debug
  mode, and no longer warns "Laravel 11 detected" on every Laravel 12 and 13
  install.

#### Sorting

No code changes are needed. Two small differences:

- Sort validation messages name the endpoint:
  `Invalid sort field: title. deals.list accepts: created_at, weighted_value.`
  If you match on the old `Accepted:` wording, match on `Invalid sort field`
  instead.
- `timeTracking()->list()` now also accepts a list of field names and a
  `['starts_on' => 'desc']` map, like every other resource.

#### Removed classes

`McoreServices\TeamleaderSDK\Constants\TeamleaderConstants` and
`McoreServices\TeamleaderSDK\Constants\ErrorMessages` are removed. Nothing in
the SDK read them. If your code used one of their constants, inline the value.

## From 2.2 to 2.3

There are no breaking API changes in the SDK itself, and 2.3 runs on the same
PHP and Laravel versions. What does change across 2.2.4 – 2.3.0 is that
requests which used to be silently wrong now throw.

**Silent no-ops now throw `InvalidArgumentException`.** An unsupported filter,
sort field, include, option or body field used to be passed to the API — which
ignored it — or dropped by the SDK. Either way the call returned unfiltered
data, or an update changed nothing. Now it throws, naming the accepted values.

If upgrading surfaces one of these, the call was not doing what it appeared
to. Fix the name rather than catching the exception; the resource's
[reference page](../reference/README.md) lists what it accepts.

The most common cases:

| Before | Instead |
|---|---|
| `companies()->list(['name' => ...])` | `['term' => ...]` — searches name, VAT, email and phone |
| `timeTracking()->list(['updated_since' => ...])` | `started_after` / `ended_after` |
| `companies()->info($id, 'custom_fields')` | nothing — custom fields are always returned on `info` |
| `contacts()->with(...)->info($id)` | nothing — `contacts.info` takes no includes |
| A sort on a field the endpoint does not sort by | a field from the resource's **Sorting** section |

**Stricter-than-API checks were relaxed** at the same time, where the SDK
demanded fields the API does not — meeting customers, timer subjects, expense
totals, task work types on update. Code that worked around those can be
simplified.

The full list, release by release, is in the
[changelog](https://github.com/MCore-Services-bv/teamleader-sdk/blob/main/CHANGELOG.md).

## From 1.x to 2.0

2.0 dropped Laravel 10 and 11, which are end of life with unpatched security
advisories. On Laravel 12 no changes are needed. On Laravel 10 or 11, upgrade
Laravel, or pin the SDK to `^1.2` until you can.
