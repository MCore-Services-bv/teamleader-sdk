# Multiple Connections

One Laravel application can talk to several Teamleader accounts: six
environments, a handful of clients, or one per tenant. Each account is a
**connection** with its own credentials, tokens, refresh lock and rate-limit
window.

{% hint style="info" %}
Available from v3.0. Connecting each account is covered in
[Authentication](../getting-started/authentication.md#several-accounts-one-callback).
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

## Connections stored in the database

Twelve environment variables for six accounts works, but adding a seventh
account then means a deploy. Instead, store a connection's credentials in the
database:

```bash
php artisan teamleader:connections:add bruges     # prompts for client ID and secret
php artisan teamleader:connections:list           # with each account and its id
php artisan teamleader:connections:rename bruges brugge   # keeps credentials and tokens
php artisan teamleader:connections:remove bruges  # also removes its tokens
```

Client ID and secret are encrypted with `APP_KEY` in the
`teamleader_connections` table (created by `php artisan migrate`). For scripts,
pass `--client-id=`, `--client-secret=` and optionally `--redirect-uri=` and
`--expected-account=`.

Without `--redirect-uri`, a stored connection uses `TEAMLEADER_REDIRECT_URI`.
When that is not set either, the command asks for the redirect URI. Nothing is
stored until every value checks out, so a failed run leaves the table as it
was.

Adding an account then takes four steps: create the integration in that
Teamleader account, run `connections:add`, connect it through your OAuth
route, and pin the account it connected to:

```bash
php artisan teamleader:connections:expect bruges --current
php artisan teamleader:connections:expect --all --current   # every stored connection
```

From then on a callback that connects any other account for `bruges` is
refused and nothing is stored. `--current` reads the account from the
connection's tokens, so the credentials are not asked again. Pass an id
instead (`connections:expect bruges 0ab6c2…`) or `--clear` to remove it.

### Renaming

`teamleader:connections:rename {from} {to}` moves the stored credentials and
the tokens in one transaction, so the connection stays connected. The
rate-limit window belongs to the integration, not the name, and is
unaffected. Update what names the connection yourself: code that calls
`Teamleader::connection('old')`, `TEAMLEADER_CONNECTION`, scheduled commands
with `--connection`, and queued bulk jobs that have not run yet.

A connection defined in `config/teamleader.php` is renamed there. Then run
`connections:rename old new --tokens-only` to move its tokens along.

### Without any credentials in `.env`

The first connection can be stored too. Either name it `default`:

```bash
php artisan teamleader:connections:add default
```

or give it a name and make that the default connection:

```dotenv
TEAMLEADER_CONNECTION=antwerp
```

Either way, `Teamleader::companies()` then uses it without a name.

### Only named connections

An application can also have no default at all. Then `teamleader:status`
shows every connection, `teamleader:health` checks each named connection
under *Connections*, and the data commands (`list`, `info`, `export`, …) ask
for `--connection=`. `Teamleader::connection('name')` works as always; code
that uses `Teamleader::` without a name needs `TEAMLEADER_CONNECTION`.

A connection in `config/teamleader.php` always wins over one with the same
name in the database; the commands refuse to add or remove such a name.

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

## Keeping every connection alive

`teamleader:tokens:refresh` runs every ten minutes and renews each connection
that is due, and `teamleader:status --all` shows them side by side. See
[Token refresh](../getting-started/authentication.md#token-refresh).
