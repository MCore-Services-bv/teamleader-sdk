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
     * The one bulk operation that cannot be undone, so the list is checked up
     * front: an empty list, or one naming the same record twice, is refused —
     * both usually mean the ids came from the wrong place.
     *
     * @param  iterable<int|string, string|array{id: string}>  $ids
     *
     * @throws InvalidArgumentException When the list is empty or has duplicates
     */
    public function delete(string $resource, iterable $ids): BulkOperation
    {
        $ids = is_array($ids) ? $ids : iterator_to_array($ids, true);

        if ($ids === []) {
            throw new InvalidArgumentException("No ids given to delete from {$resource}.");
        }

        $seen = [];

        foreach ($ids as $key => $id) {
            $value = is_array($id) ? ($id['id'] ?? null) : $id;

            if (is_string($value) && isset($seen[$value])) {
                throw new InvalidArgumentException(
                    "Id {$value} appears twice in the delete list (rows {$seen[$value]} and {$key}). "
                    .'Nothing was deleted; check where the list came from.'
                );
            }

            if (is_string($value)) {
                $seen[$value] = $key;
            }
        }

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
