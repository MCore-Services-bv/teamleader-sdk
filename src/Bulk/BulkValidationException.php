<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

use InvalidArgumentException;

/**
 * Rows failed client-side validation, and validateFirst() was on: nothing was
 * sent. Every invalid row is listed, not just the first.
 */
final class BulkValidationException extends InvalidArgumentException
{
    /**
     * @param  array<int|string, BulkFailure>  $failures
     */
    public function __construct(
        public readonly string $resource,
        public readonly array $failures,
        public readonly int $rows,
    ) {
        $lines = [];

        foreach (array_slice($failures, 0, 10, true) as $index => $failure) {
            $lines[] = "  row {$index}: {$failure->message()}";
        }

        $more = count($failures) > 10 ? "\n  … and ".(count($failures) - 10).' more' : '';

        parent::__construct(
            count($failures)." of {$rows} rows are invalid for {$resource}; nothing was sent.\n"
            .implode("\n", $lines).$more
        );
    }
}
