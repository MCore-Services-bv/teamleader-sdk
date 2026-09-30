<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tokens;

/**
 * Where each connection's tokens are kept.
 *
 * The SDK binds DatabaseTokenStore. Bind your own implementation to store
 * tokens elsewhere — a secrets manager, a tenants table:
 *
 *     $this->app->singleton(TokenStore::class, VaultTokenStore::class);
 *
 * An implementation is responsible for protecting the values at rest.
 * TokenService caches what get() returns, encrypted, in the application's
 * cache store; the store itself is read on a cache miss.
 */
interface TokenStore
{
    /** The tokens for a connection, or null when it was never connected */
    public function get(string $connection): ?StoredTokens;

    /** Replace a connection's tokens */
    public function put(string $connection, StoredTokens $tokens): void;

    /** Remove a connection's tokens */
    public function forget(string $connection): void;

    /**
     * Every connection that has tokens stored
     *
     * @return list<string>
     */
    public function connections(): array;
}
