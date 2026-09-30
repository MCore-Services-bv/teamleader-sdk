<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tokens;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

/**
 * One connection's token pair and what the SDK knows about it.
 *
 * Immutable. The values here are plain text: encryption is the store's job,
 * so this object must never be logged, cached or serialised as it is.
 */
final readonly class StoredTokens
{
    public const CONNECTED = 'connected';

    public const NEEDS_REAUTHORIZATION = 'needs_reauthorization';

    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public ?CarbonImmutable $expiresAt,
        public int $expiresIn = 3600,
        public string $tokenType = 'Bearer',
        public string $status = self::CONNECTED,
        public ?string $accountId = null,
        public ?string $accountName = null,
        public ?CarbonImmutable $lastRefreshedAt = null,
    ) {}

    /**
     * Seconds until the access token expires; negative once expired, null when
     * the expiry is unknown.
     */
    public function secondsUntilExpiry(): ?int
    {
        return $this->expiresAt === null
            ? null
            : (int) CarbonImmutable::now()->diffInSeconds($this->expiresAt, false);
    }

    public function withStatus(string $status): self
    {
        return new self(
            $this->accessToken, $this->refreshToken, $this->expiresAt, $this->expiresIn, $this->tokenType,
            $status, $this->accountId, $this->accountName, $this->lastRefreshedAt,
        );
    }

    public function needsReauthorization(): bool
    {
        return $this->status === self::NEEDS_REAUTHORIZATION;
    }

    /**
     * Scalars only — dates as ISO 8601 — so the array survives any cache store.
     *
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt?->toIso8601String(),
            'expires_in' => $this->expiresIn,
            'token_type' => $this->tokenType,
            'status' => $this->status,
            'account_id' => $this->accountId,
            'account_name' => $this->accountName,
            'last_refreshed_at' => $this->lastRefreshedAt?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            refreshToken: isset($data['refresh_token']) && $data['refresh_token'] !== '' ? (string) $data['refresh_token'] : null,
            expiresAt: self::date($data['expires_at'] ?? null),
            expiresIn: (int) ($data['expires_in'] ?? 3600),
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
            status: (string) ($data['status'] ?? self::CONNECTED),
            accountId: isset($data['account_id']) ? (string) $data['account_id'] : null,
            accountName: isset($data['account_name']) ? (string) $data['account_name'] : null,
            lastRefreshedAt: self::date($data['last_refreshed_at'] ?? null),
        );
    }

    /**
     * Strings, unix timestamps and DateTimeInterface; anything else — including
     * the __PHP_Incomplete_Class an old cache entry can come back as — is null,
     * which callers treat as "expiry unknown, refresh".
     */
    public static function date(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return CarbonImmutable::createFromTimestamp((int) $value);
        }

        if (! is_string($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
