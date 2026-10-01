# Token Storage and Security

How the SDK stores and refreshes OAuth tokens, and what to do to protect them
in production.

## Two layers

| Layer | Where | Purpose |
|---|---|---|
| Token store | `teamleader_tokens` table, one row per connection | Source of truth; survives restarts and cache flushes |
| Cache | Your default Laravel cache store | Fast reads on every request |

Reads check the cache first and fall back to the store. Writes go to the store
first, then to the cache, so a lost cache is recoverable and a lost row means
connecting again.

**Both layers are encrypted** with your application key (`APP_KEY`), using
Laravel's encrypter. Neither the table nor the cache store holds a readable
token.

### The table

Created by a migration that ships with the package:

```bash
php artisan migrate
```

```
teamleader_tokens
├── id                 bigint, primary key
├── connection         varchar(100), unique — 'default' for a single account
├── access_token       text, encrypted
├── refresh_token      text, encrypted, nullable
├── token_type         varchar(50), default 'Bearer'
├── expires_in         integer
├── expires_at         timestamp, indexed
├── status             varchar(32) — 'connected' or 'needs_reauthorization'
├── account_id         varchar, nullable — the connected Teamleader account
├── account_name       varchar, nullable
├── last_refreshed_at  timestamp, nullable
├── created_at         timestamp
└── updated_at         timestamp
```

To change the migrations, publish them first:
`php artisan vendor:publish --tag=teamleader-migrations`.

Credentials of connections added with `teamleader:connections:add` are kept in
a second table, `teamleader_connections`, with the client ID and secret
encrypted the same way.

### Cache keys

| Key | Holds | Lifetime |
|---|---|---|
| `teamleader:{connection}:tokens` | The token pair and expiry, encrypted | Until 2 minutes before the access token expires (at least 60 s) |
| `teamleader:{connection}:refresh_lock` | Refresh lock | 60 s |

### Storing tokens elsewhere

The table is the default `TokenStore`. To keep tokens somewhere else — a
secrets manager, your own tenants table — implement
`McoreServices\TeamleaderSDK\Tokens\TokenStore` and bind it:

```php
$this->app->singleton(TokenStore::class, VaultTokenStore::class);
```

Your implementation is then responsible for protecting the values at rest. The
SDK still caches them, encrypted.

## Refresh

An access token with less than **15 minutes** left is refreshed before the
request goes out:

```
getValidAccessToken()
 ├── read the cache, fall back to the database
 ├── less than 15 minutes left?
 │    └── take the refresh lock (Cache::add, atomic)
 │         ├── another process holds it → wait, then read the new token
 │         └── this process holds it
 │              ├── POST /oauth2/access_token with the refresh token
 │              ├── write the new tokens to the database, then the cache
 │              └── release the lock
 └── return the access token
```

The lock stops several queue workers refreshing at once and overwriting each
other's tokens. **It needs a cache store shared by every process** — Redis,
Memcached or the database store. The file and array stores do not coordinate
across workers.

If Teamleader answers the refresh with a 400 or 401, the refresh token has been
revoked. The connection is marked `needs_reauthorization` and its tokens are
kept but never used; a user has to connect it again. Tokens are also renewed on
a schedule — see [Authentication](../getting-started/authentication.md#token-refresh).

## `APP_KEY` protects the tokens

Anyone with `APP_KEY` and a copy of the table can read the tokens, and a
refresh token gives full API access to the connected Teamleader account until
it is revoked. So:

- Keep `APP_KEY` out of version control and different per environment.
- **Rotating `APP_KEY` makes the stored tokens unreadable.** The SDK reports a
  `TokenStorageException` naming the connection. Either connect the account
  again, or add the old key to `APP_PREVIOUS_KEYS` during the rotation — the
  SDK re-encrypts each token with the new key the next time it is refreshed.
- A database backup is only as sensitive as the key it can be combined with:
  store backups and `.env` apart.

Rows written by v2.x are plain text. They are encrypted automatically the
first time the SDK reads them after the upgrade.

## Production checklist

- [ ] Cache store is Redis (or another shared store), not `file` or `array`
- [ ] Redis has a password and TLS
- [ ] `php artisan migrate` has run after installing or upgrading the SDK
- [ ] `.env` is not in version control, and `APP_KEY` differs per environment
- [ ] `TEAMLEADER_THROW_EXCEPTIONS` is not set to `false` (the default is `true`), so failures are noticed
- [ ] Token refresh log entries are monitored

## Diagnostics

```php
use McoreServices\TeamleaderSDK\Services\TokenService;

$tokens = app(TokenService::class);

$tokens->getTokenInfo();
// [
//   'has_access_token' => true,
//   'has_refresh_token' => true,
//   'expires_at' => '2026-09-30 14:30:00',
//   'expires_in' => 847,
//   'needs_refresh' => false,
//   'connection' => 'default',
//   'status' => 'connected',
//   'token_source' => 'cache',          // 'cache', 'database' or 'none'
//   'cache_has_tokens' => true,
//   'database_has_tokens' => true,
//   'storage_sync' => [...],
// ]

$tokens->hasValidTokens();      // bool
$tokens->syncTokensToCache();   // re-cache from the database, after a cache flush
$tokens->clearTokens();         // remove from cache and database
```

`php artisan teamleader:status` shows the same information.

## If a token leaks

1. **Revoke access** for the integration in the Teamleader account.
2. **Clear the stored tokens:** `Teamleader::logout()`, or
   `app(TokenService::class)->clearTokens()` from Tinker.
3. **Rotate the client secret** in the developer portal if it may have leaked
   too, and update `TEAMLEADER_CLIENT_SECRET`.
4. **Connect again** through your OAuth route.
5. **Review** what the token was used for in the Teamleader account's audit
   information.
