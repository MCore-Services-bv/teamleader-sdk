# Multiple Connections

One Laravel application can talk to several Teamleader accounts — six
environments, a handful of clients, one per tenant. Each account is a
**connection** with its own credentials, tokens, refresh lock and rate-limit
window.

{% hint style="info" %}
Available from v3.0. Connecting several accounts through one callback route,
and scheduled token renewal, are coming in the following v3.0 steps.
{% endhint %}

## One integration per account

An integration is created inside one Teamleader account, and its client ID and
secret only work there. So every connection needs **its own integration** and
its own `client_id` and `client_secret`. There is no fallback between
connections for those two.

`redirect_uri` is the one value that can be shared: register the same callback
URL in each integration.

## Configuration

The flat keys you already have are the `default` connection. Add one block per
extra account:

```php
// config/teamleader.php
'client_id' => env('TEAMLEADER_CLIENT_ID'),
'client_secret' => env('TEAMLEADER_CLIENT_SECRET'),
'redirect_uri' => env('TEAMLEADER_REDIRECT_URI'),

'default' => env('TEAMLEADER_CONNECTION', 'default'),

'connections' => [
    'antwerp' => [
        'client_id' => env('TEAMLEADER_ANTWERP_CLIENT_ID'),
        'client_secret' => env('TEAMLEADER_ANTWERP_CLIENT_SECRET'),
        // redirect_uri left out: uses TEAMLEADER_REDIRECT_URI
    ],
    'ghent' => [
        'client_id' => env('TEAMLEADER_GHENT_CLIENT_ID'),
        'client_secret' => env('TEAMLEADER_GHENT_CLIENT_SECRET'),
    ],
],
```

A connection missing its `client_id` or `client_secret` fails with a
`ConfigurationException` that names it, the first time it is used.

A single-account application changes nothing.

## Usage

```php
Teamleader::companies()->list();                          // the default connection
Teamleader::connection('antwerp')->companies()->list();   // a named one

$ghent = Teamleader::connection('ghent');
$ghent->deals()->lazy(['status' => 'open'])->each(...);
```

Every resource made from a connection uses that connection. Each connection is
built once per process and reused.

`TEAMLEADER_CONNECTION` changes which connection `Teamleader::` uses without a
name.

## Connections defined at runtime

For accounts you keep in your own database:

```php
// One at a time
Teamleader::extend('tenant-42', fn () => [
    'client_id' => $tenant->teamleader_client_id,
    'client_secret' => decrypt($tenant->teamleader_client_secret),
]);

// Or every unknown name, through your own lookup — return null for "no such connection"
Teamleader::resolveConnectionsUsing(
    fn (string $name) => Tenant::where('slug', $name)->first()?->teamleaderConfig()
);
```

Call these from a service provider's `boot()` method.

## What each connection keeps apart

| | Per connection |
|---|---|
| Tokens | One row in `teamleader_tokens`, by `connection` |
| Cache | `teamleader:{connection}:tokens` |
| Refresh lock | `teamleader:{connection}:refresh_lock` — one account refreshing never blocks another |
| Rate-limit window | One per **client ID** — Teamleader allows 200 requests a minute per integration, so six accounts get six windows. The ID is hashed in the Redis key |
| Events | Every event has a `connection` property |
