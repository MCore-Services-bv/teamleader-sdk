<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\TeamleaderSDK;

/**
 * Bulk operations on one connection — `Teamleader::bulk()`, or
 * `Teamleader::connection('antwerp')->bulk()`.
 */
final class BulkManager
{
    public function __construct(private readonly TeamleaderSDK $sdk) {}

    /**
     * Every record of a resource, for writing to a file or a callback.
     *
     * @param  string  $resource  The resource key, as for Teamleader::{key}() — `contacts`, `deals`
     * @param  array  $filters  As for list()
     * @param  array  $options  As for list(); `page_size` defaults to 100
     */
    public function export(string $resource, array $filters = [], array $options = []): Export
    {
        return new Export($this->sdk->resource($resource), $filters, $options);
    }

    /**
     * Create one record per row through `{resource}()->create($row)`.
     *
     * @param  iterable<int|string, array<string, mixed>>  $rows  Keys are kept, so results map back to your input
     */
    public function create(string $resource, iterable $rows): BulkOperation
    {
        return $this->operation($resource, 'create', $rows);
    }

    /**
     * Update one record per row through `update($row['id'], $rest)`.
     *
     * @param  iterable<int|string, array<string, mixed>>  $rows  Each with an `id`
     */
    public function update(string $resource, iterable $rows): BulkOperation
    {
        return $this->operation($resource, 'update', $rows);
    }

    /**
     * Delete one record per id.
     *
     * @param  iterable<int|string, string|array{id: string}>  $ids
     */
    public function delete(string $resource, iterable $ids): BulkOperation
    {
        return $this->operation($resource, 'delete', $ids);
    }

    /**
     * Any resource method, once per row — tagging, linking, `deals()->win()`.
     * A row that is a list is spread as the arguments; anything else is the
     * single argument.
     *
     *     Teamleader::bulk()->call('deals', 'win', $dealIds)->run();
     *     Teamleader::bulk()->call('deals', 'lose', [[$id, $reasonId], ...])->run();
     *
     * @param  iterable<int|string, mixed>  $argumentsPerRow
     */
    public function call(string $resource, string $method, iterable $argumentsPerRow): BulkOperation
    {
        if (! method_exists($this->sdk->resource($resource), $method)) {
            throw new InvalidArgumentException("{$resource}() has no method {$method}().");
        }

        return $this->operation($resource, "call:{$method}", $argumentsPerRow);
    }

    /**
     * What a queued bulk operation has done so far — or in total, once its
     * batch has finished. Null for an unknown or expired batch (kept a week).
     */
    public function result(string $batchId): ?BulkResult
    {
        return QueuedResults::forBatch($batchId)?->collect();
    }

    private function operation(string $resource, string $operation, iterable $rows): BulkOperation
    {
        // Resolve now: a typo fails here, not after validating every row
        $this->sdk->resource($resource);

        return new BulkOperation($this->sdk, $resource, $operation, is_array($rows) ? $rows : iterator_to_array($rows, true));
    }
}
