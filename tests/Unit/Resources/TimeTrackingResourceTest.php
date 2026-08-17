<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\TimeTracking\TimeTracking;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Regression tests for the v2.1.2 TimeTracking fixes.
 *
 * applyFilters() had a `default:` branch that forwarded any unknown filter key
 * to the API. Teamleader ignores keys it does not recognise and answers 200 with
 * the full dataset, so a mistyped or unsupported filter returned every entry in
 * the account. The reported case returned 32,985 entries for a query the caller
 * believed was scoped to two days.
 *
 * Alongside it: applySorting() accepted any field although the API declares only
 * `starts_on`, and the subject-type whitelist did not distinguish between the
 * types accepted for writing and the narrower set accepted as a list filter.
 */
final class TimeTrackingResourceTest extends ResourceTestCase
{
    private TimeTracking $timeTracking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeTracking = $this->resource(TimeTracking::class);
    }

    // ---------------------------------------------------------------------
    // Unknown filters
    // ---------------------------------------------------------------------

    public function test_updated_since_is_rejected(): void
    {
        // The reported case. Not a filter timeTracking.list supports.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid filter key 'updated_since'");

        try {
            $this->timeTracking->list(['updated_since' => '2026-03-31T00:00:00+00:00']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_invoiced_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid filter key 'invoiced'");

        $this->timeTracking->list(['invoiced' => false]);
    }

    public function test_invoiceable_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->timeTracking->list(['invoiceable' => true]);
    }

    public function test_the_error_names_the_supported_filters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('started_after');

        $this->timeTracking->list(['banana' => 'yes']);
    }

    public function test_a_null_filter_value_is_treated_as_unset_not_as_an_error(): void
    {
        // Skipped before validation, so ['user_id' => null] means "not filtering
        // by user" rather than "invalid filter".
        $this->timeTracking->list(['user_id' => null]);

        $this->assertLastBodyMissing('filter');
    }

    // ---------------------------------------------------------------------
    // Supported filters
    // ---------------------------------------------------------------------

    public function test_supported_filters_reach_the_request(): void
    {
        $this->timeTracking->list([
            'user_id' => 'user-uuid',
            'started_after' => '2026-01-01T00:00:00+00:00',
            'started_before' => '2026-01-31T00:00:00+00:00',
        ]);

        $this->assertLastEndpoint('timeTracking.list');
        $this->assertLastBodyHas('filter.user_id', 'user-uuid');
        $this->assertLastBodyHas('filter.started_after', '2026-01-01T00:00:00+00:00');
    }

    public function test_between_dates_builds_a_started_range(): void
    {
        $this->timeTracking->betweenDates('2026-01-01', '2026-01-31');

        $this->assertLastBodyHas('filter.started_after', '2026-01-01');
        $this->assertLastBodyHas('filter.started_before', '2026-01-31');
    }

    public function test_ids_string_is_coerced_to_an_array(): void
    {
        $this->timeTracking->list(['ids' => 'entry-uuid']);

        $this->assertLastBodyHas('filter.ids', ['entry-uuid']);
    }

    // ---------------------------------------------------------------------
    // Subject types — filter vs write
    // ---------------------------------------------------------------------

    public function test_for_subject_accepts_a_valid_filter_type(): void
    {
        $this->timeTracking->forSubject('company-uuid', 'company');

        $this->assertLastBodyHas('filter.subject.type', 'company');
        $this->assertLastBodyHas('filter.subject.id', 'company-uuid');
    }

    public function test_nextgen_task_is_rejected_as_a_filter_and_says_why(): void
    {
        // nextgenTask is valid for writes but the API offers no such filter value.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('relates_to');

        try {
            $this->timeTracking->forSubject('task-uuid', 'nextgenTask');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_nextgen_task_is_still_accepted_when_creating_an_entry(): void
    {
        $this->timeTracking->create([
            'started_at' => '2026-01-15T10:00:00+00:00',
            'duration' => 3600,
            'subject' => ['id' => '2175597d-484e-4a1c-a781-cbc3d9f893ba', 'type' => 'nextgenTask'],
        ]);

        $this->assertLastEndpoint('timeTracking.add');
        $this->assertLastBodyHas('subject.type', 'nextgenTask');
    }

    public function test_an_unknown_subject_type_in_a_raw_filter_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->timeTracking->list([
            'subject' => ['id' => 'some-uuid', 'type' => 'banana'],
        ]);
    }

    public function test_a_subject_filter_without_a_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('both id and type');

        $this->timeTracking->list(['subject' => ['id' => 'some-uuid']]);
    }

    public function test_for_subject_types_validates_every_entry(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->timeTracking->forSubjectTypes(['company', 'banana']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_for_subject_types_sends_the_array(): void
    {
        $this->timeTracking->forSubjectTypes(['company', 'contact']);

        $this->assertLastBodyHas('filter.subject_types', ['company', 'contact']);
    }

    // ---------------------------------------------------------------------
    // relates_to
    // ---------------------------------------------------------------------

    public function test_related_to_builds_the_filter(): void
    {
        $this->timeTracking->relatedTo('project-uuid', 'nextgenProject');

        $this->assertLastBodyHas('filter.relates_to.type', 'nextgenProject');
        $this->assertLastBodyHas('filter.relates_to.id', 'project-uuid');
    }

    public function test_an_invalid_relates_to_type_in_a_raw_filter_is_rejected(): void
    {
        // relatedTo() already validated; the raw filter path did not.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid relates_to type');

        $this->timeTracking->list([
            'relates_to' => ['id' => 'some-uuid', 'type' => 'company'],
        ]);
    }

    // ---------------------------------------------------------------------
    // Sorting
    // ---------------------------------------------------------------------

    public function test_sorting_by_starts_on_is_accepted(): void
    {
        $this->timeTracking->list([], ['sort' => 'starts_on', 'sort_order' => 'desc']);

        $this->assertSame(
            [['field' => 'starts_on', 'order' => 'desc']],
            $this->lastBody()['sort'] ?? null
        );
    }

    public function test_an_unsupported_sort_field_is_rejected(): void
    {
        // The API declares only starts_on and silently ignores anything else.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field');

        try {
            $this->timeTracking->list([], ['sort' => 'created_at']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_an_invalid_sort_order_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort order');

        $this->timeTracking->list([], ['sort' => 'starts_on', 'sort_order' => 'sideways']);
    }

    public function test_no_sort_key_is_sent_when_no_sort_is_requested(): void
    {
        $this->timeTracking->list();

        $this->assertLastBodyMissing('sort');
    }

    // ---------------------------------------------------------------------
    // Sideloading and pagination
    // ---------------------------------------------------------------------

    public function test_includes_are_sent_as_includes_plural(): void
    {
        $this->timeTracking->list([], ['include' => 'materials']);

        $this->assertLastBodyHas('includes', 'materials');
        $this->assertLastBodyMissing('include');
    }

    public function test_fluent_includes_reach_the_request(): void
    {
        $this->timeTracking->withMaterials()->list();

        $this->assertLastBodyHas('includes', 'materials');
    }

    public function test_pagination_options_are_forwarded(): void
    {
        $this->timeTracking->list([], ['page_size' => 100, 'page_number' => 3]);

        $this->assertLastBodyHas('page.size', 100);
        $this->assertLastBodyHas('page.number', 3);
    }
}
