<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Tasks;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Tasks\Tasks;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for tasks.*, against specification 1.221.0.
 */
final class TasksPayloadTest extends ResourceTestCase
{
    private function tasks(): Tasks
    {
        /** @var Tasks */
        return $this->resource(Tasks::class);
    }

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

    // -- list ------------------------------------------------------------------

    /**
     * Before v2.2.10 a field-name sort was a TypeError and sort_order was ignored.
     */
    public function test_sort_by_field_name(): void
    {
        $this->tasks()->list([], ['sort' => 'due_on', 'sort_order' => 'desc']);

        $this->assertLastEndpoint('tasks.list');
        $this->assertLastBody(['sort' => [['field' => 'due_on', 'order' => 'desc']]]);
    }

    /**
     * `name` was advertised until v2.2.10; the API sorts on created_at and due_on only.
     */
    public function test_unknown_sort_field_throws(): void
    {
        $this->expectRejected(fn () => $this->tasks()->list([], ['sort' => 'name']), 'created_at, due_on');
    }

    public function test_unassigned_sends_a_null_user_id(): void
    {
        $this->tasks()->unassigned(['page_size' => 10]);

        $this->assertLastBody(['filter' => ['user_id' => null], 'page' => ['size' => 10, 'number' => 1]]);
    }

    public function test_helpers_merge_extra_filters(): void
    {
        $this->tasks()->forUser('user-uuid', ['filters' => ['completed' => false]]);

        $this->assertLastBody(['filter' => ['user_id' => 'user-uuid', 'completed' => false]]);
    }

    public function test_unknown_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->tasks()->list(['status' => 'open']), 'status');
    }

    public function test_boolean_filters_must_be_booleans(): void
    {
        $this->expectRejected(fn () => $this->tasks()->list(['completed' => 'yes']), 'completed');
    }

    public function test_customer_filter_type_is_checked(): void
    {
        $this->expectRejected(
            fn () => $this->tasks()->list(['customer' => ['type' => 'user', 'id' => 'x']]),
            'filter.customer.type'
        );
    }

    public function test_due_dates_must_exist(): void
    {
        $this->expectRejected(fn () => $this->tasks()->dueBetween('2026-02-30', '2026-03-31'), 'due_from');
    }

    public function test_unknown_option_throws(): void
    {
        $this->expectRejected(fn () => $this->tasks()->list([], ['include' => 'custom_fields']), 'include');
    }

    // -- write -----------------------------------------------------------------

    public function test_create(): void
    {
        $this->tasks()->create([
            'title' => 'Call back',
            'due_on' => '2026-01-01',
            'work_type_id' => 'work-type-uuid',
            'estimated_duration' => ['value' => 30, 'unit' => 'min'],
        ]);

        $this->assertLastEndpoint('tasks.create');
        $this->assertLastBodyHas('estimated_duration.unit', 'min');
    }

    public function test_create_requires_a_work_type(): void
    {
        $this->expectRejected(fn () => $this->tasks()->create(['title' => 'T', 'due_on' => '2026-01-01']), 'work_type_id');
    }

    /**
     * `priority` is returned by tasks.info but is not a write field.
     */
    public function test_unknown_fields_throw(): void
    {
        $this->expectRejected(fn () => $this->tasks()->update('task-uuid', ['priority' => 'A']), 'tasks.update does not accept: priority');
    }

    public function test_duration_is_in_minutes_only(): void
    {
        $this->expectRejected(fn () => $this->tasks()->update('task-uuid', [
            'estimated_duration' => ['value' => 1, 'unit' => 'hours'],
        ]), 'estimated_duration.unit');
    }

    public function test_update_can_unassign_and_unlink(): void
    {
        $this->tasks()->update('task-uuid', ['assignee' => null, 'deal_id' => null]);

        $this->assertLastBody(['assignee' => null, 'deal_id' => null, 'id' => 'task-uuid']);
    }

    public function test_assignee_type_is_checked(): void
    {
        $this->expectRejected(fn () => $this->tasks()->update('task-uuid', [
            'assignee' => ['type' => 'company', 'id' => 'x'],
        ]), 'assignee.type');
    }

    public function test_info_takes_no_includes(): void
    {
        $this->expectRejected(fn () => $this->tasks()->info('task-uuid', 'custom_fields'));
    }

    // -- schedule --------------------------------------------------------------

    /**
     * Z was rejected before v2.2.10.
     */
    public function test_schedule_accepts_utc_z(): void
    {
        $this->tasks()->schedule('task-uuid', '2026-01-01T09:00:00Z', '2026-01-01T10:00:00Z');

        $this->assertLastEndpoint('tasks.schedule');
        $this->assertLastBody(['id' => 'task-uuid', 'starts_at' => '2026-01-01T09:00:00Z', 'ends_at' => '2026-01-01T10:00:00Z']);
    }

    public function test_schedule_end_must_follow_start(): void
    {
        $this->expectRejected(
            fn () => $this->tasks()->schedule('task-uuid', '2026-01-01T10:00:00+01:00', '2026-01-01T09:00:00+01:00'),
            'ends_at'
        );
    }
}
