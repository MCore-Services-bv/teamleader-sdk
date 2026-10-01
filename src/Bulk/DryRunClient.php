<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

use McoreServices\TeamleaderSDK\TeamleaderSDK;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * A TeamleaderSDK that records requests instead of sending them.
 *
 * Resources reach the API only through request(), so a resource built on this
 * client runs its own validation and builds the exact body it would send —
 * and nothing leaves the process. Bulk operations validate every row this
 * way before sending any, and the CLI's --dry-run prints what it recorded.
 *
 * The parent constructor is deliberately not called: it resolves credentials
 * and reads tokens, which a dry run must not need.
 */
final class DryRunClient extends TeamleaderSDK
{
    /** @var list<array{method: string, endpoint: string, body: array}> */
    private array $recorded = [];

    public function __construct(private readonly string $connection = 'default') {}

    public function request($method, $endpoint, $data = [])
    {
        $this->recorded[] = ['method' => (string) $method, 'endpoint' => (string) $endpoint, 'body' => (array) $data];

        // Shaped like a successful answer, so code that reads the response
        // (an id after a create) keeps going
        // `location` lets Files::uploadFile() continue to its second step
        return ['data' => ['id' => 'dry-run', 'type' => 'dry-run', 'location' => 'dry-run'], 'headers' => [], 'dry_run' => true];
    }

    /**
     * Record the file upload instead of sending it. The contents are not read.
     */
    public function sendFileContents(string $location, mixed $contents): array
    {
        $this->recorded[] = ['method' => 'POST', 'endpoint' => 'files.upload (contents)', 'body' => ['location' => $location]];

        return ['data' => ['id' => 'dry-run', 'type' => 'file'], 'dry_run' => true];
    }

    /**
     * What has been recorded since the last call, and forget it.
     *
     * @return list<array{method: string, endpoint: string, body: array}>
     */
    public function pull(): array
    {
        $recorded = $this->recorded;
        $this->recorded = [];

        return $recorded;
    }

    public function connectionName(): string
    {
        return $this->connection;
    }

    public function getLogger(): LoggerInterface
    {
        return new NullLogger;
    }
}
