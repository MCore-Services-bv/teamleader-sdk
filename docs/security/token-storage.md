# Token Storage & Security

How the SDK stores, refreshes, and protects OAuth tokens — and how to harden that storage for production.

---

## How Token Storage Works

The SDK uses a **two-layer storage strategy**:

| Layer | Driver | Purpose |
|---|---|---|
| **Cache** | Laravel Cache (configurable) | Fast reads on every API request |
| **Database** | `teamleader_tokens` table | Persistent source of truth across restarts |

Every read checks cache first and falls back to the database. Every write goes to the database first, then the cache. This means the database is always authoritative — a cache miss is recoverable; a database miss requires re-authentication.

### The `teamleader_tokens` Table

The table is **created automatically** on the first successful OAuth callback. No `php artisan migrate` is needed.

```
teamleader_tokens
├── id              bigint (PK)
├── access_token    text
├── refresh_token   text (nullable)
├── token_type      varchar(50)  default 'Bearer'
├── expires_in      integer
├── expires_at      timestamp    ← indexed
├── created_at      timestamp
└── updated_at      timestamp    ← indexed
```

> **Only one row is ever kept.** On each token refresh the existing row is updated in-place. There is no token history.

### Cache Keys

| Key | Content | TTL |
|---|---|---|
| `teamleader_access_token` | Access token string | `expires_in − 120 s` (min 60 s) |
| `teamleader_access_token_expires_at` | Expiry timestamp | Same as above |
| `teamleader_refresh_token` | Refresh token string | 7 days |
| `teamleader_refresh_lock` | Distributed lock flag | 60 s |

---

## Token Refresh Lifecycle

Tokens are refreshed automatically when they have **less than 15 minutes** remaining.

```
getValidAccessToken()
    │
    ├── read cache
    │       └── miss? read database → re-cache
    │
    ├── token expires within 15 min?
    │       └── yes → acquire distributed lock (Redis)
    │               ├── another worker already refreshing? → wait, then read DB
    │               └── this worker wins the lock
    │                       ├── read refresh_token from DB
    │                       ├── POST /oauth2/access_token
    │                       ├── write new tokens to DB first
    │                       ├── write to cache
    │                       └── release lock
    │
    └── return access_token
```

### Distributed Lock

The refresh lock (`teamleader_refresh_lock`) is set via `Cache::add()` — an atomic operation that only succeeds for the first caller. This prevents multiple Laravel Horizon workers from refreshing simultaneously and racing to write conflicting tokens.

**This requires a shared cache driver (Redis or database).** File cache is per-process and will not coordinate across workers.

### Invalid Refresh Token

If the OAuth server returns HTTP 400 or 401 during a refresh, the SDK assumes the refresh token has been revoked. It calls `clearTokens()` immediately — wiping both cache and database — and returns `null`. The next API call will fail with an unauthenticated error, prompting re-authorisation.

---

## Default Security Posture

By default, tokens are stored **as plain text** in both the cache and the database. This is fine for development but requires hardening for production.

| Storage | Default | Recommendation |
|---|---|---|
| Cache | Plain text in configured driver | Use Redis with TLS/SSL |
| Database | Plain text `text` columns | Add Laravel encrypted casts |
| Cache driver | Often `file` in dev | Must be `redis` or `database` in production |

---

## Production Hardening

### 1. Use Redis for Cache

File cache is per-process and stores tokens in plain text on disk. Redis is required for multi-worker deployments and strongly recommended for all production use.

```env
CACHE_DRIVER=redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379
REDIS_SCHEME=tls
```

```php
// config/database.php — enable TLS for the Redis connection
'default' => [
    'host'     => env('REDIS_HOST', '127.0.0.1'),
    'password' => env('REDIS_PASSWORD'),
    'port'     => env('REDIS_PORT', '6379'),
    'scheme'   => env('REDIS_SCHEME', 'tcp'),  // set to 'tls' in production
    'database' => env('REDIS_DB', '0'),
],
```

