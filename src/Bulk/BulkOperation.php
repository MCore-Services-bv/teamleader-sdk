<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

use Closure;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use McoreServices\TeamleaderSDK\Bulk\Jobs\RunBulkChunk;
use McoreServices\TeamleaderSDK\Events\BulkBatchFinished;
use McoreServices\TeamleaderSDK\Exceptions\ConnectionNeedsReauthorizationException;
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use Throwable;

/**
 * Many writes to one resource, one row at a time, through the resource's own
 * methods — so every row gets exactly the validation a single call gets.
 *
 *     $result = Teamleader::bulk()->create('companies', $rows)
 *         ->continueOnError()
 *         ->run();
 *
 * By default every row is validated before any is sent: an import with one
 * bad row out of ten thousand fails before the first request, listing every
 * invalid row, instead of half-way through.
 *
 * Requests are sequential, through the rate limiter. At 200 requests a
 * minute, 10,000 rows take about 50 minutes; for that, see dispatch().
 */
final class BulkOperation
{
    public const ALREADY_SUCCEEDED = 'Already succeeded in an earlier run.';

    private bool $validateFirst = true;

    private bool $continueOnError = false;

    private bool $waitForRateLimit = true;

    /** @var (Closure(mixed, int|string): (string|int|null))|null */
    private ?Closure $uniqueBy = null;

    /** @var array<int|string, true> */
    private array $alreadySucceeded = [];

    /** @var (Closure(BulkProgress): void)|null */
    private ?Closure $progress = null;

    /**
     * @param  string  $operation  create, update, delete or call:{method}
     * @param  array<int|string, mixed>  $rows
     */
    public function __construct(
        private readonly TeamleaderSDK $sdk,
        private readonly string $resource,
        private readonly string $operation,
        private readonly array $rows,
    ) {}

    /**
     * Validate every row before sending any (the default). Off: rows are
     * validated as they are sent, and an invalid row fails on its own.
     */
    public function validateFirst(bool $validate = true): self
    {
        $this->validateFirst = $validate;

        return $this;
    }

    /**
     * Keep going after a row the API refuses. Off (the default): stop at the
     * first refusal; the remaining rows are reported as skipped.
     */
    public function continueOnError(bool $continue = true): self
    {
        $this->continueOnError = $continue;

        return $this;
    }

    /**
     * Wait out a full rate-limit window (65 s) instead of failing — right for
     * a CLI import, wrong for a web request. On by default for run(); queued
     * chunks release themselves instead.
     */
    public function waitForRateLimit(bool $wait = true): self
    {
        $this->waitForRateLimit = $wait;

        return $this;
    }

    /**
     * Send only the first of rows sharing a key; the others are skipped. Teamleader
     * has no idempotency keys, so a duplicate in the input is a duplicate record.
     *
     *     ->uniqueBy(fn (array $row) => $row['vat_number'] ?? null)   // null: never a duplicate
     *
     * @param  Closure(mixed $row, int|string $index): (string|int|null)  $key
     */
    public function uniqueBy(Closure $key): self
    {
        $this->uniqueBy = $key;

        return $this;
    }

    /**
     * Skip the rows a previous run already did — after it stopped, or after a
     * crash, with the indices you stored from it.
     *
     * @param  BulkResult|list<int|string>  $previous  A result, or its succeededIndices()
     */
    public function resumeFrom(BulkResult|array $previous): self
    {
        $indices = $previous instanceof BulkResult ? $previous->succeededIndices() : $previous;
        $this->alreadySucceeded = array_fill_keys($indices, true);

        return $this;
    }

    /**
     * @param  Closure(BulkProgress): void  $callback
     */
    public function onProgress(Closure $callback): self
    {
        $this->progress = $callback;

        return $this;
    }

    /**
     * Validate every row and record what would be sent — sending nothing.
     * requests() on the result holds the exact bodies.
     */
    public function dryRun(): BulkResult
    {
        $result = new BulkResult($this->resource, $this->operation, true);
        [$toSend] = $this->plan($result);
        $client = new DryRunClient($this->sdk->connectionName());
        $resource = $client->resource($this->resource);

        foreach ($toSend as $index => $row) {
            try {
                $result->recordSuccess($index, $this->invoke($resource, $row));
            } catch (InvalidArgumentException $e) {
                $result->recordFailure(new BulkFailure($index, $row, $e, true));
            }

            $result->recordRequests($index, $client->pull());
        }

        return $result;
    }

