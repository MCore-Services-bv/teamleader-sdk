# Token Storage and Security

How the SDK stores and refreshes OAuth tokens, and what to do to protect them
in production.

## Two layers

| Layer | Where | Purpose |
|---|---|---|
| Database | `teamleader_tokens` table | Source of truth; survives restarts and cache flushes |
| Cache | Your default Laravel cache store | Fast reads on every request |

Reads check the cache first and fall back to the database. Writes go to the
database first, then to the cache, so a lost cache is recoverable and a lost
database row means connecting again.

### The table

Created automatically the first time tokens are stored — no migration needed.
Only one row is ever kept; a refresh updates it in place.

```
teamleader_tokens
├── id             bigint, primary key
├── access_token   text
├── refresh_token  text, nullable
├── token_type     varchar(50), default 'Bearer'
├── expires_in     integer
├── expires_at     timestamp, indexed
├── created_at     timestamp
└── updated_at     timestamp, indexed
```

### Cache keys

| Key | Holds | Lifetime |
|---|---|---|
| `teamleader_access_token` | Access token | Token lifetime minus 2 minutes (at least 60 s) |
| `teamleader_refresh_token` | Refresh token | 7 days |
| `teamleader_refresh_lock` | Refresh lock | 60 s |

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
revoked. The SDK clears all stored tokens, and the user has to connect again.

## Tokens are stored unencrypted

The SDK writes the tokens as plain text to both the table and the cache. A
refresh token gives full API access to the connected Teamleader account until
it is revoked, so protect both stores accordingly:

- **Cache:** use Redis with a password and TLS (`REDIS_SCHEME=tls`), not the
  file store — which also writes the tokens to disk in `storage/`.
- **Database:** enable encryption at rest, and keep backups under the same
  access rules as the live database.
- **Access:** give the application's database user only the privileges it
  needs.

Encrypting the tokens inside the SDK is on the list for v3.0.

## Production checklist

- [ ] Cache store is Redis (or another shared store), not `file` or `array`
- [ ] Redis has a password and TLS
- [ ] Database encryption at rest is on
- [ ] `.env` is not in version control, and `APP_KEY` differs per environment
- [ ] `TEAMLEADER_THROW_EXCEPTIONS=true`, so a failed refresh is noticed
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
