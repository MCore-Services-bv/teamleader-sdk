# Events and Logging

The SDK fires Laravel events for every request it sends, every answer it gets,
every failure, every rate-limit wait and every token refresh. Listen to them to
log, measure or alert — without wrapping the SDK.

{% hint style="info" %}
Available from v3.0.
{% endhint %}

## The events

All are in `McoreServices\TeamleaderSDK\Events`. Every event has a `connection` property — `default` for a single account; see
[Multiple Connections](multiple-connections.md). None carries a token value,
and request and response bodies have tokens, secrets and other sensitive keys
redacted before the event is created.

| Event | Fired when | Properties |
|---|---|---|
| `RequestSending` | A request is about to be sent — once per attempt, so a retry fires it again | `method`, `endpoint`, `body` |
| `ResponseReceived` | The API answered, with any status | `method`, `endpoint`, `statusCode`, `durationMs`, `body`; `successful()` |
| `RequestFailed` | An error status, no answer at all, no access token, or the rate-limit wait ran out | `method`, `endpoint`, `statusCode` (null when the API was not reached), `message`, `exception` |
| `RateLimitWaited` | The SDK held a request back to stay inside the rate limit | `waitedMs`, `usagePercentage`, `endpoint`, `gaveUp` |
| `TokenRefreshed` | The access token was refreshed and stored | `expiresIn` |
| `TokenRefreshFailed` | A refresh failed | `reason`, `statusCode`, `reauthorizationRequired` |

`RequestFailed` fires whether `TEAMLEADER_THROW_EXCEPTIONS` is on or off, so a
listener sees every failure either way.

An error status fires `ResponseReceived` and then `RequestFailed`. A request the
SDK refuses to build — an unknown filter, a missing required field — throws
before anything is sent and fires nothing.

## Listening

```php
use Illuminate\Support\Facades\Event;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;

Event::listen(function (ResponseReceived $event) {
    if ($event->durationMs > 2000) {
        logger()->warning("Slow Teamleader call: {$event->endpoint}", [
            'ms' => $event->durationMs,
        ]);
    }
});
```

### Alert when an account needs reconnecting

`TokenRefreshFailed` with `reauthorizationRequired` is the one moment a person
has to act: Teamleader refused the refresh token, the stored tokens were
cleared, and nothing works until someone connects the account again.

```php
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;

Event::listen(function (TokenRefreshFailed $event) {
    if ($event->reauthorizationRequired) {
        Notification::route('mail', 'ops@example.com')
            ->notify(new TeamleaderNeedsReconnecting($event->reason));
    }
});
```

### Cost

An event nobody listens to is not built: the SDK checks for a listener first,
so the request body is not copied or sanitised. With no listeners, the events
cost one lookup per request.

## Logging

```dotenv
TEAMLEADER_LOG_CHANNEL=teamleader
TEAMLEADER_LOG_REQUESTS=false
TEAMLEADER_LOG_RESPONSES=false
```

**`TEAMLEADER_LOG_CHANNEL`** sends all SDK log output to one channel from
`config/logging.php`. Unset, the SDK uses your default channel. A separate
channel makes it easy to keep SDK output out of your main log, or to turn its
level down:

```php
// config/logging.php
'teamleader' => [
    'driver' => 'daily',
    'path' => storage_path('logs/teamleader.log'),
    'level' => env('TEAMLEADER_LOG_LEVEL', 'info'),
    'days' => 14,
],
```

**`TEAMLEADER_LOG_REQUESTS`** and **`TEAMLEADER_LOG_RESPONSES`** write every
request and response body to that channel at `debug` level. They are off by
default: bodies hold your customers' names, addresses and email addresses, and
logging them is a decision to make deliberately. Tokens are always redacted.

Both are implemented as listeners on `RequestSending` and `ResponseReceived`,
so the log shows exactly what your own listeners receive.

## Recent calls

`TeamleaderSDK::getApiCalls()` returns the last 100 calls in the current
process — method, endpoint, status, response size and duration. It is meant
for a quick look in Tinker. For anything you keep, listen to
`ResponseReceived`.
