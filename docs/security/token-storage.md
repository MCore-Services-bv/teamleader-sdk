# Token Storage Security

## Overview

The Teamleader SDK stores OAuth tokens in two layers:
1. **Cache Layer** - For fast access and token refresh coordination
2. **Database Layer** - For persistent storage

## Security Considerations

### Development Environment

In development, the default Laravel cache driver is often `file` or `array`:

```env
CACHE_DRIVER=file  # Not secure for production!
```

**Risks:**
- File cache stores tokens in plain text in `storage/framework/cache`
- Array cache loses tokens on application restart
- File permissions may expose tokens

**Recommendations for Development:**
- Use `file` cache with proper permissions (770)
- Ensure `storage/` is excluded from version control
- Never commit `.env` with real credentials

### Production Environment

**CRITICAL: Never use file or array cache in production!**

```env
# ✅ RECOMMENDED: Use encrypted cache drivers
CACHE_DRIVER=redis

# Or use database with encryption
CACHE_DRIVER=database
```

### Recommended Production Setup

#### 1. Use Redis with TLS

```env
REDIS_CLIENT=predis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379
REDIS_DB=0
REDIS_SCHEME=tls  # Enable TLS encryption
```

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'scheme' => env('REDIS_SCHEME', 'tcp'), // Use 'tls' in production
    ],
],
```

#### 2. Enable Laravel Encryption for Database

```php
// In your Token model or migration
protected $casts = [
    'access_token' => 'encrypted',
    'refresh_token' => 'encrypted',
];
```

#### 3. Secure Application Key

```bash
# Generate secure application key
php artisan key:generate

# In production, never use the default key!
APP_KEY=base64:GENERATED_KEY_HERE
```

#### 4. Restrict Database Access

```env
# Use read-only database user for application
DB_USERNAME=app_readonly
DB_PASSWORD=secure_password

# Separate user with write access for migrations only
```

### Security Checklist

**Before Production Deployment:**

- [ ] Change `CACHE_DRIVER` from `file`/`array` to `redis` or `database`
- [ ] Enable Redis TLS/SSL if using Redis
- [ ] Verify `storage/` directory has correct permissions (770)
- [ ] Ensure `.env` is not in version control
- [ ] Use encrypted casts for token storage in database
- [ ] Rotate application key if it was exposed
- [ ] Enable database encryption at rest
- [ ] Use secure Redis password (20+ characters)
- [ ] Implement network-level security (firewall rules)
- [ ] Enable audit logging for token access

### Token Rotation

The SDK automatically refreshes access tokens. For additional security:

```php
// Force token refresh
$tokenService = app(TokenService::class);
$tokenService->clearTokens(); // Force re-authentication

// Or implement periodic rotation
// In App\Console\Kernel.php
protected function schedule(Schedule $schedule)
{
    // Rotate tokens every 30 days
    $schedule->call(function () {
        // Custom token rotation logic
    })->monthly();
}
```

### Monitoring

Log token access for security auditing:

```php
// config/teamleader.php
'logging' => [
    'log_token_refresh' => true,  // Log when tokens are refreshed
],
```

Monitor for suspicious activity:
- Frequent token refreshes (possible attack)
- Token refresh from unexpected IPs
- Failed authentication attempts

### What to Do If Tokens Are Compromised

1. **Immediately revoke access** in Teamleader:
    - Go to Teamleader Marketplace
    - Disconnect your integration

2. **Clear all tokens**:
```bash
php artisan tinker
>>> app(TokenService::class)->clearTokens();
>>> Cache::flush();
```

3. **Rotate application key**:
```bash
php artisan key:generate
```

4. **Re-authenticate**:
```php
// Force users to re-authenticate
Route::get('/reconnect', function() {
    return Teamleader::authorize();
});
```

5. **Review logs** for unauthorized access

6. **Update passwords** and secrets

## Additional Resources

- [Laravel Encryption](https://laravel.com/docs/encryption)
- [Redis Security](https://redis.io/topics/security)
- [OWASP Security Guidelines](https://owasp.org/)
