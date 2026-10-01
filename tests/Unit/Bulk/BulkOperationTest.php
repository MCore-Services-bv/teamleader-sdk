<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Bulk;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Bulk\BulkProgress;
use McoreServices\TeamleaderSDK\Bulk\BulkValidationException;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * v3.0 (§5.2): bulk writes — validated first, sent one by one, every row
 * accounted for.
 */
final class BulkOperationTest extends ResourceTestCase
{
    private const COMPANY = '0b9a4c8e-1f2d-4e3a-9b5c-6d7e8f901234';

    // -- validation before sending ---------------------------------------------

    public function test_invalid_rows_stop_everything_before_the_first_request(): void
    {
        $rows = [$this->deal('A'), ['title' => 'no lead'], $this->deal('C'), ['lead' => []]];

        try {
            $this->api->bulk()->create('deals', $rows)->run();
            $this->fail('Expected BulkValidationException.');
        } catch (BulkValidationException $e) {
            $this->assertSame([1, 3], array_keys($e->failures));
            $this->assertStringContainsString('2 of 4 rows are invalid for deals; nothing was sent', $e->getMessage());
            $this->assertStringContainsString('row 1:', $e->getMessage());
            $this->assertTrue($e->failures[1]->beforeSending);
        }

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_valid_rows_are_sent_in_order(): void
    {
        $result = $this->api->bulk()->create('deals', [$this->deal('A'), $this->deal('B')])->run();

        $this->assertSame([0, 1], array_keys($result->succeeded()));
        $this->assertTrue($result->isComplete());
        $this->assertSame(['deals.create', 'deals.create'], $this->api->endpoints());
        $this->assertSame('B', $this->api->lastBody()['title']);
    }

    public function test_without_validate_first_an_invalid_row_fails_on_its_own(): void
    {
        $result = $this->api->bulk()->create('deals', [$this->deal('A'), ['title' => 'no lead'], $this->deal('C')])
            ->validateFirst(false)
            ->continueOnError()
            ->run();

        $this->assertSame([0, 2], array_keys($result->succeeded()));
        $this->assertSame([1], array_keys($result->failed()));
        $this->assertTrue($result->failed()[1]->beforeSending);
        $this->assertSame(2, $this->api->callCount());
    }

    // -- API refusals -----------------------------------------------------------

    public function test_the_first_refusal_stops_the_rest_by_default(): void
    {
        $this->api->queueResponses([['data' => ['id' => 'd1']], $this->refusal(), ['data' => ['id' => 'd3']]]);

        $result = $this->api->bulk()->create('deals', [$this->deal('A'), $this->deal('B'), $this->deal('C')])->run();

        $this->assertSame([0], array_keys($result->succeeded()));
        $this->assertSame([1], array_keys($result->failed()));
        $this->assertSame(422, $result->failed()[1]->statusCode());
        $this->assertSame([2], array_keys($result->skipped()));
        $this->assertSame(2, $this->api->callCount());
    }

    public function test_continue_on_error_sends_every_row(): void
    {
        $this->api->queueResponses([['data' => ['id' => 'd1']], $this->refusal(), ['data' => ['id' => 'd3']]]);

        $result = $this->api->bulk()->create('deals', [$this->deal('A'), $this->deal('B'), $this->deal('C')])
            ->continueOnError()
            ->run();

        $this->assertSame([0, 2], array_keys($result->succeeded()));
        $this->assertSame([1], array_keys($result->failed()));
        $this->assertSame([], $result->skipped());
    }

    // -- duplicates and resuming ------------------------------------------------

    public function test_unique_by_sends_the_first_of_each_key(): void
    {
        $rows = ['r1' => $this->deal('Acme'), 'r2' => $this->deal('Globex'), 'r3' => $this->deal('Acme')];

        $result = $this->api->bulk()->create('deals', $rows)->uniqueBy(fn (array $row) => $row['title'])->run();

        $this->assertSame(['r1', 'r2'], array_keys($result->succeeded()));
        $this->assertSame(['r3' => 'Duplicate of row r1 by uniqueBy().'], $result->skipped());
    }

    public function test_resume_skips_what_succeeded_before(): void
    {
        $this->api->queueResponses([['data' => ['id' => 'd1']], $this->refusal()]);
        $rows = [$this->deal('A'), $this->deal('B'), $this->deal('C')];

        $first = $this->api->bulk()->create('deals', $rows)->run();

        $this->api->reset();
        $second = $this->api->bulk()->create('deals', $rows)->resumeFrom(json_decode(json_encode($first->succeededIndices())))->run();

        $this->assertSame([1, 2], array_keys($second->succeeded()));
        $this->assertSame([0], array_keys($second->skipped()));
        $this->assertSame(2, $this->api->callCount());
    }

    // -- operations -------------------------------------------------------------

    public function test_update_takes_the_id_from_the_row(): void
    {
        $this->api->bulk()->update('deals', [['id' => 'deal-1', 'title' => 'Renamed']])->run();

        $this->assertSame('deals.update', $this->api->lastEndpoint());
        $this->assertSame(['title' => 'Renamed', 'id' => 'deal-1'], $this->api->lastBody());
    }

    public function test_an_update_row_without_an_id_is_invalid(): void
    {
        $this->expectException(BulkValidationException::class);
        $this->expectExceptionMessage('An update row needs an id');

        $this->api->bulk()->update('deals', [['title' => 'no id']])->run();
    }

    public function test_delete_takes_ids(): void
    {
        $result = $this->api->bulk()->delete('deals', ['deal-1', ['id' => 'deal-2']])->run();

        $this->assertCount(2, $result->succeeded());
        $this->assertSame(['id' => 'deal-2'], $this->api->lastBody());
    }

    public function test_call_runs_any_method_per_row(): void
    {
        $this->api->bulk()->call('deals', 'win', ['deal-1', 'deal-2'])->run();
        $this->api->bulk()->call('deals', 'lose', [['deal-3', 'reason-1']])->run();

        $this->assertSame(['deals.win', 'deals.win', 'deals.lose'], $this->api->endpoints());
        $this->assertSame(['id' => 'deal-3', 'reason_id' => 'reason-1'], $this->api->lastBody());
    }

    public function test_call_refuses_a_method_the_resource_does_not_have(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('deals() has no method winn()');

        $this->api->bulk()->call('deals', 'winn', ['deal-1']);
    }

    // -- dry run, progress, settings --------------------------------------------

    public function test_a_dry_run_validates_and_records_the_bodies_but_sends_nothing(): void
    {
        $result = $this->api->bulk()->create('deals', [$this->deal('A'), ['title' => 'no lead']])->dryRun();

        $this->assertTrue($result->dryRun);
        $this->assertSame(0, $this->api->callCount());
        $this->assertSame([0], array_keys($result->succeeded()));
        $this->assertSame([1], array_keys($result->failed()));
        $this->assertSame('deals.create', $result->requests()[0][0]['endpoint']);
        $this->assertSame('A', $result->requests()[0][0]['body']['title']);
        $this->assertSame([], $result->requests()[1]);
    }

    public function test_progress_is_reported_after_every_row(): void
    {
        $seen = [];

        $this->api->bulk()->create('deals', [$this->deal('A'), $this->deal('B')])
            ->onProgress(function (BulkProgress $p) use (&$seen) {
                $seen[] = [$p->processed, $p->total, $p->percentage()];
            })
            ->run();

        $this->assertSame([[1, 2, 50.0], [2, 2, 100.0]], $seen);
    }

    public function test_throw_exceptions_and_max_wait_are_restored_afterwards(): void
    {
        $this->api->throwExceptions(false);
        config(['teamleader.rate_limiting.max_wait_ms' => 5000]);

        $this->api->bulk()->create('deals', [$this->deal('A')])->run();

        $this->assertFalse($this->api->getErrorHandler()->getThrowExceptions());
        $this->assertSame(5000, config('teamleader.rate_limiting.max_wait_ms'));
    }

    // -- helpers ----------------------------------------------------------------

    private function deal(string $title): array
    {
        return ['lead' => ['customer' => ['type' => 'company', 'id' => self::COMPANY]], 'title' => $title];
    }

    private function refusal(): array
    {
        return ['error' => true, 'status_code' => 422, 'message' => 'Validation failed', 'errors' => ['Validation failed']];
    }
}
