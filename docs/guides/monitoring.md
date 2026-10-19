# Monitoring with Pulse

The SDK ships two [Laravel Pulse](https://laravel.com/docs/pulse) recorders
and three dashboard cards. Together they show what the SDK does in
production, per connection:

* **Teamleader Requests**: every endpoint you call, with how often, how fast,
  how many failed and why.
* **Teamleader Connections**: one row per connection, with traffic, error
  rate, rate-limit waits and token renewals. Connections that need someone to
  connect them again are listed at the top.
* **Teamleader Time**: per route, queued job and Artisan command, how much
  time went to Teamleader, its share of the whole, and how many calls were
  made. This card explains a page that takes nine seconds because it makes 14
  Teamleader calls.

> [!NOTE]
> Available from v3.4. Needs `laravel/pulse` 1.8 or later. Pulse is optional:
> without it, nothing under `McoreServices\TeamleaderSDK\Pulse` is loaded.

## Why not Pulse's own cards

Pulse's *Slow Outgoing Requests* card only sees requests sent with Laravel's
`Http` client. The SDK sends through Guzzle directly, so Teamleader traffic does
not show up there at all. Pulse's *Slow Requests* card shows that a page is
slow, but not that Teamleader is why.

## Install

If Pulse is not installed yet:

```bash
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

### 1. Add the recorders

In `config/pulse.php`, add the SDK's recorders to `recorders`:

```php
'recorders' => [
    // … Pulse's own recorders …

    \McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderRequests::class => [
        'enabled' => env('PULSE_TEAMLEADER_REQUESTS_ENABLED', true),
        'sample_rate' => env('PULSE_TEAMLEADER_REQUESTS_SAMPLE_RATE', 1),
        'threshold' => env('PULSE_TEAMLEADER_REQUESTS_THRESHOLD', 1000),
        'ignore' => [
            // '/^webhooks\./',
        ],
        'connections' => [
            'ignore' => [
                // '/^sandbox$/',
            ],
            'groups' => [
                // '/^site-.+$/' => 'site-*',
            ],
        ],
    ],

    \McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderCallers::class => [
        'enabled' => env('PULSE_TEAMLEADER_CALLERS_ENABLED', true),
        'sample_rate' => env('PULSE_TEAMLEADER_CALLERS_SAMPLE_RATE', 1),
        'ignore' => [
            // '#^/pulse#',
        ],
    ],
],
```

### 2. Add the cards to the dashboard

Publish Pulse's dashboard if you have not already:

```bash
php artisan vendor:publish --tag=pulse-dashboard
```

Then add the cards to `resources/views/vendor/pulse/dashboard.blade.php`:

```blade
<x-pulse>
    <livewire:pulse.servers cols="full" />

    <livewire:teamleader.requests cols="8" />
    <livewire:teamleader.connections cols="4" />
    <livewire:teamleader.callers cols="full" />

    {{-- … Pulse's own cards … --}}
</x-pulse>
```

`cols` and `rows` work as for Pulse's own cards. The connections card also
takes `limit` (default 10), the number of connections it lists, busiest
first: `<livewire:teamleader.connections cols="4" limit="25" />`.

Pulse records from every process that has the recorders configured: web
requests, queue workers and scheduled commands. Nothing else needs to run
beyond what Pulse itself needs.

## The cards

### Teamleader Requests

One row per connection and endpoint (`brand-a` · `invoices.draft`), with:

| Column | |
|---|---|
| Count | Answers from the API |
| Failed | Requests that did not succeed, with the reasons below the endpoint |
| Slow | Answers slower than `threshold` |
| Average, Slowest | Duration of the API call, in ms |

Failure reasons:

| Reason | Meaning |
|---|---|
| `HTTP 422`, `HTTP 404`, … | The API answered with that status |
| needs reconnecting | Teamleader refused the refresh token; nothing works until someone connects the account again |
| not connected | No tokens are stored for the connection, so nothing was sent |
| rate-limit wait ran out | The SDK waited for room in the rate limit longer than `rate_limiting.max_wait_ms`, and gave up |
| no answer | A timeout or connection error: the API was never reached |

Sort by count, failures, slow, average or slowest. With more than one
connection, a **Connection** filter appears. Like Pulse's own cards, a card
shows at most 100 rows.

### Teamleader Connections

One row per connection with its requests, failures and error rate (failures
out of all attempts, including those that never reached the API), rate-limit
waits (how many, and the time spent waiting), average duration and token
renewals.

Above the table, every connection whose last known state is not healthy:

| State | Set when | Cleared when |
|---|---|---|
| **Needs reconnecting** | A refresh was refused (`TokenRefreshFailed` with `reauthorizationRequired`), or a request was refused because of it | The account is connected again (`ConnectionAuthorized`), a token is renewed, or the API answers a request on the connection |
| **Not connected** | A request was made on a connection that has no tokens | The same |

Each process writes a connection's state at most once a minute, so after
reconnecting, the card can take up to a minute (plus Pulse's own polling) to
catch up. A connection that was removed while flagged stays listed until Pulse
trims its data (seven days by default) or `php artisan pulse:clear
--type=teamleader_connection_state`.

These are listed by their real connection name, also when connections are
grouped (see below), and whatever the `limit`: a connection that needs
someone to act is never hidden.

### Teamleader Time

One row per route, queued job and Artisan command that called Teamleader:

| Column | |
|---|---|
| Count | Runs of the route, job or command |
| Calls | Average number of Teamleader calls per run (hover for the most in one run) |
| Average | Average time spent on Teamleader per run: API calls (including ones that timed out) plus rate-limit waits. The pause between retry attempts is not included. |
| Share | That time as a share of the run's total duration |
| Slowest | The most time spent on Teamleader in one run |

A route with a high share and many calls is a candidate for
[sideloading](sideloading.md), [`lazy()`](pagination.md), a cache, or a
[queued bulk operation](bulk-operations.md).

How runs are counted:

* A queued job is its own caller. A job on the `sync` queue is part of
  whatever dispatched it.
* A command started with `Artisan::call()`, from a web request, a job or
  another command, is a caller of its own, and its time also counts toward
  whatever started it. A command started with `$this->call()` from another
  command is part of that command: Laravel fires no command events for it.
* Worker commands (`queue:work`, `horizon`, `octane:start`, `schedule:work`,
  …) are never callers themselves. The jobs and requests they run are.

## Configuration

### `TeamleaderRequests`

| Key | Default | |
|---|---|---|
| `sample_rate` | `1` | The share of successful requests recorded, between 0 and 1. Failures, rate-limit waits and token events are always recorded. Counts on the cards are scaled back up and shown as `~1,200`. |
| `threshold` | `1000` | When an answer counts as slow, in ms. Or per endpoint: `['/^files\./' => 5000, 'default' => 1000]` |
| `ignore` | `[]` | Patterns matched against the endpoint. Matching requests are not recorded. |
| `connections.ignore` | `[]` | Patterns matched against the connection name. Nothing from a matching connection is recorded. |
| `connections.groups` | `[]` | Patterns mapped to one name, to record several connections as one row. |

### `TeamleaderCallers`

| Key | Default | |
|---|---|---|
| `sample_rate` | `1` | The share of runs recorded |
| `ignore` | `[]` | Patterns matched against the route path (`/admin/quotations`), job class or command name |

### Many connections

An application with a connection per customer or per site can have hundreds
of connections, often named after an id. Group them, so the cards show one row
for all of them:

```php
'connections' => [
    'groups' => [
        '/^site-.+$/' => 'site-*',
    ],
],
```

The first matching pattern wins, and the replacement may use the pattern's
groups: `'/^(brand)-(.+)$/' => '$1-*'`. Connection states are never grouped:
*Needs reconnecting* names the exact connection.

## What is recorded

Request and response bodies are never read or stored, and neither are tokens
or query parameters. The recorders store endpoint names, connection names,
durations, status codes and counts. A connection's state also keeps the
reason Teamleader or the SDK gave, such as `invalid_grant`.

All Pulse entry types start with `teamleader_`, so they can be cleared on their
own:

```bash
php artisan pulse:clear --type=teamleader_request --type=teamleader_failure
```

| Type | Key | Value |
|---|---|---|
| `teamleader_request` | `[connection, endpoint]` | Duration in ms |
| `teamleader_slow_request` | `[connection, endpoint]` | Duration in ms |
| `teamleader_connection_request` | connection | Duration in ms |
| `teamleader_failure` | `[connection, endpoint, reason]` | |
| `teamleader_connection_failure` | connection | |
| `teamleader_connection_unanswered` | connection | (failures that never reached the API) |
| `teamleader_rate_limit_wait` | connection | ms waited |
| `teamleader_rate_limit_gave_up` | connection | |
| `teamleader_token_refreshed` | connection | |
| `teamleader_token_refresh_failed` | connection | |
| `teamleader_caller_time` | `[kind, name, via]` | Teamleader ms in one run |
| `teamleader_caller_duration` | `[kind, name, via]` | Total ms of that run |
| `teamleader_caller_calls` | `[kind, name, via]` | Calls in that run |
| `teamleader_connection_state` (a Pulse value) | connection | `{"state", "reason", "at"}` |

## Cost

The recorders only listen to the SDK's [events](events-and-logging.md); they
change nothing about how requests are sent. Per API call they add two or three
Pulse entries, which Pulse buffers and writes at the end of the request or
job.

The `ResponseReceived` event builds its sanitised body only when a listener
reads it, and the recorders never do. Redacting a 100-row listing takes tens
of milliseconds, so with Pulse installed this matters: that cost is not paid.

For large bulk runs, consider a `sample_rate` below 1 for `TeamleaderRequests`,
or Pulse's Redis ingest so workers do not write to the database themselves:

```dotenv
PULSE_INGEST_DRIVER=redis
```

and run `php artisan pulse:work`. See
[Pulse's documentation](https://laravel.com/docs/pulse) on ingesting.

## Pulse's cache card and token keys

Pulse's *Cache* card lists the SDK's token keys, one per connection:
`teamleader:{connection}:tokens`. Two things to know when reading it:

* The scheduled `teamleader:tokens:refresh` command (every ten minutes) reads
  each connection's tokens from the database rather than the cache, so a
  connection that is used rarely shows about six misses an hour and few hits.
  That is by design: it keeps one process from renewing with an old copy.
* To show them as one row, group them in Pulse's cache recorder:

```php
\Laravel\Pulse\Recorders\CacheInteractions::class => [
    // …
    'groups' => [
        '/^teamleader:(.+):tokens$/' => 'teamleader:*:tokens',
        // … Pulse's default groups …
    ],
],
```

## Testing

The recorders are ordinary listeners, so the SDK's events can be fired in a
test with Pulse installed and the entries read back after `Pulse::ingest()`.
With [`Teamleader::fake()`](testing.md), no request is sent and no event is
fired, so Pulse records nothing for faked calls.
