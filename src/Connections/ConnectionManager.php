<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Connections;

use Closure;
use McoreServices\TeamleaderSDK\Exceptions\ConfigurationException;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tokens\DatabaseTokenStore;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;
use Throwable;

/**
 * Builds one TeamleaderSDK per connection and keeps it.
 *
 * Modelled on Laravel's DatabaseManager. Each SDK instance has the
 * connection's own credentials, tokens, cache entries, refresh lock and
 * rate-limit window, and every resource made from it inherits them:
 *
 *     Teamleader::companies()->list();                         // default connection
 *     Teamleader::connection('antwerp')->companies()->list();  // a named one
 *
 * Where a connection comes from, first match wins:
 *
 *   1. extend()                       — defined at runtime
 *   2. config('teamleader.connections')
 *   3. the flat 2.x keys, for `default` — client_id, client_secret, redirect_uri
 *   4. the teamleader_connections table — `php artisan teamleader:connections:add`
 *   5. resolveConnectionsUsing()      — your own lookup for any other name
 */
class ConnectionManager
{
    /** @var array<string, TeamleaderSDK> */
    private array $connections = [];

    /** @var array<string, Closure(): array<string, mixed>> */
    private array $extensions = [];

    /** @var (Closure(string): (array<string, mixed>|null))|null */
    private ?Closure $resolver = null;

    /** The SDK for a connection; the default connection when no name is given */
    public function connection(?string $name = null): TeamleaderSDK
    {
        $name ??= $this->getDefaultConnection();

        return $this->connections[$name] ??= new TeamleaderSDK(connection: $this->config($name));
    }

    public function getDefaultConnection(): string
    {
        $default = config('teamleader.default');

        return is_string($default) && $default !== '' ? $default : 'default';
    }

    /**
     * Define a connection at runtime — a tenant from your own database, say.
     *
     * @param  Closure(): array<string, mixed>  $config  Returns client_id, client_secret,
     *                                                   and optionally redirect_uri and
     *                                                   expected_account_id
     */
    public function extend(string $name, Closure $config): static
    {
        $this->extensions[$name] = $config;
        $this->purge($name);

        return $this;
    }

    /**
     * Resolve every name that is not configured through your own lookup.
     *
     * @param  Closure(string): (array<string, mixed>|null)  $resolver  Null for an unknown name
     */
    public function resolveConnectionsUsing(Closure $resolver): static
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * A connection's credentials
     *
     * @throws ConfigurationException When the name is not configured anywhere, or incomplete
     */
    public function config(string $name): ConnectionConfig
    {
        $fallbackRedirect = $this->defaultRedirectUri();

        if (isset($this->extensions[$name])) {
            return ConnectionConfig::fromArray($name, (array) ($this->extensions[$name])(), $fallbackRedirect);
        }

        $configured = config("teamleader.connections.{$name}");

        if (is_array($configured)) {
            return ConnectionConfig::fromArray($name, $configured, $fallbackRedirect);
        }

        if ($name === 'default' && config('teamleader.client_id')) {
            return $this->flatDefault();
        }

        $stored = $this->connectionStore()->get($name);

        if ($stored !== null) {
            return ConnectionConfig::fromArray($name, $stored, $fallbackRedirect);
        }

        if ($name === 'default') {
            // Throws, naming the missing keys
            return $this->flatDefault();
        }

        $resolved = $this->resolver ? ($this->resolver)($name) : null;

        if (is_array($resolved)) {
            return ConnectionConfig::fromArray($name, $resolved, $fallbackRedirect);
        }

        throw new ConfigurationException(
            "Teamleader connection '{$name}' is not configured. Add it to 'connections' in config/teamleader.php, "
            .'run `php artisan teamleader:connections:add '.$name.'`, or define it with Teamleader::extend().'
        );
    }

    /**
     * Where a connection is defined: config, database, runtime, or null
     */
    public function sourceOf(string $name): ?string
    {
        return match (true) {
            isset($this->extensions[$name]) => 'runtime',
            is_array(config("teamleader.connections.{$name}")),
            $name === 'default' && (bool) config('teamleader.client_id') => 'config',
            $this->connectionStore()->has($name) => 'database',
            default => null,
        };
    }

