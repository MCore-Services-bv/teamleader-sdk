<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Planning;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Planning\PlannableItems;
use McoreServices\TeamleaderSDK\Resources\Planning\Reservations;
use McoreServices\TeamleaderSDK\Resources\Planning\UserAvailability;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the Planning category, against specification 1.221.0.
 */
final class PlanningPayloadTest extends ResourceTestCase
{
    private function expectRejected(callable $call, ?string $message = null): void
    {
        $this->expectException(InvalidArgumentException::class);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }

        try {
            $call();
        } finally {
            $this->assertNoRequestMade();
        }
    }

    // -- plannable items -------------------------------------------------------

    /**
     * plannableItems.list has no status filter; the SDK sent one until v2.2.16.
     */
    public function test_status_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(PlannableItems::class)->list(['status' => ['active']]), 'no status filter');
    }

    public function test_types_filter_and_field_order_sort(): void
    {
        $this->resource(PlannableItems::class)->ofTypes(['task', 'meeting'], [], ['sort' => 'end_date:desc']);

        $this->assertLastBody([
            'filter' => ['types' => ['task', 'meeting']],
            'sort' => [['field' => 'end_date', 'order' => 'desc']],
        ]);
    }

    public function test_type_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(PlannableItems::class)->ofTypes(['todo']), 'filter.types[0]');
    }

    /**
     * A string sort was not validated before v2.2.16.
     */
    public function test_sort_field_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(PlannableItems::class)->list([], ['sort' => 'title']), 'title');
    }

    public function test_unassigned_items(): void
    {
        $this->resource(PlannableItems::class)->unassigned(['project_ids' => 'project-uuid']);

        $this->assertLastBody(['filter' => ['assignees' => [null], 'project_ids' => ['project-uuid']]]);
    }

    public function test_assignee_type_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(PlannableItems::class)->list([
            'assignees' => [['type' => 'contact', 'id' => 'c']],
        ]), 'assignees[0].type');
    }

    // -- reservations ----------------------------------------------------------

    public function test_reservation_filters_added_in_the_spec(): void
    {
        $this->resource(Reservations::class)->list([
            'term' => 'kick-off',
            'project_ids' => 'project-uuid',
            'work_type_ids' => ['work-type-uuid'],
            'sources' => [['type' => 'task', 'id' => 'task-uuid']],
        ]);

        $this->assertLastBody(['filter' => [
            'term' => 'kick-off',
            'project_ids' => ['project-uuid'],
            'work_type_ids' => ['work-type-uuid'],
            'sources' => [['type' => 'task', 'id' => 'task-uuid']],
        ]]);
    }

    public function test_reservation_unknown_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(Reservations::class)->list(['user_id' => 'u']), 'user_id');
    }

    public function test_reservation_source_type_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Reservations::class)->list([
            'sources' => [['type' => 'invoice', 'id' => 'x']],
        ]), 'filter.sources[0].type');
    }

    public function test_reservation_plannable_item_cannot_change(): void
    {
        $this->expectRejected(
            fn () => $this->resource(Reservations::class)->update('reservation-uuid', ['plannable_item_id' => 'p']),
            'reservations.update does not accept: plannable_item_id'
        );
    }

    public function test_reservation_date_must_exist(): void
    {
        $this->expectRejected(fn () => $this->resource(Reservations::class)->create([
            'plannable_item_id' => 'p',
            'date' => '2026-02-30',
            'duration' => ['unit' => 'minutes', 'value' => 60],
            'assignee' => ['type' => 'user', 'id' => 'u'],
        ]), 'date');
    }

    // -- user availability -----------------------------------------------------

    public function test_availability_page_shorthand(): void
    {
        $this->resource(UserAvailability::class)->dailyForUser('user-uuid', '2026-01-01', '2026-01-31', ['page_size' => 50]);

        $this->assertLastEndpoint('userAvailability.daily');
        $this->assertLastBodyHas('page', ['size' => 50, 'number' => 1]);
    }

    public function test_availability_unknown_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(UserAvailability::class)->daily([
            'period' => ['start_date' => '2026-01-01', 'end_date' => '2026-01-02'],
            'filter' => ['user_ids' => ['u']],
        ]), 'user_ids');
    }
}
