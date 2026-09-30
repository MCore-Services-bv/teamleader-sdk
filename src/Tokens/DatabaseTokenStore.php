<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tokens;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Tokens in the `teamleader_tokens` table, one row per connection, with the
 * access and refresh token encrypted by Laravel's encrypter (APP_KEY).
 *
 * Rows written by v2.x hold plain-text tokens. They are read as they are and
 * rewritten encrypted on the spot, so an upgrade needs no manual step beyond
 * `php artisan migrate`.
 *
 * Rotating APP_KEY makes the stored tokens unreadable. That surfaces as a
 * TokenStorageException naming the connection; connect it again. (Laravel's
 * APP_PREVIOUS_KEYS avoids this during a planned rotation.)
 */
class DatabaseTokenStore implements TokenStore
{
    public const TABLE = 'teamleader_tokens';

    /**
     * @param  string|null  $databaseConnection  Database connection name; null
     *                                           uses the application default
     */
    public function __construct(private readonly ?string $databaseConnection = null) {}

    public function get(string $connection): ?StoredTokens
    {
        $row = $this->query(fn () => $this->table()->where('connection', $connection)->first());

        if ($row === null) {
            return null;
        }

        $plainTextFound = false;
        $accessToken = $this->decrypt((string) $row->access_token, $connection, $plainTextFound);
        $refreshToken = $row->refresh_token === null || $row->refresh_token === ''
            ? null
            : $this->decrypt((string) $row->refresh_token, $connection, $plainTextFound);

        $tokens = new StoredTokens(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresAt: StoredTokens::date($row->expires_at),
            expiresIn: (int) $row->expires_in,
            tokenType: (string) ($row->token_type ?: 'Bearer'),
            status: (string) ($row->status ?: StoredTokens::CONNECTED),
            accountId: $row->account_id,
            accountName: $row->account_name,
            lastRefreshedAt: StoredTokens::date($row->last_refreshed_at),
        );

        // A row from v2.x: encrypt it now rather than on the next refresh
        if ($plainTextFound) {
            $this->put($connection, $tokens);
        }

        return $tokens;
    }

    public function put(string $connection, StoredTokens $tokens): void
    {
        $now = CarbonImmutable::now()->toDateTimeString();

        $values = [
            'access_token' => Crypt::encryptString($tokens->accessToken),
            'refresh_token' => $tokens->refreshToken === null ? null : Crypt::encryptString($tokens->refreshToken),
            'token_type' => $tokens->tokenType,
            'expires_in' => $tokens->expiresIn,
            'expires_at' => $tokens->expiresAt?->toDateTimeString(),
            'status' => $tokens->status,
            'account_id' => $tokens->accountId,
            'account_name' => $tokens->accountName,
            'last_refreshed_at' => $tokens->lastRefreshedAt?->toDateTimeString(),
            'updated_at' => $now,
        ];

        $this->query(function () use ($connection, $values, $now) {
            $updated = $this->table()->where('connection', $connection)->update($values);

            if ($updated === 0 && ! $this->table()->where('connection', $connection)->exists()) {
                $this->table()->insert(['connection' => $connection, 'created_at' => $now, ...$values]);
            }
        });
    }

    public function forget(string $connection): void
    {
        $this->query(fn () => $this->table()->where('connection', $connection)->delete());
    }

    public function connections(): array
    {
        return $this->query(fn () => $this->table()->orderBy('connection')->pluck('connection')->map(fn ($name) => (string) $name)->all());
    }

    private function table(): Builder
    {
        return $this->db()->table(self::TABLE);
    }

    private function db(): ConnectionInterface
    {
        return DB::connection($this->databaseConnection);
    }

    /**
     * Run a query, turning a missing table or column into an actionable error.
     *
     * @template T
     *
     * @param  callable(): T  $query
     * @return T
     */
    private function query(callable $query): mixed
    {
        try {
            return $query();
        } catch (QueryException $e) {
            throw new TokenStorageException(
                'Could not use the '.self::TABLE.' table: '.$e->getMessage()
                .' — if the table is missing or predates SDK v3.0, run `php artisan migrate`.',
                0,
                $e
            );
        }
    }

    /**
     * Decrypt a stored token. A value that is not an encrypted payload at all
     * is a v2.x plain-text token: returned as it is, and flagged for rewriting.
     * A payload that fails to decrypt was encrypted with another APP_KEY.
     */
    private function decrypt(string $value, string $connection, bool &$plainTextFound): string
    {
        if (! $this->looksEncrypted($value)) {
            $plainTextFound = true;

            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            throw new TokenStorageException(
                "The stored tokens for connection '{$connection}' cannot be decrypted: they were encrypted "
                .'with a different APP_KEY. Connect the account again, or add the old key to APP_PREVIOUS_KEYS.',
                0,
                $e
            );
        }
    }

    /** Laravel's payload: base64 of a JSON object with iv, value and mac */
    private function looksEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
    }
}
