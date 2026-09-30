# Configuration

Settings live in `config/teamleader.php` and are set from `.env`. Only the
three OAuth credentials are required.

## Settings the SDK reads

| Variable | Default | |
|---|---|---|
| `TEAMLEADER_CLIENT_ID` | — | Required |
| `TEAMLEADER_CLIENT_SECRET` | — | Required |
| `TEAMLEADER_REDIRECT_URI` | — | Required; must match your integration |
| `TEAMLEADER_API_VERSION` | `2023-09-26` | Sent as the API version header |
| `TEAMLEADER_API_TIMEOUT` | `30` | Request timeout, seconds |
| `TEAMLEADER_API_CONNECT_TIMEOUT` | `10` | Connect timeout, seconds |
| `TEAMLEADER_API_READ_TIMEOUT` | `25` | Read timeout, seconds |
| `TEAMLEADER_API_RETRY_ATTEMPTS` | `3` | Attempts for server and connection errors |
| `TEAMLEADER_API_RETRY_DELAY` | `1000` | First retry delay, milliseconds; doubles per attempt, capped at 30 s |
| `TEAMLEADER_BASE_URL` | `https://api.focus.teamleader.eu` | API host — change only for a proxy or a sandbox |
| `TEAMLEADER_AUTH_URL` | `https://focus.teamleader.eu` | OAuth host (authorize, token exchange and refresh) |
| `TEAMLEADER_THROW_EXCEPTIONS` | `false` | Throw typed exceptions on failure |
| `TEAMLEADER_RATE_LIMITING_ENABLED` | `true` | Track and throttle requests in Redis |
| `TEAMLEADER_RATE_LIMIT_REDIS_CONNECTION` | `default` | Redis connection used by the limiter |
| `TEAMLEADER_RATE_LIMIT_MAX_WAIT_MS` | `5000` | Longest wait for a free slot before throwing |
| `TEAMLEADER_VALIDATE_ON_BOOT` | `false` | Validate the configuration when the app boots |

{% hint style="info" %}
**`TEAMLEADER_LOG_CHANNEL`, `TEAMLEADER_LOG_REQUESTS` and
`TEAMLEADER_LOG_RESPONSES` are not read yet.** They are in the published file
because v3.0 wires them up together with the SDK's events. Until then the SDK
logs through your application's default logger, with tokens always redacted.
{% endhint %}

The SDK does not cache API responses, and include validation cannot be turned
off: both are by design. Tokens are cached in your application's default cache
store.

## Settings worth changing

### `TEAMLEADER_THROW_EXCEPTIONS`

`false` by default for backwards compatibility: a failed request is logged and
returned as an array with `error => true`. Set it to `true` in new projects so
failures surface as typed exceptions. A 429 always throws, whatever this is set
to. See [Error handling](../guides/error-handling.md).

### `TEAMLEADER_RATE_LIMIT_MAX_WAIT_MS`

How long the SDK waits for a free slot in the rate-limit window before throwing
`RateLimitExceededException`. The 5-second default suits web requests and queue
workers. For a long-running CLI import, `65000` lets the SDK sit out a full
window itself. See [Rate limiting](../guides/rate-limiting.md).

## Storing reference UUIDs

Departments, deal phases, work types and custom field definitions have UUIDs
that differ per Teamleader account. Keep them in `config/teamleader.php`
instead of scattering them through your code:

```bash
php artisan teamleader:export-uuids
php artisan teamleader:export-uuids --resource=departments
```

The command prints configuration you can paste into the file. Then:

```php
Teamleader::deals()->create([
    'title'         => 'Enterprise deal',
    'lead'          => ['customer' => ['type' => 'company', 'id' => $companyId]],
    'phase_id'      => config('teamleader.deal_phases.qualified'),
    'department_id' => config('teamleader.departments.sales'),
]);
```

## Checking the configuration

```bash
php artisan teamleader:config:validate          # what is missing or wrong
php artisan teamleader:config:validate --fix    # with how to fix it
```

See [Artisan commands](../guides/artisan-commands.md).
