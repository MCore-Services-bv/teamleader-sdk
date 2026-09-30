<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Support;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Support\Cursor;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * v3.0 (F3): the end conditions and guards of the pager, against a fake
 * endpoint. No Laravel and no resource — just pages.
 */
final class CursorTest extends TestCase
{
    /** @var list<array{int, int}> page, size of every fetch */
    private array $fetches = [];

    public function test_it_stops_on_a_short_page(): void
    {
        $cursor = $this->cursorOver(5, pageSize: 2);

        $this->assertSame(['r1', 'r2', 'r3', 'r4', 'r5'], $this->ids($cursor));
        $this->assertSame([[1, 2], [2, 2], [3, 2]], $this->fetches);
        $this->assertSame(3, $cursor->lastPage());
    }

    public function test_a_full_last_page_costs_one_empty_request_without_a_total(): void
    {
        $cursor = $this->cursorOver(4, pageSize: 2);

        $this->assertSame(['r1', 'r2', 'r3', 'r4'], $this->ids($cursor));
        $this->assertCount(3, $this->fetches);
    }

    public function test_a_known_total_saves_the_empty_request(): void
    {
        $cursor = $this->cursorOver(4, pageSize: 2, reportTotal: true);

        $this->assertSame(['r1', 'r2', 'r3', 'r4'], $this->ids($cursor));
        $this->assertCount(2, $this->fetches);
        $this->assertSame(4, $cursor->total());
    }

    public function test_an_empty_first_page_yields_nothing(): void
    {
        $cursor = $this->cursorOver(0, pageSize: 2);

        $this->assertSame([], $this->ids($cursor));
        $this->assertCount(1, $this->fetches);
    }

    public function test_nothing_is_fetched_until_iterated_and_only_what_is_consumed(): void
    {
        $cursor = $this->cursorOver(10, pageSize: 2);
        $this->assertSame([], $this->fetches);

        foreach ($cursor as $record) {
            break;
        }

        $this->assertCount(1, $this->fetches);
    }

    public function test_total_is_null_without_meta_and_fetches_the_first_page_once(): void
    {
        $cursor = $this->cursorOver(3, pageSize: 2);

        $this->assertNull($cursor->total());
        $this->assertNull($cursor->total());
        $this->assertCount(1, $this->fetches);
    }

    public function test_from_page_resumes(): void
    {
        $cursor = $this->cursorOver(5, pageSize: 2)->fromPage(2);

        $this->assertSame(['r3', 'r4', 'r5'], $this->ids($cursor));
        $this->assertSame([[2, 2], [3, 2]], $this->fetches);
    }

    public function test_an_error_response_throws_instead_of_ending_early(): void
    {
        $cursor = new Cursor(fn (int $page) => $page === 1
            ? ['data' => [['id' => 'a'], ['id' => 'b']]]
            : ['error' => true, 'status_code' => 500, 'message' => 'Server error'], 2, 'things.list');

        $seen = [];

        try {
            foreach ($cursor as $record) {
                $seen[] = $record['id'];
            }
            $this->fail('An error page must throw.');
        } catch (TeamleaderException $e) {
            $this->assertStringContainsString('things.list failed on page 2: Server error', $e->getMessage());
            $this->assertSame(500, $e->getStatusCode());
        }

        $this->assertSame(['a', 'b'], $seen);
    }

    public function test_an_endpoint_ignoring_the_page_size_throws(): void
    {
        $cursor = new Cursor(fn () => ['data' => [['id' => 'a'], ['id' => 'b'], ['id' => 'c']]], 2, 'things.list');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('not honouring the page parameters');

        iterator_to_array($cursor, false);
    }

    public function test_an_endpoint_ignoring_the_page_number_throws(): void
    {
        $cursor = new Cursor(fn () => ['data' => [['id' => 'a'], ['id' => 'b']]], 2, 'things.list');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('same first record on pages 1 and 2');

        iterator_to_array($cursor, false);
    }

    public function test_page_size_and_start_page_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Cursor(fn () => [], 0, 'things.list');
    }

    // -- helpers ----------------------------------------------------------------

    private function cursorOver(int $records, int $pageSize, bool $reportTotal = false): Cursor
    {
        $all = array_map(fn (int $i) => ['id' => "r{$i}"], range(1, max(1, $records)));
        $all = $records === 0 ? [] : $all;

        return new Cursor(function (int $page, int $size) use ($all, $reportTotal) {
            $this->fetches[] = [$page, $size];

            $response = ['data' => array_slice($all, ($page - 1) * $size, $size)];

            if ($reportTotal) {
                $response['meta'] = ['page' => ['size' => $size, 'number' => $page], 'matches' => count($all)];
            }

            return $response;
        }, $pageSize, 'things.list');
    }

    /**
     * @return list<string>
     */
    private function ids(Cursor $cursor): array
    {
        return array_map(fn (array $record) => $record['id'], iterator_to_array($cursor, false));
    }
}