    /**
     * Send the rows.
     *
     * @throws BulkValidationException When validateFirst() is on and any row is invalid — nothing is sent
     */
    public function run(): BulkResult
    {
        $result = new BulkResult($this->resource, $this->operation);
        [$toSend] = $this->plan($result);

        if ($this->validateFirst) {
            $failures = $this->validate($toSend);

            if ($failures !== []) {
                throw new BulkValidationException($this->resource, $failures, count($this->rows));
            }
        }

        $resource = $this->sdk->resource($this->resource);
        $handler = $this->sdk->getErrorHandler();
        $throwing = $handler->getThrowExceptions();
        $maxWait = config('teamleader.rate_limiting.max_wait_ms');

        // Exceptions on for our own calls, whatever the application chose:
        // a failure returned as an array would be counted as a success
        $handler->setThrowExceptions(true);

        if ($this->waitForRateLimit) {
            config(['teamleader.rate_limiting.max_wait_ms' => max((int) $maxWait, 65000)]);
        }

        try {
            $stopped = false;

            foreach ($toSend as $index => $row) {
                if ($stopped) {
                    $result->recordSkip($index, 'Not sent: the operation stopped at an earlier row.');
                    $this->report($result, $index);

                    continue;
                }

                try {
                    $result->recordSuccess($index, $this->invoke($resource, $row));
                } catch (Throwable $e) {
                    $result->recordFailure(new BulkFailure($index, $row, $e, $e instanceof InvalidArgumentException));

                    // These stop everything: no later row can do better
                    $stopped = ! $this->continueOnError
                        || $e instanceof RateLimitExceededException
                        || $e instanceof ConnectionNeedsReauthorizationException;
                }

                $this->report($result, $index);
            }
        } finally {
            $handler->setThrowExceptions($throwing);
            config(['teamleader.rate_limiting.max_wait_ms' => $maxWait]);
        }

        return $result;
    }

    /**
     * Send the rows from queue workers, in chunks, as one Laravel job batch.
     *
     * Validation (validateFirst), uniqueBy() and resumeFrom() are applied here,
     * before anything is queued: an invalid row throws now, in the request
     * that dispatches, and nothing is queued.
     *
     * Each chunk is one job on this connection. On a rate limit a job
     * releases itself for as long as Teamleader asks instead of holding the
     * worker, and resumes after the rows it already sent. Without
     * continueOnError(), the first refusal cancels the remaining chunks.
     *
     * When every chunk has run, BulkBatchFinished is fired with the result;
     * Teamleader::bulk()->result($batch->id) reads it at any time.
     *
     * Needs a real queue (not `sync`) and Laravel's job_batches table
     * (`php artisan make:queue-batches-table && php artisan migrate`).
     *
     * @param  int  $chunk  Rows per job
     * @param  string|null  $queueConnection  Queue connection; null uses the default
     * @param  string|null  $queue  Queue name
     *
     * @throws BulkValidationException When validateFirst() is on and any row is invalid — nothing is queued
     * @throws LogicException On the sync queue driver
     */
    public function dispatch(int $chunk = 50, ?string $queueConnection = null, ?string $queue = null): Batch
    {
        if ($chunk < 1) {
            throw new InvalidArgumentException("chunk must be at least 1, {$chunk} given.");
        }

        $queueConnection ??= (string) config('queue.default');

        if (config("queue.connections.{$queueConnection}.driver") === 'sync') {
            throw new LogicException(
                "Queue connection '{$queueConnection}' uses the sync driver: the whole bulk operation would run "
                .'inside this request. Use run() here, or dispatch on a real queue (database, redis, sqs).'
            );
        }

        $result = new BulkResult($this->resource, $this->operation);
        [$toSend] = $this->plan($result);

        if ($this->validateFirst) {
            $failures = $this->validate($toSend);

            if ($failures !== []) {
                throw new BulkValidationException($this->resource, $failures, count($this->rows));
            }
        }

        $runId = (string) Str::uuid();
        $connection = $this->sdk->connectionName();
        $chunks = array_chunk($toSend, $chunk, true);
        $chunkIds = array_map('strval', array_keys($chunks));

        $results = new QueuedResults($runId);
        $results->start($connection, $this->resource, $this->operation, ['planned', ...$chunkIds]);

        // Rows skipped while planning (duplicates, earlier runs) belong in the result too
        foreach ($result->skipped() as $index => $reason) {
            if ($reason !== self::ALREADY_SUCCEEDED) {
                $results->skip('planned', [$index], $reason);
            }
        }

        $jobs = [];

        foreach ($chunks as $id => $rows) {
            $jobs[] = new RunBulkChunk($runId, (string) $id, $connection, $this->resource, $this->operation, $rows, $this->continueOnError);
        }

        $batch = Bus::batch($jobs)
            ->name("Teamleader {$this->operation} {$this->resource} ({$connection})")
            ->allowFailures()
            ->onConnection($queueConnection)
            ->finally(function (Batch $batch) use ($runId, $connection) {
                $result = (new QueuedResults($runId))->collect();

                if ($result !== null) {
                    event(new BulkBatchFinished($batch->id, $connection, $result, $batch->cancelled()));
                }
            });

        if ($queue !== null) {
            $batch->onQueue($queue);
        }

        $dispatched = $batch->dispatch();
        $results->linkBatch($dispatched->id);

        return $dispatched;
    }

