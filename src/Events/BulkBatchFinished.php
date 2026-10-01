<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

use McoreServices\TeamleaderSDK\Bulk\BulkResult;

/**
 * Every chunk of a queued bulk operation has run.
 *
 * `$result` accounts for every row under its input key. Succeeded rows carry
 * only the new or changed record's id (`['data' => ['id' => ...]]`).
 */
final readonly class BulkBatchFinished
{
    public function __construct(
        public string $batchId,
        public string $connection,
        public BulkResult $result,
        public bool $cancelled,
    ) {}
}
