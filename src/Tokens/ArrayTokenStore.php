<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tokens;

/**
 * Tokens kept in memory for the lifetime of the object — for tests and the
 * fake. Nothing is persisted and nothing is encrypted, so never bind this in
 * production.
 */
final class ArrayTokenStore implements TokenStore
{
    /** @var array<string, StoredTokens> */
    private array $tokens = [];

    public function get(string $connection): ?StoredTokens
    {
        return $this->tokens[$connection] ?? null;
    }

    public function put(string $connection, StoredTokens $tokens): void
    {
        $this->tokens[$connection] = $tokens;
    }

    public function forget(string $connection): void
    {
        unset($this->tokens[$connection]);
    }

    public function connections(): array
    {
        return array_keys($this->tokens);
    }
}