    /**
     * The rows to send, after recording duplicates and already-done rows as skipped.
     *
     * @return array{0: array<int|string, mixed>}
     */
    private function plan(BulkResult $result): array
    {
        $toSend = [];
        $seen = [];

        foreach ($this->rows as $index => $row) {
            if (isset($this->alreadySucceeded[$index])) {
                $result->recordSkip($index, self::ALREADY_SUCCEEDED);

                continue;
            }

            if ($this->uniqueBy !== null) {
                $key = ($this->uniqueBy)($row, $index);

                if ($key !== null && isset($seen[$key])) {
                    $result->recordSkip($index, "Duplicate of row {$seen[$key]} by uniqueBy().");

                    continue;
                }

                if ($key !== null) {
                    $seen[$key] = $index;
                }
            }

            $toSend[$index] = $row;
        }

        return [$toSend];
    }

    /**
     * Every row through the resource's own validation, on a client that sends nothing.
     *
     * @param  array<int|string, mixed>  $rows
     * @return array<int|string, BulkFailure>
     */
    private function validate(array $rows): array
    {
        $resource = (new DryRunClient($this->sdk->connectionName()))->resource($this->resource);
        $failures = [];

        foreach ($rows as $index => $row) {
            try {
                $this->invoke($resource, $row);
            } catch (InvalidArgumentException $e) {
                $failures[$index] = new BulkFailure($index, $row, $e, true);
            }
        }

        return $failures;
    }

    /**
     * One row, as the method call it stands for.
     */
    private function invoke(Resource $resource, mixed $row): array
    {
        [$method, $arguments] = $this->callFor($row);

        $response = $resource->{$method}(...$arguments);

        // Exceptions are forced on, but an error array must never count as a success
        if (is_array($response) && ! empty($response['error'])) {
            if ((int) ($response['status_code'] ?? 0) === 429) {
                throw new RateLimitExceededException((string) ($response['message'] ?? 'Rate limit exceeded'));
            }

            throw new TeamleaderException(
                (string) ($response['message'] ?? 'The API refused the row'),
                (int) ($response['status_code'] ?? 0),
                null,
                ['response' => $response['response'] ?? null],
                isset($response['status_code']) ? (int) $response['status_code'] : null,
                (array) ($response['errors'] ?? [])
            );
        }

        return is_array($response) ? $response : ['data' => $response];
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function callFor(mixed $row): array
    {
        if (str_starts_with($this->operation, 'call:')) {
            return [substr($this->operation, 5), is_array($row) && array_is_list($row) ? $row : [$row]];
        }

        return match ($this->operation) {
            'create' => ['create', [$this->assertArray($row)]],
            'update' => $this->updateCall($this->assertArray($row)),
            'delete' => ['delete', [is_array($row) ? ($row['id'] ?? throw new InvalidArgumentException('A delete row needs an id.')) : $row]],
        };
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function updateCall(array $row): array
    {
        $id = $row['id'] ?? null;

        if (! is_string($id) || $id === '') {
            throw new InvalidArgumentException('An update row needs an id.');
        }

        unset($row['id']);

        return ['update', [$id, $row]];
    }

    private function assertArray(mixed $row): array
    {
        if (! is_array($row)) {
            throw new InvalidArgumentException('Each row must be an array, '.get_debug_type($row).' given.');
        }

        return $row;
    }

    private function report(BulkResult $result, int|string $index): void
    {
        if ($this->progress === null) {
            return;
        }

        $counts = $result->counts();

        ($this->progress)(new BulkProgress(
            processed: $counts['succeeded'] + $counts['failed'] + $counts['skipped'],
            total: count($this->rows),
            succeeded: $counts['succeeded'],
            failed: $counts['failed'],
            skipped: $counts['skipped'],
            lastIndex: $index,
        ));
    }
}
