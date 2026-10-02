<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Testing;

use McoreServices\TeamleaderSDK\Connections\ConnectionConfig;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\TeamleaderSDK;

/**
 * The connection manager behind Teamleader::fake(): every connection — the
 * default one and any name — is a FakeTeamleader. They share the stubs given
 * to fake(), and their calls are recorded here as well, so the facade's
 * assertions cover every connection:
 *
 *     Teamleader::assertSent('companies.list');                      // on any connection
 *     Teamleader::connection('antwerp')->assertSent('companies.list'); // on one
 */
class FakeConnectionManager extends ConnectionManager
{
    use AssertsTeamleaderRequests;

    /** @var array<string, FakeTeamleader> */
    private array $fakes = [];

    /** @var list<array{method: string, endpoint: string, body: array, connection: string}> */
    private array $recorded = [];

    private bool $preventStrayRequests = false;

    /**
     * @param  array<string, mixed>  $stubs  endpoint or pattern => stub, for every connection
     */
    public function __construct(private array $stubs = []) {}

    public function connection(?string $name = null): TeamleaderSDK
    {
        $name ??= $this->getDefaultConnection();

        return $this->fakes[$name] ??= new FakeTeamleader($name, [], $this);
    }

    /** Add stubs for every connection; later ones win */
    public function stub(array $stubs): static
    {
        $this->stubs = $stubs + $this->stubs;

        return $this;
    }

    /** @return array<string, mixed> */
    public function stubs(): array
    {
        return $this->stubs;
    }

    /** Throw on any request no stub answers, instead of an empty success */
    public function preventStrayRequests(bool $prevent = true): static
    {
        $this->preventStrayRequests = $prevent;

        return $this;
    }

    public function preventsStrayRequests(): bool
    {
        return $this->preventStrayRequests;
    }

    /** @internal Called by each FakeTeamleader */
    public function record(array $call): void
    {
        $this->recorded[] = $call;
    }

    protected function recordedCalls(): array
    {
        return $this->recorded;
    }

    // -- the manager's own API, answered without configuration ------------------

    public function config(string $name): ConnectionConfig
    {
        return $this->connection($name)->getConnectionConfig();
    }

    public function isConfigured(string $name): bool
    {
        return true;
    }

    public function sourceOf(string $name): ?string
    {
        return 'fake';
    }

    public function names(): array
    {
        $names = array_keys($this->fakes);

        if (! in_array($this->getDefaultConnection(), $names, true)) {
            $names[] = $this->getDefaultConnection();
        }

        sort($names);

        return $names;
    }

    public function statuses(): array
    {
        $statuses = [];

        foreach ($this->names() as $name) {
            $statuses[$name] = [
                'connection' => $name,
                'configured' => true,
                'status' => 'connected',
                'account_id' => null,
                'account_name' => 'Fake',
                'expires_in' => 3600,
                'last_refreshed_at' => null,
                'error' => null,
            ];
        }

        return $statuses;
    }

    public function purge(?string $name = null): void
    {
        if ($name === null) {
            $this->fakes = [];
        } else {
            unset($this->fakes[$name]);
        }
    }
}
