<?php

namespace McoreServices\TeamleaderSDK\Services;

use Carbon\CarbonImmutable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use McoreServices\TeamleaderSDK\Connections\ConnectionConfig;
use McoreServices\TeamleaderSDK\Events\TokenRefreshed;
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;
use McoreServices\TeamleaderSDK\Tokens\DatabaseTokenStore;
use McoreServices\TeamleaderSDK\Tokens\StoredTokens;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;
use McoreServices\TeamleaderSDK\Traits\DispatchesEvents;
use McoreServices\TeamleaderSDK\Traits\SanitizesLogData;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Keeps one connection's access token valid.
 *
 * Reads go to the cache first and to the TokenStore on a miss. The cache holds
 * a single entry per connection — `teamleader:{connection}:tokens` — encrypted
 * with the application key, so no token is readable in the cache store either.
 *
 * An access token with less than 15 minutes left is refreshed before it is
 * handed out, under a per-connection lock so concurrent workers refresh once.
 */
class TokenService
{
    use DispatchesEvents;
    use SanitizesLogData;

    // Refresh token if it expires within this many seconds
    private const REFRESH_THRESHOLD = 900; // 15 minutes

    // Lock timeout to prevent infinite locks
    private const LOCK_TIMEOUT = 60;

    /**
     * Cache keys used before v3.0. They held the tokens in plain text; they are
     * removed once per process so a copy does not linger for up to seven days.
     */
    private const LEGACY_CACHE_KEYS = [
        'teamleader_access_token',
        'teamleader_access_token_expires_at',
        'teamleader_refresh_token',
        'teamleader_refresh_lock',
    ];

    private static bool $legacyCacheCleared = false;

    private Client $httpClient;

    private TokenStore $store;

    /**
     * @param  ConnectionConfig|null  $credentials  The connection's client ID and secret,
     *                                              used for the refresh. Null reads the
     *                                              flat teamleader.client_id / client_secret
     */
    public function __construct(
        ?TokenStore $store = null,
        private readonly string $connection = 'default',
        private readonly ?ConnectionConfig $credentials = null,
    ) {
        $this->store = $store ?? (app()->bound(TokenStore::class) ? app(TokenStore::class) : new DatabaseTokenStore);

        $this->httpClient = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);

