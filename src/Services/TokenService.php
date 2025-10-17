<?php

namespace McoreServices\TeamleaderSDK\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use McoreServices\TeamleaderSDK\Traits\SanitizesLogData;

class TokenService
{
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

        if (!$tokenData || !$tokenData['access_token']) {
            // Fallback to database
            $tokenData = $this->getTokensFromDatabase();

            if ($tokenData && $tokenData['access_token']) {
                // Cache the database tokens for performance
                $this->cacheTokens($tokenData);
            }
        }

        if (!$tokenData || !$tokenData['access_token']) {
            Log::warning('TokenService: No access token found in cache or database');
            return null;
        }

        // Check if token needs refreshing
        if ($this->shouldRefreshToken($tokenData)) {
            Log::debug('TokenService: Token needs refreshing');
            $newToken = $this->refreshTokenIfNeeded();
            return $newToken;
        }

        return $tokenData['access_token'];
    }

    /**
     * Check if the current access token should be refreshed
     */
    private function shouldRefreshToken(array $tokenData): bool
    {
        if (!isset($tokenData['expires_at'])) {
            Log::debug('TokenService: No expiration info, assuming refresh needed');
            return true;
        }

        $expiresAt = Carbon::parse($tokenData['expires_at']);
        $now = Carbon::now();

        // Refresh if token expires within the threshold
        $shouldRefresh = $expiresAt->subSeconds(self::REFRESH_THRESHOLD)->isPast();

        if ($shouldRefresh) {
            $minutesLeft = $now->diffInMinutes($expiresAt, false);
            Log::debug('TokenService: Token refresh needed', [
                'expires_at' => $expiresAt->toDateTimeString(),
                'minutes_left' => $minutesLeft,
                'threshold_minutes' => self::REFRESH_THRESHOLD / 60
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

        if (!$lockAcquired) {
            Log::debug('TokenService: Another refresh is in progress, waiting...');

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
            Log::error('TokenService: Exception during token refresh', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

        if (!$refreshToken) {
            Log::error('TokenService: No refresh token available in database');
            $this->clearAllTokens(); // Clean up any stale cache
            return null;
        }

        try {
            Log::info('TokenService: Attempting to refresh access token', [
                'refresh_token_preview' => substr($refreshToken, 0, 20) . '...'
            ]);

            $response = $this->httpClient->post('https://focus.teamleader.eu/oauth2/access_token', [
                'form_params' => [
                    'client_id' => Config::get('teamleader.client_id'),
                    'client_secret' => Config::get('teamleader.client_secret'),
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Token refresh failed with status: ' . $response->getStatusCode());
            }

            $result = json_decode($response->getBody()->getContents(), true);

            if (!isset($result['access_token'])) {
                throw new Exception('No access token in refresh response: ' . json_encode($result));
            }

            // CRITICAL: Store the new tokens (including new refresh token)
            $this->storeTokens($result);

            Log::info('TokenService: Access token refreshed successfully', [
                'expires_in' => $result['expires_in'] ?? 'unknown',
                'token_type' => $result['token_type'] ?? 'unknown',
                'has_new_refresh_token' => isset($result['refresh_token'])
            ]);

            return $result['access_token'];

        } catch (GuzzleException $e) {
            $statusCode = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = (string) $e->getResponse()->getBody();

                Log::error('TokenService: HTTP error during token refresh', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'error' => $e->getMessage()
                ]);
            } else {
                Log::error('TokenService: Network error during token refresh', [
                    'error' => $e->getMessage()
                ]);
            }

            // If refresh token is invalid (400/401), clear all tokens
            if (in_array($statusCode, [400, 401])) {
                Log::critical('TokenService: Refresh token is invalid, clearing all tokens');
                $this->clearAllTokens();
            }

            return null;
        } catch (Exception $e) {
            Log::error('TokenService: Error refreshing token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Store tokens in both database (persistent) and cache (performance)
     */
    public function storeTokens(array $tokenData): void
    {
        $expiresIn = $tokenData['expires_in'] ?? 3600;
        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        // Get existing refresh token if new one not provided
        $refreshToken = $tokenData['refresh_token'] ?? null;

        // CRITICAL FIX: If no refresh token in new data, preserve the existing one
        if (empty($refreshToken)) {
            $existingData = $this->getTokensFromDatabase();
            $refreshToken = $existingData['refresh_token'] ?? null;

            Log::info('TokenService: No refresh token in new data, preserving existing', [
                'has_existing_refresh_token' => !empty($refreshToken)
            ]);
        }

        $tokenRecord = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $refreshToken, // Use preserved or new refresh token
            'token_type' => $tokenData['token_type'] ?? 'Bearer',
            'expires_in' => $expiresIn,
            'expires_at' => $expiresAt,
            'updated_at' => Carbon::now(),
        ];

        // CRITICAL: Store in database first (persistent storage)
        try {
            $this->ensureTokensTableExists();

            $existing = DB::table(self::TOKENS_TABLE)->first();

            if ($existing) {
                DB::table(self::TOKENS_TABLE)->update($tokenRecord);
                Log::debug('TokenService: Updated existing token record in database');
            } else {
                $tokenRecord['created_at'] = Carbon::now();
                DB::table(self::TOKENS_TABLE)->insert($tokenRecord);
                Log::debug('TokenService: Created new token record in database');
            }
        } catch (Exception $e) {
            Log::error('TokenService: Failed to store tokens in database', [
                'error' => $e->getMessage()
            ]);
            // Don't throw - we can still use cache temporarily
        }

        // Then cache for performance (with shorter TTL for safety)
        $this->cacheTokens($tokenRecord);

        Log::info('TokenService: Tokens stored successfully', [
            'expires_in_minutes' => round($expiresIn / 60, 1),
            'expires_at' => $expiresAt->toDateTimeString(),
            'has_refresh_token' => !empty($refreshToken),
            'refresh_token_source' => isset($tokenData['refresh_token']) ? 'new' : 'preserved'
        ]);
    }

    /**
     * Cache tokens for performance
     */
    private function cacheTokens(array $tokenData): void
    {
        $expiresIn = $tokenData['expires_in'] ?? 3600;
        $cacheTtl = max(60, $expiresIn - 120); // Cache for slightly less time for safety

        try {
            Cache::put(self::ACCESS_TOKEN_KEY, $tokenData['access_token'], $cacheTtl);
            Cache::put(self::ACCESS_TOKEN_KEY . '_expires_at', $tokenData['expires_at'], $cacheTtl);

            if (!empty($tokenData['refresh_token'])) {
                // Cache refresh token for longer but still less than database
                Cache::put(self::REFRESH_TOKEN_KEY, $tokenData['refresh_token'], 60 * 60 * 24 * 7); // 7 days
            }

            Log::debug('TokenService: Tokens cached', [
                'cache_ttl_minutes' => round($cacheTtl / 60, 1)
            ]);
        } catch (Exception $e) {
            Log::warning('TokenService: Failed to cache tokens', [
                'error' => $e->getMessage()
            ]);
            // Don't throw - database storage is more important
        }
    }

    /**
     * Get tokens from cache
     */
    private function getTokensFromCache(): ?array
    {
        try {
            $accessToken = Cache::get(self::ACCESS_TOKEN_KEY);
            $expiresAt = Cache::get(self::ACCESS_TOKEN_KEY . '_expires_at');
            $refreshToken = Cache::get(self::REFRESH_TOKEN_KEY);

            if (!$accessToken) {
                return null;
            }

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt,
                'source' => 'cache'
            ];
        } catch (Exception $e) {
            Log::warning('TokenService: Failed to get tokens from cache', [
                'error' => $e->getMessage()
            ]);
            return null;
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

            if (!$tokenRecord) {
                Log::debug('TokenService: No token record found in database');
                return null;
            }

            return [
                'access_token' => $tokenRecord->access_token,
                'refresh_token' => $tokenRecord->refresh_token,
                'expires_at' => $tokenRecord->expires_at,
                'expires_in' => $tokenRecord->expires_in,
                'token_type' => $tokenRecord->token_type,
                'source' => 'database'
            ];
        } catch (Exception $e) {
            Log::error('TokenService: Failed to get tokens from database', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Ensure the tokens table exists
     */
    private function ensureTokensTableExists(): void
    {
        if (!DB::getSchemaBuilder()->hasTable(self::TOKENS_TABLE)) {
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

            Log::info('TokenService: Created teamleader_tokens table');
        }
    }

    /**
     * Clear all stored tokens (cache and database)
     */
    public function clearTokens(): void
    {
        $this->clearAllTokens();
    }

    /**
     * Clear all tokens from all storage locations
     */
    private function clearAllTokens(): void
    {
        // Clear cache
        try {
            Cache::forget(self::ACCESS_TOKEN_KEY);
            Cache::forget(self::ACCESS_TOKEN_KEY . '_expires_at');
            Cache::forget(self::REFRESH_TOKEN_KEY);
            Log::debug('TokenService: Cleared tokens from cache');
        } catch (Exception $e) {
            Log::warning('TokenService: Failed to clear cache tokens', [
                'error' => $e->getMessage()
            ]);
        }

        // Clear database
        try {
            if (DB::getSchemaBuilder()->hasTable(self::TOKENS_TABLE)) {
                DB::table(self::TOKENS_TABLE)->delete();
                Log::debug('TokenService: Cleared tokens from database');
            }
        } catch (Exception $e) {
            Log::warning('TokenService: Failed to clear database tokens', [
                'error' => $e->getMessage()
            ]);
        }

        Log::info('TokenService: All tokens cleared from all storage locations');
    }

    /**
     * Check if we have valid tokens
     */
    public function hasValidTokens(): bool
    {
        $tokenData = $this->getTokensFromCache() ?? $this->getTokensFromDatabase();

        if (!$tokenData || !$tokenData['access_token'] || !$tokenData['refresh_token']) {
            return false;
        }

        // Check if not expired (with some buffer)
        if (isset($tokenData['expires_at'])) {
            $expiresAt = Carbon::parse($tokenData['expires_at']);
            $isExpired = $expiresAt->subMinutes(5)->isPast(); // 5 minute buffer

            if ($isExpired) {
                Log::debug('TokenService: Tokens exist but are expired');
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

        $expiresAt = null;
        $expiresIn = null;

        if ($activeData && isset($activeData['expires_at'])) {
            $expiresAt = Carbon::parse($activeData['expires_at']);
            $expiresIn = max(0, Carbon::now()->diffInSeconds($expiresAt, false));
        }

        return [
            'has_access_token' => !empty($activeData['access_token']),
            'has_refresh_token' => !empty($activeData['refresh_token']),
            'expires_at' => $expiresAt ? $expiresAt->toDateTimeString() : null,
            'expires_in' => $expiresIn,
            'needs_refresh' => $activeData ? $this->shouldRefreshToken($activeData) : true,
            'token_source' => $activeData['source'] ?? 'none',
            'cache_has_tokens' => !empty($cacheData),
            'database_has_tokens' => !empty($dbData),
            'storage_sync' => [
                'cache_access_token' => !empty($cacheData['access_token']),
                'db_access_token' => !empty($dbData['access_token']),
                'cache_refresh_token' => !empty($cacheData['refresh_token']),
                'db_refresh_token' => !empty($dbData['refresh_token']),
            ]
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
                Log::info('TokenService: Synced tokens from database to cache');
                return true;
            }

            Log::warning('TokenService: No valid tokens in database to sync');
            return false;
        } catch (Exception $e) {
            Log::error('TokenService: Failed to sync tokens to cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
