<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

/**
 * Where a bulk operation is, passed to onProgress() after every row.
 */
final readonly class BulkProgress
{
    public function __construct(
        public int $processed,
        public int $total,
        public int $succeeded,
        public int $failed,
        public int $skipped,
        public int|string $lastIndex,
    ) {}

    public function percentage(): float
    {
        return $this->total === 0 ? 100.0 : round($this->processed / $this->total * 100, 1);
    }
}
