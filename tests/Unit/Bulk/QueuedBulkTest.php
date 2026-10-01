<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Bulk;

use Carbon\CarbonImmutable;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use LogicException;
use McoreServices\TeamleaderSDK\Bulk\BulkValidationException;
use McoreServices\TeamleaderSDK\Bulk\Jobs\RunBulkChunk;
use McoreServices\TeamleaderSDK\Bulk\QueuedResults;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * v3.0 (§5.3): queued bulk — chunks as a job batch, rate limits released
 * rather than waited out, and a retry that never resends a finished row.
 */
final class QueuedBulkTest extends ResourceTestCase
{
    private const COMPANY = '0b9a4c8e-1f2d-4e3a-9b5c-6d7e8f901234';

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'database']);
    }

    // -- dispatch ---------------------------------------------------------------

    public function test_rows_are_split_into_chunks_of_one_batch(): void
    {
        Bus::fake();

        $this->api->bulk()->create('deals', $this->deals(5))->dispatch(chunk: 2);

        Bus::assertBatched(function (PendingBatch $batch) {
            $chunks = $batch->jobs->all();

            return count($chunks) === 3
                && $chunks[0] instanceof RunBulkChunk
                && array_keys($chunks[0]->rows) === [0, 1]
                && array_keys($chunks[2]->rows) === [4]
                && $chunks[0]->resource === 'deals';
        });

        $this->assertSame(0, $this->api->callCount(), 'Nothing is sent while dispatching');
    }

    public function test_invalid_rows_throw_before_anything_is_queued(): void
    {
        Bus::fake();

        try {
            $this->api->bulk()->create('deals', [...$this->deals(2), ['title' => 'no lead']])->dispatch();
            $this->fail('Expected BulkValidationException.');
        } catch (BulkValidationException) {
        }

        Bus::assertNothingBatched();
    }

    public function test_the_sync_driver_is_refused(): void
    {
        config(['queue.default' => 'sync']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('sync driver');

        $this->api->bulk()->create('deals', $this->deals(1))->dispatch();
    }

    // -- the job ----------------------------------------------------------------

    public function test_a_chunk_records_its_rows(): void
    {
        $runId = $this->startRun(['0']);

        $this->job($runId, '0', $this->deals(2))->handle($this->manager());

        $result = (new QueuedResults($runId))->collect();
        $this->assertSame([0, 1], array_keys($result->succeeded()));
        $this->assertSame(2, $this->api->callCount());
    }

    public function test_a_rate_limit_releases_the_job_and_the_retry_resends_nothing(): void
    {
        $runId = $this->startRun(['0']);
        $this->api->queueResponses([
            ['data' => ['id' => 'deal-1']],
            ['error' => true, 'status_code' => 429, 'message' => 'Too many requests'],
        ]);

        $job = $this->job($runId, '0', $this->deals(3))->withFakeQueueInteractions();
        $job->handle($this->manager());

        $job->assertReleased();
        $this->assertSame(['deal-1'], array_values(array_filter(array_map(
            fn ($r) => $r['data']['id'], (new QueuedResults($runId))->collect()->succeeded()
        ))));

        // The retry — same payload, as Laravel re-queues it
        $this->api->reset();
        $this->job($runId, '0', $this->deals(3))->handle($this->manager());

        $this->assertSame(2, $this->api->callCount(), 'Only rows 1 and 2; row 0 is not sent again');
        $this->assertSame([0, 1, 2], array_keys((new QueuedResults($runId))->collect()->succeeded()));
    }

    public function test_a_refusal_cancels_the_batch_without_continue_on_error(): void
    {
        $runId = $this->startRun(['0']);
        $this->api->queueResponse(['error' => true, 'status_code' => 422, 'message' => 'Invalid']);

        [$job, $batch] = $this->job($runId, '0', $this->deals(2))->withFakeBatch();
        $job->handle($this->manager());

        $this->assertTrue($batch->cancelled());
        $result = (new QueuedResults($runId))->collect();
        $this->assertSame([0], array_keys($result->failed()));
        $this->assertSame([1], array_keys($result->skipped()));
    }

    public function test_with_continue_on_error_the_batch_carries_on(): void
    {
        $runId = $this->startRun(['0']);
        $this->api->queueResponse(['error' => true, 'status_code' => 422, 'message' => 'Invalid']);

        [$job, $batch] = $this->job($runId, '0', $this->deals(2), continueOnError: true)->withFakeBatch();
        $job->handle($this->manager());

        $this->assertFalse($batch->cancelled());
        $this->assertSame([1], array_keys((new QueuedResults($runId))->collect()->succeeded()));
    }

    public function test_a_cancelled_batch_skips_its_remaining_chunks(): void
    {
        $runId = $this->startRun(['0']);

        [$job, $batch] = $this->job($runId, '0', $this->deals(2))->withFakeBatch(cancelledAt: CarbonImmutable::now());
        $job->handle($this->manager());

        $this->assertSame(0, $this->api->callCount());
        $this->assertSame([0, 1], array_keys((new QueuedResults($runId))->collect()->skipped()));
    }

    // -- helpers ----------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    private function deals(int $count): array
    {
        return array_map(fn (int $i) => ['lead' => ['customer' => ['type' => 'company', 'id' => self::COMPANY]], 'title' => "Deal {$i}"], range(0, $count - 1));
    }

    /**
     * @param  list<string>  $chunks
     */
    private function startRun(array $chunks): string
    {
        $runId = 'run-'.bin2hex(random_bytes(4));
        (new QueuedResults($runId))->start('default', 'deals', 'create', $chunks);

        return $runId;
    }

    private function job(string $runId, string $chunk, array $rows, bool $continueOnError = false): RunBulkChunk
    {
        return new RunBulkChunk($runId, $chunk, 'default', 'deals', 'create', $rows, $continueOnError);
    }

    /** A manager that hands the job the recording client */
    private function manager(): ConnectionManager
    {
        $api = $this->api;

        return new class($api) extends ConnectionManager
        {
            public function __construct(private readonly TeamleaderSDK $api) {}

            public function connection(?string $name = null): TeamleaderSDK
            {
                return $this->api;
            }
        };
    }
}
