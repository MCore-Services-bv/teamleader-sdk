<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Connections;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Connection credentials kept in the `teamleader_connections` table, managed
 * with the teamleader:connections:* commands. Client ID and secret are
 * encrypted with APP_KEY.
 *
 * Read by ConnectionManager after config/teamleader.php, so a connection
 * defined in configuration always wins over one with the same name here.
 */
class DatabaseConnectionStore
{
    public const TABLE = 'teamleader_connections';

    /** Letters, digits, dashes and underscores; it ends up in URLs and cache keys */
    public const NAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,99}$/';

    public function __construct(private readonly ?string $databaseConnection = null) {}

    /**
     * A connection's configuration array, or null when it is not stored — or
     * when the table does not exist yet, so an application that never ran
     * the migration keeps working from configuration alone.
     *
     * @return array{client_id: string, client_secret: string, redirect_uri: ?string, expected_account_id: ?string}|null
     */
    public function get(string $name): ?array
    {
        $row = $this->quietly(fn () => $this->table()->where('name', $name)->first());

        if ($row === null) {
            return null;
        }

        return [
            'client_id' => Crypt::decryptString($row->client_id),
            'client_secret' => Crypt::decryptString($row->client_secret),
            'redirect_uri' => $row->redirect_uri,
            'expected_account_id' => $row->expected_account_id,
        ];
    }

    /**
     * @param  array{client_id: string, client_secret: string, redirect_uri?: ?string, expected_account_id?: ?string}  $config
     */
    public function put(string $name, array $config): void
    {
        self::assertValidName($name);

        $now = CarbonImmutable::now()->toDateTimeString();
        $values = [
            'client_id' => Crypt::encryptString($config['client_id']),
            'client_secret' => Crypt::encryptString($config['client_secret']),
            'redirect_uri' => $config['redirect_uri'] ?? null,
            'expected_account_id' => $config['expected_account_id'] ?? null,
            'updated_at' => $now,
        ];

        if ($this->table()->where('name', $name)->update($values) === 0) {
            $this->table()->insert(['name' => $name, 'created_at' => $now, ...$values]);
        }
    }

    /**
     * Rename a stored connection, keeping its credentials
     *
     * @return bool False when no connection by that name is stored
     */
    public function rename(string $from, string $to): bool
    {
        self::assertValidName($to);

        return $this->table()->where('name', $from)->update([
            'name' => $to,
            'updated_at' => CarbonImmutable::now()->toDateTimeString(),
        ]) > 0;
    }

    /**
     * Set or clear a stored connection's expected account, keeping its credentials
     *
     * @return bool False when no connection by that name is stored
     */
    public function setExpectedAccount(string $name, ?string $accountId): bool
    {
        return $this->table()->where('name', $name)->update([
            'expected_account_id' => $accountId,
            'updated_at' => CarbonImmutable::now()->toDateTimeString(),
        ]) > 0;
    }

    public function forget(string $name): bool
    {
        return $this->table()->where('name', $name)->delete() > 0;
    }

    public function has(string $name): bool
    {
        return (bool) $this->quietly(fn () => $this->table()->where('name', $name)->exists());
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->quietly(fn () => $this->table()->orderBy('name')->pluck('name')->map(fn ($n) => (string) $n)->all()) ?? [];
    }

    public static function assertValidName(string $name): void
    {
        if (! preg_match(self::NAME_PATTERN, $name)) {
            throw new InvalidArgumentException(
                "'{$name}' is not a valid connection name. Use letters, digits, dashes and underscores, up to 100 characters."
            );
        }
    }

    private function table(): Builder
    {
        return DB::connection($this->databaseConnection)->table(self::TABLE);
    }

    /**
     * Reads tolerate a missing table (null). Writes do not: they are explicit
     * commands, and an error there should say to run the migration.
     */
    private function quietly(callable $query): mixed
    {
        try {
            return $query();
        } catch (QueryException) {
            return null;
        }
    }
}
