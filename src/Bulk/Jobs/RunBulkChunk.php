<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk\Jobs;

use DateTimeInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use McoreServices\TeamleaderSDK\Bulk\BulkOperation;
use McoreServices\TeamleaderSDK\Bulk\QueuedResults;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;

/**
 * One chunk of a queued bulk operation, on the connection it was dispatched for.
 *
 * On a rate limit it releases itself for as long as Teamleader asks, instead
 * of holding the worker; the rows it finished are recorded first, so the
 * retry carries on after them. Retries for up to a day.
 */
final class RunBulkChunk implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Not readonly: a queued job is rebuilt from its payload. And not named
     * `$connection` — Queueable uses that for the queue connection.
     *
     * @param  string  $teamleaderConnection  The Teamleader connection to send on
     * @param  array<int|string, mixed>  $rows  Keyed as in the original input
     */
    public function __construct(
        public string $runId,
        public string $chunk,
        public string $teamleaderConnection,
        public string $resource,
        public string $operation,
        public array $rows,
        public bool $continueOnError,
    ) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    public function handle(ConnectionManager $manager): void
    {
        $results = new QueuedResults($this->runId);

        if ($this->batch()?->cancelled()) {
            $results->skip($this->chunk, array_keys($this->rows), 'Not sent: the batch was cancelled after a failure.');

            return;
        }

        $result = (new BulkOperation($manager->connection($this->teamleaderConnection), $this->resource, $this->operation, $this->rows))
            ->validateFirst(false)
            ->continueOnError($this->continueOnError)
            ->waitForRateLimit(false)
            ->resumeFrom($results->succeededIndices($this->chunk))
            ->run();

        foreach ($result->failed() as $failure) {
            if ($failure->exception instanceof RateLimitExceededException) {
                $results->record($this->chunk, $result, final: false);
                $this->release(max(1, $failure->exception->getRetryAfter()) + 1);

                return;
            }
        }

        $results->record($this->chunk, $result, final: true);

        // Without continueOnError, one refusal ends the whole run, not just this chunk
        if ($result->hasFailures() && ! $this->continueOnError) {
            $this->batch()?->cancel();
        }
    }
}
