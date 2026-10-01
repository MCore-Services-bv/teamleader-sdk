<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

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
}