        $this->clearLegacyCacheOnce();
    }

    /** The connection these tokens belong to */
    public function connection(): string
    {
        return $this->connection;
    }

    public function store(): TokenStore
    {
        return $this->store;
    }

    /**
     * Get a valid access token, refreshing if necessary
     */
    public function getValidAccessToken(): ?string
    {
        $tokens = $this->current();

        if ($tokens === null || $tokens->accessToken === '') {
            $this->log()->warning('TokenService: No access token stored', ['connection' => $this->connection]);

            return null;
        }

        if ($this->shouldRefresh($tokens)) {
            $this->log()->debug('TokenService: Token needs refreshing', ['connection' => $this->connection]);

            return $this->refreshTokenIfNeeded();
        }

        return $tokens->accessToken;
    }

    /**
     * Store a token response from Teamleader — the OAuth callback or a refresh.
     *
     * A response without a refresh token keeps the stored one. The account id
     * and name already stored are kept too.
     *
     * @param  array<string, mixed>  $tokenData  access_token, refresh_token, expires_in, token_type
     * @param  bool  $refreshed  True when this is the result of a refresh
     */
    public function storeTokens(array $tokenData, bool $refreshed = false): void
    {
        $existing = $this->readStore();
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);
        $refreshToken = $tokenData['refresh_token'] ?? null;

        if (empty($refreshToken)) {
            $refreshToken = $existing?->refreshToken;

            $this->log()->info('TokenService: No refresh token in new data, preserving existing', [
                'connection' => $this->connection,
                'has_existing_refresh_token' => ! empty($refreshToken),
            ]);
        }

        $tokens = new StoredTokens(
            accessToken: (string) $tokenData['access_token'],
            refreshToken: $refreshToken,
            expiresAt: CarbonImmutable::now()->addSeconds($expiresIn),
            expiresIn: $expiresIn,
            tokenType: (string) ($tokenData['token_type'] ?? 'Bearer'),
            status: StoredTokens::CONNECTED,
            accountId: $existing?->accountId,
            accountName: $existing?->accountName,
            lastRefreshedAt: $refreshed ? CarbonImmutable::now() : $existing?->lastRefreshedAt,
        );

        try {
            $this->store->put($this->connection, $tokens);
        } catch (Throwable $e) {
            $this->log()->error('TokenService: Failed to store tokens', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);
            // Keep going: the cached copy serves until the store is fixed
        }

        $this->writeCache($tokens);

        $this->log()->info('TokenService: Tokens stored successfully', [
            'connection' => $this->connection,
            'expires_in_minutes' => round($expiresIn / 60, 1),
            'has_refresh_token' => ! empty($refreshToken),
            'refresh_token_source' => isset($tokenData['refresh_token']) ? 'new' : 'preserved',
        ]);
    }

    /**
     * Clear all stored tokens (cache and store)
     */
    public function clearTokens(): void
    {
        $this->forgetCache();

        try {
            $this->store->forget($this->connection);
        } catch (Throwable $e) {
            $this->log()->warning('TokenService: Failed to clear stored tokens', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);
        }

        $this->log()->info('TokenService: Tokens cleared', ['connection' => $this->connection]);
    }

    /**
     * Check if we have valid tokens
     */
    public function hasValidTokens(): bool
    {
        $tokens = $this->current();

        if ($tokens === null || $tokens->accessToken === '' || empty($tokens->refreshToken)) {
            return false;
        }

        if ($tokens->needsReauthorization()) {
            return false;
        }

        // Unknown expiry: not invalid, but shouldRefresh() will refresh it
        if ($tokens->expiresAt === null) {
            return true;
        }

        return ! $tokens->expiresAt->subMinutes(5)->isPast();
    }

    /**
     * Get comprehensive token information for debugging
     *
     * @return array<string, mixed>
     */
    public function getTokenInfo(): array
    {
        $cached = $this->readCache();
        $stored = $this->readStore();
        $active = $cached ?? $stored;

        return [
            'connection' => $this->connection,
            'has_access_token' => $active !== null && $active->accessToken !== '',
            'has_refresh_token' => ! empty($active?->refreshToken),
            'expires_at' => $active?->expiresAt?->toDateTimeString(),
            'expires_in' => $active?->secondsUntilExpiry() === null ? null : max(0, $active->secondsUntilExpiry()),
            'needs_refresh' => $active === null || $this->shouldRefresh($active),
            'status' => $active?->status,
            'account_id' => $active?->accountId,
            'account_name' => $active?->accountName,
            'last_refreshed_at' => $active?->lastRefreshedAt?->toDateTimeString(),
            'token_source' => $cached !== null ? 'cache' : ($stored !== null ? 'database' : 'none'),
            'cache_has_tokens' => $cached !== null,
            'database_has_tokens' => $stored !== null,
            'storage_sync' => [
                'cache_access_token' => $cached !== null && $cached->accessToken !== '',
                'db_access_token' => $stored !== null && $stored->accessToken !== '',
                'cache_refresh_token' => ! empty($cached?->refreshToken),
                'db_refresh_token' => ! empty($stored?->refreshToken),
            ],
        ];
    }

    /**
     * Re-cache the stored tokens, after a cache flush
     */
    public function syncTokensToCache(): bool
    {
        $stored = $this->readStore();

        if ($stored === null || $stored->accessToken === '') {
            $this->log()->warning('TokenService: No stored tokens to sync', ['connection' => $this->connection]);

            return false;
        }

        $this->writeCache($stored);
        $this->log()->info('TokenService: Synced stored tokens to cache', ['connection' => $this->connection]);

        return true;
    }

    /**
     * Refresh the access token using the refresh token with proper locking
     */
    public function refreshTokenIfNeeded(): ?string
    {
        $lock = $this->cacheKey('refresh_lock');

        if (! Cache::add($lock, true, self::LOCK_TIMEOUT)) {
            $this->log()->debug('TokenService: Another refresh is in progress, waiting...', ['connection' => $this->connection]);

            $attempts = 0;
            while (Cache::has($lock) && $attempts < 60) {
                usleep(500000); // 500ms
                $attempts++;
            }

            // The other process stored the new pair; read it from the store
            $this->forgetCache();

            return $this->current()?->accessToken;
        }

        try {
            return $this->performTokenRefresh();
        } catch (Exception $e) {
            $this->log()->error('TokenService: Exception during token refresh', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            Cache::forget($lock);
        }
    }

    /**
     * Perform the actual token refresh
     */
    private function performTokenRefresh(): ?string
    {
        // The store, not the cache: another process may have refreshed since
        $refreshToken = $this->readStore()?->refreshToken;

        if (! $refreshToken) {
            $this->log()->error('TokenService: No refresh token stored', ['connection' => $this->connection]);
            $this->forgetCache();

            $this->fireEvent(TokenRefreshFailed::class, fn () => new TokenRefreshFailed(
                'No refresh token is stored. Connect the account through OAuth.', null, true,
                connection: $this->connection,
            ));

            return null;
        }

        try {
            // No part of the refresh token is logged
            $this->log()->info('TokenService: Attempting to refresh access token', ['connection' => $this->connection]);

            $authUrl = rtrim((string) (Config::get('teamleader.auth_url') ?: 'https://focus.teamleader.eu'), '/');

            $response = $this->httpClient->post($authUrl.'/oauth2/access_token', [
                'form_params' => [
                    'client_id' => $this->credentials?->clientId ?? Config::get('teamleader.client_id'),
                    'client_secret' => $this->credentials?->clientSecret ?? Config::get('teamleader.client_secret'),
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

            // Store the new pair, including the new refresh token, before the
            // lock is released
            $this->storeTokens($result, true);

            $this->log()->info('TokenService: Access token refreshed successfully', [
                'connection' => $this->connection,
                'expires_in' => $result['expires_in'] ?? 'unknown',
                'has_new_refresh_token' => isset($result['refresh_token']),
            ]);

            $this->fireEvent(TokenRefreshed::class, fn () => new TokenRefreshed(
                isset($result['expires_in']) ? (int) $result['expires_in'] : null,
                connection: $this->connection,
            ));

            return $result['access_token'];

        } catch (GuzzleException $e) {
            $statusCode = null;

            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();

                $this->log()->error('TokenService: HTTP error during token refresh', [
                    'connection' => $this->connection,
                    'status_code' => $statusCode,
                    'response_body' => (string) $e->getResponse()->getBody(),
                ]);
            } else {
                $this->log()->error('TokenService: Network error during token refresh', [
                    'connection' => $this->connection,
                    'error' => $e->getMessage(),
                ]);
            }

            // A refused refresh token (400/401) cannot recover
            $refused = in_array($statusCode, [400, 401], true);

            if ($refused) {
                $this->log()->critical('TokenService: Refresh token is invalid, clearing tokens', ['connection' => $this->connection]);
                $this->clearTokens();
            }

            $this->fireEvent(TokenRefreshFailed::class, fn () => new TokenRefreshFailed(
                $refused
                    ? 'Teamleader refused the refresh token. Connect the account through OAuth again.'
                    : 'The token endpoint could not be reached: '.$e->getMessage(),
                $statusCode,
                $refused,
                connection: $this->connection,
            ));

            return null;
        } catch (Exception $e) {
            $this->log()->error('TokenService: Error refreshing token', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);

            $this->fireEvent(TokenRefreshFailed::class, fn () => new TokenRefreshFailed($e->getMessage(), connection: $this->connection));

            return null;
        }
    }

    // -- storage ----------------------------------------------------------------

    /** Cache first, then the store — caching what the store returned */
    private function current(): ?StoredTokens
    {
        $cached = $this->readCache();

        if ($cached !== null) {
            return $cached;
        }

        $stored = $this->readStore();

        if ($stored !== null && $stored->accessToken !== '') {
            $this->writeCache($stored);
        }

        return $stored;
    }

    private function readStore(): ?StoredTokens
    {
        try {
            return $this->store->get($this->connection);
        } catch (Throwable $e) {
            $this->log()->error('TokenService: Failed to read stored tokens', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * The cached tokens, or null. An entry that is not a readable encrypted
     * payload — a leftover from another version, another APP_KEY — is removed,
     * so the store becomes the source again.
     */
    private function readCache(): ?StoredTokens
    {
        try {
            $payload = Cache::get($this->cacheKey('tokens'));

            if ($payload === null) {
                return null;
            }

            if (! is_string($payload)) {
                throw new Exception('Cached tokens are a '.get_debug_type($payload).', not an encrypted string');
            }

            $data = json_decode(Crypt::decryptString($payload), true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? StoredTokens::fromArray($data) : null;
        } catch (Throwable $e) {
            $this->log()->warning('TokenService: Unreadable cached tokens, purging', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);

            $this->forgetCache();

            return null;
        }
    }

    /**
     * Cache the tokens, encrypted, until shortly before the access token expires
     */
    private function writeCache(StoredTokens $tokens): void
    {
        $secondsLeft = $tokens->secondsUntilExpiry() ?? $tokens->expiresIn;
        $ttl = max(60, $secondsLeft - 120);

        try {
            Cache::put($this->cacheKey('tokens'), Crypt::encryptString(json_encode($tokens->toArray())), $ttl);
        } catch (Throwable $e) {
            $this->log()->warning('TokenService: Failed to cache tokens', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function forgetCache(): void
    {
        try {
            Cache::forget($this->cacheKey('tokens'));
        } catch (Throwable $e) {
            $this->log()->warning('TokenService: Failed to forget cached tokens', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldRefresh(StoredTokens $tokens): bool
    {
        if ($tokens->expiresAt === null) {
            return true;
        }

        return $tokens->expiresAt->subSeconds(self::REFRESH_THRESHOLD)->isPast();
    }

    /** `teamleader:{connection}:{suffix}` */
    private function cacheKey(string $suffix): string
    {
        return "teamleader:{$this->connection}:{$suffix}";
    }

    private function clearLegacyCacheOnce(): void
    {
        if (self::$legacyCacheCleared) {
            return;
        }

        self::$legacyCacheCleared = true;

        try {
            foreach (self::LEGACY_CACHE_KEYS as $key) {
                Cache::forget($key);
            }
        } catch (Throwable) {
            // No cache store configured: nothing to clear
        }
    }

    /**
     * The logger for token output: `teamleader.logging.channel` when set, the
     * application's default channel otherwise.
     */
    private function log(): LoggerInterface
    {
        $channel = Config::get('teamleader.logging.channel');

        return is_string($channel) && $channel !== '' ? Log::channel($channel) : Log::driver();
    }
}
