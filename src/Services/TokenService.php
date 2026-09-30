<?php

namespace McoreServices\TeamleaderSDK\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use McoreServices\TeamleaderSDK\Events\TokenRefreshed;
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;
use McoreServices\TeamleaderSDK\Traits\DispatchesEvents;
use McoreServices\TeamleaderSDK\Traits\SanitizesLogData;
use Psr\Log\LoggerInterface;

class TokenService
{
    use DispatchesEvents;
    use SanitizesLogData;

    // Cache keys for performance (with database backup)
    private const ACCESS_TOKEN_KEY = 'teamleader_access_token';

    private const REFRESH_TOKEN_KEY = 'teamleader_refresh_token';

    private const REFRESH_LOCK_KEY = 'teamleader_refresh_lock';

    // Database table for persistent storage
    private const TOKENS_TABLE = 'teamleader_tokens';

    // Refresh token if it expires within this many seconds (more aggressive)
    private const REFRESH_THRESHOLD = 900; // 15 minutes

    // Lock timeout to prevent infinite locks
    private const LOCK_TIMEOUT = 60; // 60 seconds for safety

    private Client $httpClient;

    /**
     * The logger for token output: `teamleader.logging.channel` when set, the
     * application's default channel otherwise.
     */
    private function log(): LoggerInterface
    {
        $channel = Config::get('teamleader.logging.channel');

        return is_string($channel) && $channel !== '' ? Log::channel($channel) : Log::driver();
    }

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);
    }

    /**
     * Get a valid access token, refreshing if necessary
     */
    public function getValidAccessToken(): ?string
    {
        // First try to get from cache for performance
        $tokenData = $this->getTokensFromCache();

        if (! $tokenData || ! $tokenData['access_token']) {
            // Fallback to database
            $tokenData = $this->getTokensFromDatabase();

            if ($tokenData && $tokenData['access_token']) {
                // Cache the database tokens for performance
                $this->cacheTokens($tokenData);
            }
        }

        if (! $tokenData || ! $tokenData['access_token']) {
            $this->log()->warning('TokenService: No access token found in cache or database');

            return null;
        }

        // Check if token needs refreshing
        if ($this->shouldRefreshToken($tokenData)) {
            $this->log()->debug('TokenService: Token needs refreshing');
            $newToken = $this->refreshTokenIfNeeded();

            return $newToken;
        }

        return $tokenData['access_token'];
    }

    /**
     * Get tokens from cache
     *
     * Self-healing: if the cached expiry cannot be read (for example because it
     * was written as a serialized object by an older SDK version), the cached
     * tokens are purged and null is returned so the database becomes the source
     * of truth again.
     */
    private function getTokensFromCache(): ?array
    {
        try {
            $accessToken = Cache::get(self::ACCESS_TOKEN_KEY);

            if (! $accessToken || ! is_string($accessToken)) {
                return null;
            }

            $rawExpiresAt = Cache::get(self::ACCESS_TOKEN_KEY.'_expires_at');
            $expiresAt = $this->parseExpiresAt($rawExpiresAt);

            if ($rawExpiresAt !== null && $expiresAt === null) {
                $this->log()->warning('TokenService: Unreadable expires_at in cache, purging cached tokens', [
                    'type' => get_debug_type($rawExpiresAt),
                ]);

                $this->forgetCachedTokens();

                return null;
            }

            $refreshToken = Cache::get(self::REFRESH_TOKEN_KEY);

            return [
                'access_token' => $accessToken,
                'refresh_token' => is_string($refreshToken) ? $refreshToken : null,
                'expires_at' => $expiresAt?->toIso8601String(),
                'source' => 'cache',
            ];
        } catch (Exception $e) {
            $this->log()->warning('TokenService: Failed to get tokens from cache', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Safely turn a stored expiry value into a Carbon instance
     *
     * Accepts strings, unix timestamps and DateTimeInterface instances. Anything
     * else — including the __PHP_Incomplete_Class returned when a serialized
     * object cannot be rehydrated from the cache — returns null, which callers
     * treat as "expired / needs refresh" rather than crashing.
     */
    private function parseExpiresAt(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return Carbon::createFromTimestamp((int) $value);
        }

        if (! is_string($value)) {
            $this->log()->warning('TokenService: Unusable expires_at value, treating token as expired', [
                'type' => get_debug_type($value),
            ]);

            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Exception $e) {
            $this->log()->warning('TokenService: Failed to parse expires_at, treating token as expired', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Forget the cached token entries without touching the database
     */
    private function forgetCachedTokens(): void
    {
        try {
            Cache::forget(self::ACCESS_TOKEN_KEY);
            Cache::forget(self::ACCESS_TOKEN_KEY.'_expires_at');
            Cache::forget(self::REFRESH_TOKEN_KEY);
        } catch (Exception $e) {
            $this->log()->warning('TokenService: Failed to forget cached tokens', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get tokens from database (persistent storage)
     */
    private function getTokensFromDatabase(): ?array
    {
        try {
            $this->ensureTokensTableExists();

            $tokenRecord = DB::table(self::TOKENS_TABLE)
                ->orderBy('updated_at', 'desc')
                ->first();

            if (! $tokenRecord) {
                $this->log()->debug('TokenService: No token record found in database');

                return null;
            }

            return [
                'access_token' => $tokenRecord->access_token,
                'refresh_token' => $tokenRecord->refresh_token,
                'expires_at' => $tokenRecord->expires_at,
                'expires_in' => $tokenRecord->expires_in,
                'token_type' => $tokenRecord->token_type,
                'source' => 'database',
            ];
        } catch (Exception $e) {
            $this->log()->error('TokenService: Failed to get tokens from database', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Ensure the tokens table exists
     */
    private function ensureTokensTableExists(): void
    {
        if (! DB::getSchemaBuilder()->hasTable(self::TOKENS_TABLE)) {
            DB::getSchemaBuilder()->create(self::TOKENS_TABLE, function ($table) {
                $table->id();
                $table->text('access_token');
                $table->text('refresh_token')->nullable();
                $table->string('token_type', 50)->default('Bearer');
                $table->integer('expires_in');
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index('expires_at');
                $table->index('updated_at');
            });

            $this->log()->info('TokenService: Created teamleader_tokens table');
        }
    }

    /**
     * Cache tokens for performance
     *
     * Only scalar values are written to the cache. Never store a Carbon (or any
     * other object) here: cache stores serialize their payload, and a payload
     * that cannot be rehydrated comes back as __PHP_Incomplete_Class.
     */
    private function cacheTokens(array $tokenData): void
    {
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);
        $cacheTtl = max(60, $expiresIn - 120); // Cache for slightly less time for safety

        $expiresAt = $this->parseExpiresAt($tokenData['expires_at'] ?? null);

        try {
            Cache::put(self::ACCESS_TOKEN_KEY, $tokenData['access_token'], $cacheTtl);
            Cache::put(
                self::ACCESS_TOKEN_KEY.'_expires_at',
                $expiresAt?->toIso8601String(),
                $cacheTtl
            );

            if (! empty($tokenData['refresh_token'])) {
                // Cache refresh token for longer but still less than database
                Cache::put(self::REFRESH_TOKEN_KEY, $tokenData['refresh_token'], 60 * 60 * 24 * 7); // 7 days
            }

            $this->log()->debug('TokenService: Tokens cached', [
                'cache_ttl_minutes' => round($cacheTtl / 60, 1),
            ]);
        } catch (Exception $e) {
            $this->log()->warning('TokenService: Failed to cache tokens', [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - database storage is more important
        }
    }

    /**
     * Check if the current access token should be refreshed
     */
    private function shouldRefreshToken(array $tokenData): bool
    {
        $expiresAt = $this->parseExpiresAt($tokenData['expires_at'] ?? null);

        if (! $expiresAt) {
            $this->log()->debug('TokenService: No usable expiration info, assuming refresh needed');

            return true;
        }

        $now = Carbon::now();

        // Carbon is mutable: copy() before subtracting, otherwise $expiresAt is
        // shifted by the threshold and every log line below reports a wrong time.
        $shouldRefresh = $expiresAt->copy()->subSeconds(self::REFRESH_THRESHOLD)->isPast();

        if ($shouldRefresh) {
            $this->log()->debug('TokenService: Token refresh needed', [
                'expires_at' => $expiresAt->toDateTimeString(),
                'minutes_left' => round($now->diffInMinutes($expiresAt, false), 1),
                'threshold_minutes' => self::REFRESH_THRESHOLD / 60,
            ]);
        }

        return $shouldRefresh;
    }

    /**
     * Refresh the access token using the refresh token with proper locking
     */
    public function refreshTokenIfNeeded(): ?string
    {
        // Try to acquire a lock to prevent concurrent refresh attempts
        $lockAcquired = Cache::add(self::REFRESH_LOCK_KEY, true, self::LOCK_TIMEOUT);

        if (! $lockAcquired) {
            $this->log()->debug('TokenService: Another refresh is in progress, waiting...');

            // Wait for the other refresh to complete
            $attempts = 0;
            while (Cache::has(self::REFRESH_LOCK_KEY) && $attempts < 60) {
                usleep(500000); // 500ms
                $attempts++;
            }

            // Return the potentially refreshed token from database
            $tokenData = $this->getTokensFromDatabase();

            return $tokenData['access_token'] ?? null;
        }

        try {
            return $this->performTokenRefresh();
        } catch (Exception $e) {
            $this->log()->error('TokenService: Exception during token refresh', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        } finally {
            // Always release the lock
            Cache::forget(self::REFRESH_LOCK_KEY);
        }
    }

    /**
     * Perform the actual token refresh
     */
    private function performTokenRefresh(): ?string
    {
        // Get refresh token from database (most reliable source)
        $tokenData = $this->getTokensFromDatabase();
        $refreshToken = $tokenData['refresh_token'] ?? null;

        if (! $refreshToken) {
            $this->log()->error('TokenService: No refresh token available in database');
            $this->clearAllTokens(); // Clean up any stale cache

            $this->fireEvent(TokenRefreshFailed::class, fn () => new TokenRefreshFailed(
                'No refresh token is stored. Connect the account through OAuth.', null, true
            ));

            return null;
        }

        try {
            // No part of the refresh token is logged: until v3.0 its first 20
            // characters were, at info level.
            $this->log()->info('TokenService: Attempting to refresh access token');

            $authUrl = rtrim((string) (Config::get('teamleader.auth_url') ?: 'https://focus.teamleader.eu'), '/');

            $response = $this->httpClient->post($authUrl.'/oauth2/access_token', [
                'form_params' => [
                    'client_id' => Config::get('teamleader.client_id'),
                    'client_secret' => Config::get('teamleader.client_secret'),
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Token refresh failed with status: '.$response->getStatusCode());
            }

            $result = json_decode($response->getBody()->getContents(), true);

            if (! isset($result['access_token'])) {
                // Key names only — the body may hold a refresh token.
                throw new Exception('No access token in refresh response. Keys received: '
                    .(is_array($result) ? implode(', ', array_keys($result)) : gettype($result)));
            }

            // CRITICAL: Store the new tokens (including new refresh token)
            $this->storeTokens($result);

            $this->log()->info('TokenService: Access token refreshed successfully', [
                'expires_in' => $result['expires_in'] ?? 'unknown',
                'token_type' => $result['token_type'] ?? 'unknown',
                'has_new_refresh_token' => isset($result['refresh_token']),
            ]);

            $this->fireEvent(TokenRefreshed::class, fn () => new TokenRefreshed(
                isset($result['expires_in']) ? (int) $result['expires_in'] : null
            ));

            return $result['access_token'];

        } catch (GuzzleException $e) {
            $statusCode = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = (string) $e->getResponse()->getBody();

                $this->log()->error('TokenService: HTTP error during token refresh', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'error' => $e->getMessage(),
                ]);
            } else {
                $this->log()->error('TokenService: Network error during token refresh', [
                    'error' => $e->getMessage(),
                ]);
            }

            // If refresh token is invalid (400/401), clear all tokens
            $refused = in_array($statusCode, [400, 401], true);

            if ($refused) {
                $this->log()->critical('TokenService: Refresh token is invalid, clearing all tokens');
                $this->clearAllTokens();
            }

            $this->fireEvent(TokenRefreshFailed::class, fn () => new TokenRefreshFailed(
                $refused
                    ? 'Teamleader refused the refresh token. Connect the account through OAuth again.'
                    : 'The token endpoint could not be reached: '.$e->getMessage(),
                $statusCode,
                $refused
            ));

            return null;
        } catch (Exception $e) {
            $this->log()->error('TokenService: Error refreshing token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fireEvent(TokenRefreshFailed::class, fn () => new TokenRefreshFailed($e->getMessage()));

            return null;
        }
    }

    /**
     * Clear all tokens from all storage locations
     */
    private function clearAllTokens(): void
    {
        // Clear cache
        try {
            Cache::forget(self::ACCESS_TOKEN_KEY);
            Cache::forget(self::ACCESS_TOKEN_KEY.'_expires_at');
            Cache::forget(self::REFRESH_TOKEN_KEY);
            $this->log()->debug('TokenService: Cleared tokens from cache');
        } catch (Exception $e) {
            $this->log()->warning('TokenService: Failed to clear cache tokens', [
                'error' => $e->getMessage(),
            ]);
        }

        // Clear database
        try {
            if (DB::getSchemaBuilder()->hasTable(self::TOKENS_TABLE)) {
                DB::table(self::TOKENS_TABLE)->delete();
                $this->log()->debug('TokenService: Cleared tokens from database');
            }
        } catch (Exception $e) {
            $this->log()->warning('TokenService: Failed to clear database tokens', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->log()->info('TokenService: All tokens cleared from all storage locations');
    }

    /**
     * Store tokens in both database (persistent) and cache (performance)
     */
    public function storeTokens(array $tokenData): void
    {
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);
        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        // Get existing refresh token if new one not provided
        $refreshToken = $tokenData['refresh_token'] ?? null;

        // CRITICAL FIX: If no refresh token in new data, preserve the existing one
        if (empty($refreshToken)) {
            $existingData = $this->getTokensFromDatabase();
            $refreshToken = $existingData['refresh_token'] ?? null;

            $this->log()->info('TokenService: No refresh token in new data, preserving existing', [
                'has_existing_refresh_token' => ! empty($refreshToken),
            ]);
        }

        $tokenRecord = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $refreshToken, // Use preserved or new refresh token
            'token_type' => $tokenData['token_type'] ?? 'Bearer',
            'expires_in' => $expiresIn,
            // CRITICAL: keep this a scalar. This same array is handed to
            // cacheTokens(), and an object here ends up serialized in the cache
            // store, where it can come back as __PHP_Incomplete_Class.
            'expires_at' => $expiresAt->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ];

        // CRITICAL: Store in database first (persistent storage)
        try {
            $this->ensureTokensTableExists();

            $existing = DB::table(self::TOKENS_TABLE)->first();

            if ($existing) {
                DB::table(self::TOKENS_TABLE)->update($tokenRecord);
                $this->log()->debug('TokenService: Updated existing token record in database');
            } else {
                $tokenRecord['created_at'] = Carbon::now()->toDateTimeString();
                DB::table(self::TOKENS_TABLE)->insert($tokenRecord);
                $this->log()->debug('TokenService: Created new token record in database');
            }
        } catch (Exception $e) {
            $this->log()->error('TokenService: Failed to store tokens in database', [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - we can still use cache temporarily
        }

        // Then cache for performance (with shorter TTL for safety)
        $this->cacheTokens($tokenRecord);

        $this->log()->info('TokenService: Tokens stored successfully', [
            'expires_in_minutes' => round($expiresIn / 60, 1),
            'expires_at' => $tokenRecord['expires_at'],
            'has_refresh_token' => ! empty($refreshToken),
            'refresh_token_source' => isset($tokenData['refresh_token']) ? 'new' : 'preserved',
        ]);
    }

    /**
     * Clear all stored tokens (cache and database)
     */
    public function clearTokens(): void
    {
        $this->clearAllTokens();
    }

    /**
     * Check if we have valid tokens
     */
    public function hasValidTokens(): bool
    {
        $tokenData = $this->getTokensFromCache() ?? $this->getTokensFromDatabase();

        if (! $tokenData || empty($tokenData['access_token']) || empty($tokenData['refresh_token'])) {
            return false;
        }

        if (isset($tokenData['expires_at'])) {
            $expiresAt = $this->parseExpiresAt($tokenData['expires_at']);

            if (! $expiresAt) {
                $this->log()->debug('TokenService: Tokens exist but expiry is unreadable, treating as invalid');

                return false;
            }

            // copy() so the 5 minute buffer does not mutate $expiresAt
            if ($expiresAt->copy()->subMinutes(5)->isPast()) {
                $this->log()->debug('TokenService: Tokens exist but are expired');

                return false;
            }
        }

        return true;
    }

    /**
     * Get comprehensive token information for debugging
     */
    public function getTokenInfo(): array
    {
        $cacheData = $this->getTokensFromCache();
        $dbData = $this->getTokensFromDatabase();

        $activeData = $cacheData ?? $dbData;

        $expiresAt = $activeData
            ? $this->parseExpiresAt($activeData['expires_at'] ?? null)
            : null;

        $expiresIn = $expiresAt
            ? (int) max(0, round(Carbon::now()->diffInSeconds($expiresAt, false)))
            : null;

        return [
            'has_access_token' => ! empty($activeData['access_token']),
            'has_refresh_token' => ! empty($activeData['refresh_token']),
            'expires_at' => $expiresAt ? $expiresAt->toDateTimeString() : null,
            'expires_in' => $expiresIn,
            'needs_refresh' => $activeData ? $this->shouldRefreshToken($activeData) : true,
            'token_source' => $activeData['source'] ?? 'none',
            'cache_has_tokens' => ! empty($cacheData),
            'database_has_tokens' => ! empty($dbData),
            'storage_sync' => [
                'cache_access_token' => ! empty($cacheData['access_token']),
                'db_access_token' => ! empty($dbData['access_token']),
                'cache_refresh_token' => ! empty($cacheData['refresh_token']),
                'db_refresh_token' => ! empty($dbData['refresh_token']),
            ],
        ];
    }

    /**
     * Manually sync tokens from database to cache
     */
    public function syncTokensToCache(): bool
    {
        try {
            $dbData = $this->getTokensFromDatabase();

            if ($dbData && $dbData['access_token']) {
                $this->cacheTokens($dbData);
                $this->log()->info('TokenService: Synced tokens from database to cache');

                return true;
            }

            $this->log()->warning('TokenService: No valid tokens in database to sync');

            return false;
        } catch (Exception $e) {
            $this->log()->error('TokenService: Failed to sync tokens to cache', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
