<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Products\UnitOfMeasure;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Regression tests for the v2.1.2 UnitOfMeasure fix.
 *
 * list() declared the same signature as every other resource —
 * list(array $filters = [], array $options = []) — and ignored both arguments.
 * The API takes no request body at all, so every call returned the complete
 * list regardless of what was requested, with no exception, warning or log line.
 *
 * The reported failure mode: a sync layer paging through every entity and
 * soft-deleting anything the enumeration did not mention. With sixteen units it
 * appeared to work, because page 1 came back shorter than the page size and the
 * pager concluded it had reached the end. That was a coincidence. Past the page
 * size, page 2 returns page 1 again and the loop never terminates.
 */
final class UnitOfMeasureResourceTest extends ResourceTestCase
{
    private UnitOfMeasure $units;

    protected function setUp(): void
    {
        parent::setUp();

        $this->units = $this->resource(UnitOfMeasure::class);
    }

    // ---------------------------------------------------------------------
    // The guard
    // ---------------------------------------------------------------------

    public function test_a_bare_list_call_sends_an_empty_body(): void
    {
        $this->units->list();

        $this->assertLastEndpoint('unitsOfMeasure.list');
        $this->assertLastBody([]);
    }

    public function test_filters_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not support filtering');

        try {
            $this->units->list(['ids' => ['unit-uuid']]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_pagination_is_rejected(): void
    {
        // The exact call from the bug report: page 2 previously returned page 1.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not support pagination');

        try {
            $this->units->list([], ['page_size' => 5, 'page_number' => 2]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_sorting_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not support sorting');

        $this->units->list([], ['sort' => 'name']);
    }

    public function test_the_error_names_the_offending_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('page_number');

        $this->units->list([], ['page_number' => 2]);
    }

    public function test_the_error_names_the_endpoint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unitsOfMeasure.list');

        $this->units->list(['anything' => true]);
    }

    // ---------------------------------------------------------------------
    // The capability flags now match the behaviour
    // ---------------------------------------------------------------------

    public function test_capabilities_report_no_pagination_filtering_or_sorting(): void
    {
        $capabilities = $this->units->getCapabilities();

        $this->assertFalse($capabilities['supports_pagination']);
        $this->assertFalse($capabilities['supports_filtering']);
        $this->assertFalse($capabilities['supports_sorting']);
    }

    public function test_documentation_says_the_endpoint_is_not_paginated(): void
    {
        $pagination = $this->units->getDocumentation()['pagination'];

        $this->assertFalse($pagination['supported']);
        $this->assertStringContainsString('not paginated', $pagination['note']);
    }

    // ---------------------------------------------------------------------
    // info() has no endpoint
    // ---------------------------------------------------------------------

    public function test_info_throws_and_points_at_find_by_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('findById()');

        try {
            $this->units->info('unit-uuid');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    // ---------------------------------------------------------------------
    // Convenience methods still work — they all go through a bare list()
    // ---------------------------------------------------------------------

    public function test_find_by_name_matches_case_insensitively(): void
    {
        $this->api->queueListResponse([
            ['id' => 'unit-1', 'name' => 'Piece'],
            ['id' => 'unit-2', 'name' => 'Hour'],
        ]);

        $unit = $this->units->findByName('  piece  ');

        $this->assertSame('unit-1', $unit['id']);
    }

    public function test_find_by_name_returns_null_when_absent(): void
    {
        $this->api->queueListResponse([['id' => 'unit-1', 'name' => 'Piece']]);

        $this->assertNull($this->units->findByName('parsec'));
    }

    public function test_find_by_id_resolves_a_single_unit(): void
    {
        $this->api->queueListResponse([
            ['id' => 'unit-1', 'name' => 'Piece'],
            ['id' => 'unit-2', 'name' => 'Hour'],
        ]);

        $this->assertSame('Hour', $this->units->findById('unit-2')['name']);
    }

    public function test_as_options_builds_an_id_to_name_map(): void
    {
        $this->api->queueListResponse([
            ['id' => 'unit-1', 'name' => 'Piece'],
            ['id' => 'unit-2', 'name' => 'Hour'],
        ]);

        $this->assertSame(
            ['unit-1' => 'Piece', 'unit-2' => 'Hour'],
            $this->units->asOptions()
        );
    }

    public function test_count_returns_the_number_of_units(): void
    {
        $this->api->queueListResponse([
            ['id' => 'unit-1', 'name' => 'Piece'],
            ['id' => 'unit-2', 'name' => 'Hour'],
        ]);

        $this->assertSame(2, $this->units->count());
    }

    public function test_exists_reflects_whether_the_unit_is_present(): void
    {
        $this->api->queueResponses([
            ['data' => [['id' => 'unit-1', 'name' => 'Piece']], 'headers' => []],
            ['data' => [['id' => 'unit-1', 'name' => 'Piece']], 'headers' => []],
        ]);

        $this->assertTrue($this->units->exists('Piece'));
        $this->assertFalse($this->units->exists('Parsec'));
    }

    public function test_convenience_methods_send_no_arguments(): void
    {
        // They route through list(), so any argument would now throw. This is
        // the assertion that catches a future edit adding pagination to them.
        $this->api->queueListResponse([['id' => 'unit-1', 'name' => 'Piece']]);

        $this->units->asOptions();

        $this->assertLastBody([]);
    }
}