    private function flatDefault(): ConnectionConfig
    {
        return ConnectionConfig::fromArray('default', [
            'client_id' => config('teamleader.client_id'),
            'client_secret' => config('teamleader.client_secret'),
            'redirect_uri' => config('teamleader.redirect_uri'),
            'expected_account_id' => config('teamleader.expected_account_id'),
        ]);
    }

    public function connectionStore(): DatabaseConnectionStore
    {
        return app()->bound(DatabaseConnectionStore::class) ? app(DatabaseConnectionStore::class) : new DatabaseConnectionStore;
    }

    /**
     * The connections that are defined in configuration, in the
     * teamleader_connections table, or with extend().
     * Names only reachable through resolveConnectionsUsing() are not listed.
     *
     * @return list<string>
     */
    public function names(): array
    {
        $names = array_keys((array) config('teamleader.connections', []));

        if (! in_array('default', $names, true) && config('teamleader.client_id')) {
            $names[] = 'default';
        }

        $names = array_values(array_unique([...$names, ...$this->connectionStore()->names(), ...array_keys($this->extensions)]));
        sort($names);

        return $names;
    }

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_NEEDS_REAUTHORIZATION = 'needs_reauthorization';

    public const STATUS_NOT_CONNECTED = 'not_connected';

    /** Tokens stored for a name that is no longer configured */
    public const STATUS_NOT_CONFIGURED = 'not_configured';

    public const STATUS_UNREADABLE = 'unreadable';

    /**
     * Every connection that is configured or has tokens stored, with what is
     * known about it — for teamleader:status --all, teamleader:health and
     * teamleader:tokens:refresh. Reads the token store only; sends nothing.
     *
     * @return array<string, array{
     *     connection: string, configured: bool, status: string,
     *     account_id: ?string, account_name: ?string,
     *     expires_in: ?int, last_refreshed_at: ?string, error: ?string
     * }>
     */
    public function statuses(): array
    {
        $store = $this->tokenStore();

        try {
            $stored = $store->connections();
        } catch (Throwable $e) {
            $stored = [];
            $storeError = $e->getMessage();
        }

        $names = array_values(array_unique([...$this->names(), ...$stored]));
        sort($names);

        $statuses = [];

        foreach ($names as $name) {
            $row = [
                'connection' => $name,
                'configured' => $this->isConfigured($name),
                'status' => self::STATUS_NOT_CONNECTED,
                'account_id' => null,
                'account_name' => null,
                'expires_in' => null,
                'last_refreshed_at' => null,
                'error' => $storeError ?? null,
            ];

            try {
                $tokens = in_array($name, $stored, true) ? $store->get($name) : null;
            } catch (Throwable $e) {
                $tokens = null;
                $row['status'] = self::STATUS_UNREADABLE;
                $row['error'] = $e->getMessage();
            }

            if ($tokens !== null) {
                $row['status'] = $tokens->needsReauthorization() ? self::STATUS_NEEDS_REAUTHORIZATION : self::STATUS_CONNECTED;
                $row['account_id'] = $tokens->accountId;
                $row['account_name'] = $tokens->accountName;
                $row['expires_in'] = $tokens->secondsUntilExpiry();
                $row['last_refreshed_at'] = $tokens->lastRefreshedAt?->toIso8601String();
            }

            if (! $row['configured'] && $tokens !== null) {
                $row['status'] = self::STATUS_NOT_CONFIGURED;
            }

            $statuses[$name] = $row;
        }

        return $statuses;
    }

    /** Whether config() resolves the name — without building an SDK */
    public function isConfigured(string $name): bool
    {
        try {
            $this->config($name);

            return true;
        } catch (ConfigurationException) {
            return false;
        }
    }

    private function tokenStore(): TokenStore
    {
        return app()->bound(TokenStore::class) ? app(TokenStore::class) : new DatabaseTokenStore;
    }

    /** Forget a built SDK, so the next connection() call builds it again */
    public function purge(?string $name = null): void
    {
        if ($name === null) {
            $this->connections = [];

            return;
        }

        unset($this->connections[$name]);
    }

    /** redirect_uri shared by every connection that sets none */
    public function defaultRedirectUri(): ?string
    {
        $uri = config('teamleader.connections.default.redirect_uri') ?: config('teamleader.redirect_uri');

        return is_string($uri) && $uri !== '' ? $uri : null;
    }
}
