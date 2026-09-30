# Authentication

The SDK uses Teamleader's OAuth 2.0 authorization-code flow. You add two
routes; the SDK exchanges the code, stores the tokens and refreshes them from
then on.

## The two routes

```php
// routes/web.php
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// 1. Send the user to Teamleader to authorise your integration
Route::get('/teamleader/connect', function (Request $request) {
    $state = Str::random(40);
    $request->session()->put('teamleader_oauth_state', $state);

    return Teamleader::authorize($state);
})->middleware('auth');

// 2. Teamleader sends the user back here with a code
Route::get('/teamleader/callback', function (Request $request) {
    $expected = $request->session()->pull('teamleader_oauth_state');

    abort_unless($expected && hash_equals($expected, (string) $request->query('state')), 403);

    if (Teamleader::handleCallback($request->query('code'), $request->query('state'))) {
        return redirect('/dashboard')->with('success', 'Connected to Teamleader.');
    }

    return redirect('/settings')->with('error', 'Connecting to Teamleader failed.');
})->middleware('auth');
```

The callback path must match `TEAMLEADER_REDIRECT_URI` and the redirect URI of
your integration exactly.

{% hint style="warning" %}
**Check the `state` parameter yourself.** The SDK passes it through to
Teamleader and back but does not store or compare it. Without the check above,
another site could complete the flow with its own authorization code and
connect your application to the wrong Teamleader account.
{% endhint %}

`authorize()` returns a redirect response. If you need the URL itself — to
render a button, say — use `Teamleader::getAuthorizationUrl($state)`.

The callback is a `GET`, so Laravel's CSRF middleware does not apply to it.

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

Access tokens are refreshed automatically when they have less than 15 minutes
left. Refreshes are serialised with a cache lock, so concurrent workers do not
race each other. If Teamleader rejects the refresh token, the stored tokens are
cleared and the user has to connect again.

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
