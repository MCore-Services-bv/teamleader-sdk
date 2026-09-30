<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Connections;

use Closure;
use McoreServices\TeamleaderSDK\Exceptions\ConfigurationException;
use McoreServices\TeamleaderSDK\TeamleaderSDK;

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
 *   4. resolveConnectionsUsing()      — your own lookup for any other name
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

        if ($name === 'default') {
            return ConnectionConfig::fromArray('default', [
                'client_id' => config('teamleader.client_id'),
                'client_secret' => config('teamleader.client_secret'),
                'redirect_uri' => config('teamleader.redirect_uri'),
                'expected_account_id' => config('teamleader.expected_account_id'),
            ]);
        }

        $resolved = $this->resolver ? ($this->resolver)($name) : null;

        if (is_array($resolved)) {
            return ConnectionConfig::fromArray($name, $resolved, $fallbackRedirect);
        }

        throw new ConfigurationException(
            "Teamleader connection '{$name}' is not configured. Add it to 'connections' in config/teamleader.php, "
            .'or define it with Teamleader::extend().'
        );
    }

    /**
     * The connections that are defined in configuration or with extend().
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

        $names = array_values(array_unique([...$names, ...array_keys($this->extensions)]));
        sort($names);

        return $names;
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
    private function defaultRedirectUri(): ?string
    {
        $uri = config('teamleader.connections.default.redirect_uri') ?: config('teamleader.redirect_uri');

        return is_string($uri) && $uri !== '' ? $uri : null;
    }
}
