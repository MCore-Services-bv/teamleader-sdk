# Authentication

The SDK uses Teamleader's OAuth 2.0 authorization-code flow. You add two
routes; the SDK exchanges the code, stores the tokens and refreshes them from
then on.

## The two routes

```php
// routes/web.php
use Illuminate\Http\Request;
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// 1. Send the user to Teamleader to authorise your integration
Route::get('/teamleader/connect', function () {
    return Teamleader::authorize();
});

// 2. Teamleader sends the user back here with a code
Route::get('/teamleader/callback', function (Request $request) {
    if (Teamleader::handleCallback($request->query('code'), $request->query('state'))) {
        return response('Connected to Teamleader. You can close this tab and run: php artisan teamleader:status');
    }

    return response('Connecting to Teamleader failed. Check storage/logs/laravel.log.', 500);
});
```

These work in a fresh Laravel application, without login or pages of your
own. Open `/teamleader/connect` in the browser, approve the integration in
Teamleader, and check the result with `php artisan teamleader:status`.

{% hint style="warning" %}
**Anyone who can open `/teamleader/connect` can connect a Teamleader account
to your application.** Once the application is reachable by others, put both
routes behind your login (`->middleware('auth')`) and redirect to a page of
your own. Setting `expected_account_id` (see below) also stops the wrong
account from being connected.
{% endhint %}

The callback path must match `TEAMLEADER_REDIRECT_URI` and the redirect URI of
your integration exactly. Both routes need the `web` middleware group, for
the session. Routes in `routes/web.php` have it already.

### The `state` check

`authorize()` generates a random `state`, remembers it in the session, and
sends it to Teamleader. `handleCallback()` only accepts a callback whose
`state` this session issued, and each state works once. A mismatch throws
`OAuthStateException` and nothing is stored: that is what stops another site
from completing the flow with its own authorization code and connecting your
application to the wrong Teamleader account.

`authorize('your-state')` and `getAuthorizationUrl('your-state')` work as in
2.x: the SDK does not store or check a state you pass in.

`authorize()` returns a redirect response. When you need the URL itself, for
a button for example, use `Teamleader::getAuthorizationUrl()`. It generates
and remembers a state the same way.

### Several accounts, one callback

With [multiple connections](../guides/multiple-connections.md), start the flow
on the connection and keep the one callback route:

```php
Route::get('/teamleader/{connection}/connect', fn (string $connection) =>
    Teamleader::connection($connection)->authorize()
);

Route::get('/teamleader/callback', function (Request $request) {
    $connected = Teamleader::handleCallback($request->query('code'), $request->query('state'));

    if ($connected) {
        return response("Connected '{$connected->connectionName()}'. Run: php artisan teamleader:status --all");
    }

    return response('Connecting to Teamleader failed. Check storage/logs/laravel.log.', 500);
});
```

The state remembers which connection started the flow, so the callback stores
the tokens on that connection. Register the same redirect URI in every
integration.

### Making sure the right account is connected

`handleCallback()` identifies the account that was connected (`users.me`) and
stores its id and name with the tokens. Set `expected_account_id` on a
connection (`TEAMLEADER_EXPECTED_ACCOUNT_ID` for the default one), and a
callback that connects any other account throws `AccountMismatchException`
without storing anything. With several environments, the easy mistake is being
logged into the wrong Teamleader account in the browser while connecting; this
makes that mistake impossible to save.

The first time, connect without it, then read the id from
`php artisan teamleader:status --all` and add it. For a connection stored with
`teamleader:connections:add`, one command does both:
`php artisan teamleader:connections:expect {name} --current`.

### What `handleCallback()` returns

The SDK instance of the connection that was connected, which is truthy, so
the `if` above works. It returns `false` when the code exchange failed and
`TEAMLEADER_THROW_EXCEPTIONS` is off. A `ConnectionAuthorized` event is fired
on success.

## Checking the connection

```php
if (Teamleader::isAuthenticated()) {
    // Tokens are stored and valid, or can be refreshed
}
```

From the command line:

```bash
php artisan teamleader:status
```

## Token refresh

Access tokens are refreshed before they run out, two ways:

- **On a schedule.** The package schedules `teamleader:tokens:refresh` every
  ten minutes, which renews every connection whose token expires within 30
  minutes. A connection nobody uses stays connected, and a revoked refresh
  token is found before a real request needs it. **This needs the Laravel
  scheduler to run** — `php artisan schedule:work` locally, a cron entry for
  `schedule:run` in production (Forge sets one up under *Scheduler*).
- **On demand**, when a request finds less than 15 minutes left.

Both use the same per-connection lock, so they never refresh a connection
twice. That matters, because each refresh returns a new refresh token.

```bash
php artisan teamleader:tokens:refresh                     # every connection that is due
php artisan teamleader:tokens:refresh --connection=ghent  # one
php artisan teamleader:tokens:refresh --force             # even when not due
```

`TEAMLEADER_TOKENS_AUTO_REFRESH=false` turns the schedule off;
`TEAMLEADER_TOKENS_REFRESH_BEFORE` (seconds, default 1800) sets how early.

### When Teamleader refuses the refresh token

A revoked or long-unused refresh token cannot be renewed. The connection is
marked **`needs_reauthorization`** and a `TokenRefreshFailed` event with
`reauthorizationRequired` is fired. The tokens are kept for inspection, not
deleted. From then on, every request on that connection throws
`ConnectionNeedsReauthorizationException` naming it, whatever
`TEAMLEADER_THROW_EXCEPTIONS` says. Send a user through `authorize()` for that
connection to fix it.

To be alerted, listen for the event. See
[Events and Logging](../guides/events-and-logging.md#alert-when-an-account-needs-reconnecting).

### Status

```bash
php artisan teamleader:status --all
```

```
 Connection  Account              Status                 Expires in   Last refresh
 antwerp     Klant Antwerpen      connected              41 min       3 minutes ago
 default     MCore Services       connected              52 min       3 minutes ago
 ghent       Klant Gent           needs_reauthorization  —            2 days ago
```

It exits with 1 when any connection needs to be connected again, so it can
run in a deploy check. `teamleader:health` reports the same, and warns when a
token expired without being renewed. That usually means the scheduler is
not running.

How tokens are stored, and how to harden that for production, is covered in
[Token storage and security](../guides/token-storage-and-security.md).

## Disconnecting

```php
Teamleader::logout();
```

This clears the tokens stored by the SDK. To revoke access on Teamleader's
side as well, remove the integration's access from the Teamleader account.

## Using a token you already have

For scripts and tests, a token can be set directly. It bypasses the stored
tokens and is not refreshed:

```php
Teamleader::setAccessToken($token);
```