### 2. Encrypt Tokens at Rest in the Database

Add encrypted casts to a model wrapping the `teamleader_tokens` table, or apply encryption in the `TokenService` directly. Laravel's `encrypted` cast uses your `APP_KEY`:

```php
// If you wrap the table in a model:
protected $casts = [
    'access_token'  => 'encrypted',
    'refresh_token' => 'encrypted',
];
```

### 3. Rotate Your `APP_KEY`

Laravel uses `APP_KEY` for encryption. If this key is ever exposed, rotate it immediately:

```bash
php artisan key:generate
```

After rotating, any tokens encrypted with the old key become unreadable — `clearTokens()` and re-authorise.

### 4. Restrict Database Access

Use a principle-of-least-privilege database user for the application. The SDK only reads and updates a single row — it does not need DDL privileges in production (the table is created on first run).

---

## Production Security Checklist

- [ ] `CACHE_DRIVER` is `redis` (not `file` or `array`)
- [ ] Redis is password-protected with TLS enabled
- [ ] `APP_KEY` is unique, strong, and not committed to version control
- [ ] `.env` is excluded from version control (`.gitignore`)
- [ ] `storage/` directory permissions are `770` (not world-readable)
- [ ] Encrypted casts applied to `access_token` and `refresh_token`
- [ ] Database encryption-at-rest is enabled
- [ ] Application database user has no DDL privileges in production
- [ ] Token-refresh log events are monitored for anomalies

---

## Diagnostic Tools

### Check Token Status

```php
use McoreServices\TeamleaderSDK\Services\TokenService;

$tokenService = app(TokenService::class);

// Summary of all storage locations
$info = $tokenService->getTokenInfo();
// Returns:
// [
//   'has_access_token'    => true,
//   'has_refresh_token'   => true,
//   'expires_at'          => '2025-05-12 14:30:00',
//   'expires_in'          => 847,          // seconds remaining
//   'needs_refresh'       => false,
//   'token_source'        => 'cache',      // 'cache' | 'database' | 'none'
//   'cache_has_tokens'    => true,
//   'database_has_tokens' => true,
//   'storage_sync'        => [...],
// ]

// Simple boolean check (with 5-minute buffer)
$valid = $tokenService->hasValidTokens();
```

### Artisan Commands

```bash
# Show token status and expiry
php artisan teamleader:token

# Force a token refresh
php artisan teamleader:token --refresh

# Revoke and clear all tokens (forces re-authentication)
php artisan teamleader:token --revoke
```

### Force Sync Cache from Database

If you suspect the cache is stale (e.g. after a cache flush):

```php
$tokenService->syncTokensToCache();
```

### Clear All Tokens

```php
$tokenService->clearTokens();
// Clears: cache keys + all rows in teamleader_tokens
```

---

## If Tokens Are Compromised

**Step 1 — Revoke access in Teamleader immediately**

Go to Teamleader Focus → Marketplace → your integration → Disconnect.

**Step 2 — Clear all stored tokens**

```bash
php artisan tinker
>>> app(\McoreServices\TeamleaderSDK\Services\TokenService::class)->clearTokens();
>>> exit
php artisan cache:clear
```

**Step 3 — Rotate your application key (if DB encryption is in use)**

```bash
php artisan key:generate
```

**Step 4 — Re-authorise**

Direct your application through the OAuth flow again:

```php
// In a controller or artisan command:
return Teamleader::authorize();
```

**Step 5 — Review access logs** for any API calls made with the compromised token.

---

## Related

- [[Home]] — SDK overview and all resource links
- [Teamleader Developers](https://developer.focus.teamleader.eu/docs/introduction) — manage OAuth app credentials
- [Laravel Encryption](https://laravel.com/docs/encryption) — `APP_KEY` and encrypted casts
- [Redis Security](https://redis.io/docs/management/security/) — TLS, passwords, ACLs
