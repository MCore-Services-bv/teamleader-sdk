<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Calendar;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Calendar\ActivityTypes;
use McoreServices\TeamleaderSDK\Resources\Calendar\CallOutcomes;
use McoreServices\TeamleaderSDK\Resources\Calendar\Calls;
use McoreServices\TeamleaderSDK\Resources\Calendar\Events;
use McoreServices\TeamleaderSDK\Resources\Calendar\Meetings;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the Calendar category, against specification 1.221.0.
 */
final class CalendarPayloadTest extends ResourceTestCase
{
    private const UUID = '2175597d-484e-4a1c-a781-cbc3d9f893ba';

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

    // -- events ----------------------------------------------------------------

    /**
     * Before v2.2.12 a field-name sort was a TypeError and sort_order was ignored.
     */
    public function test_event_sort_by_field_name(): void
    {
        $this->resource(Events::class)->list([], ['sort' => 'starts_at', 'sort_order' => 'desc']);

        $this->assertLastEndpoint('events.list');
        $this->assertLastBody(['sort' => [['field' => 'starts_at', 'order' => 'desc']]]);
    }

    public function test_event_sort_field_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Events::class)->list([], ['sort' => 'title']), 'starts_at');
    }

    public function test_event_unknown_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(Events::class)->list(['status' => 'done']), 'status');
    }

    /**
     * events.list filters on contact attendees only; `user` was accepted until v2.2.12.
     */
    public function test_event_attendee_filter_takes_contacts_only(): void
    {
        $this->expectRejected(fn () => $this->resource(Events::class)->forAttendee('user', 'user-uuid'), 'filter.attendee.type');
    }

    public function test_event_attendee_filter(): void
    {
        $this->resource(Events::class)->forAttendee('contact', 'contact-uuid');

        $this->assertLastBody(['filter' => ['attendee' => ['type' => 'contact', 'id' => 'contact-uuid']]]);
    }

    public function test_event_create_accepts_utc_z(): void
    {
        $this->resource(Events::class)->create([
            'title' => 'Review', 'activity_type_id' => 'type-uuid',
            'starts_at' => '2026-01-01T09:00:00Z', 'ends_at' => '2026-01-01T10:00:00Z',
        ]);

        $this->assertLastEndpoint('events.create');
    }

    public function test_event_end_must_follow_start(): void
    {
        $this->expectRejected(fn () => $this->resource(Events::class)->update('event-uuid', [
            'starts_at' => '2026-01-01T10:00:00Z', 'ends_at' => '2026-01-01T09:00:00Z',
        ]), 'ends_at');
    }

    /**
     * The activity type is fixed once an event exists.
     */
    public function test_event_update_rejects_activity_type(): void
    {
        $this->expectRejected(fn () => $this->resource(Events::class)->update('event-uuid', ['activity_type_id' => 'x']), 'activity_type_id');
    }

    public function test_event_link_type_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Events::class)->update('event-uuid', [
            'links' => [['type' => 'ticket', 'id' => 't']],
        ]), 'links[0].type');
    }

    // -- meetings --------------------------------------------------------------

    /**
     * `customer` is optional; the SDK required it until v2.2.12.
     */
    public function test_meeting_schedules_without_a_customer(): void
    {
        $this->resource(Meetings::class)->schedule([
            'title' => 'Kick-off',
            'starts_at' => '2026-01-01T09:00:00+01:00',
            'ends_at' => '2026-01-01T10:00:00+01:00',
            'attendees' => [['type' => 'user', 'id' => 'user-uuid']],
        ]);

        $this->assertLastEndpoint('meetings.schedule');
        $this->assertLastBodyMissing('customer');
    }

    public function test_meeting_needs_a_user_attendee(): void
    {
        $this->expectRejected(fn () => $this->resource(Meetings::class)->update('meeting-uuid', [
            'attendees' => [['type' => 'contact', 'id' => 'contact-uuid']],
        ]), 'user attendee');
    }

    public function test_meeting_group_requires_project(): void
    {
        $this->expectRejected(fn () => $this->resource(Meetings::class)->update('meeting-uuid', ['group_id' => 'g']), 'project_id');
    }

    public function test_meeting_project_and_milestone_are_exclusive(): void
    {
        $this->expectRejected(fn () => $this->resource(Meetings::class)->update('meeting-uuid', [
            'project_id' => 'p', 'milestone_id' => 'm',
        ]), 'mutually exclusive');
    }

    public function test_meeting_update_rejects_work_order(): void
    {
        $this->expectRejected(fn () => $this->resource(Meetings::class)->update('meeting-uuid', ['work_order_id' => 'w']), 'work_order_id');
    }

    /**
     * The sort was passed through unchecked and the `includes` key ignored until v2.2.12.
     */
    public function test_meeting_list_sorts_and_includes(): void
    {
        $this->resource(Meetings::class)->list(['employee_id' => 'e'], ['sort' => 'scheduled_at', 'includes' => 'tracked_time']);

        $this->assertLastBody([
            'filter' => ['employee_id' => 'e'],
            'sort' => [['field' => 'scheduled_at', 'order' => 'asc']],
            'includes' => 'tracked_time',
        ]);
    }

    public function test_meeting_unknown_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(Meetings::class)->list(['customer_id' => 'c']), 'customer_id');
    }

    public function test_meeting_include_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Meetings::class)->info('meeting-uuid', 'custom_fields'), 'tracked_time');
    }

    public function test_meeting_fluent_include(): void
    {
        $this->resource(Meetings::class)->withEstimatedTime()->info('meeting-uuid');

        $this->assertLastBody(['id' => 'meeting-uuid', 'includes' => 'estimated_time']);
    }

    // -- calls -----------------------------------------------------------------

    public function test_call_list_requests_the_pagination_meta(): void
    {
        $this->resource(Calls::class)->betweenDates('2026-01-01', '2026-01-31', ['page_size' => 50]);

        $this->assertLastEndpoint('calls.list');
        $this->assertLastBody([
            'filter' => ['scheduled_after' => '2026-01-01', 'scheduled_before' => '2026-01-31'],
            'page' => ['size' => 50, 'number' => 1],
            'includes' => 'pagination',
        ]);
    }

    public function test_call_unknown_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(Calls::class)->list(['assignee_id' => 'x']), 'assignee_id');
    }

    public function test_call_assignee_is_a_user(): void
    {
        $this->expectRejected(fn () => $this->resource(Calls::class)->create([
            'participant' => ['customer' => ['type' => 'company', 'id' => 'c']],
            'due_at' => '2026-01-01T09:00:00+01:00',
            'assignee' => ['type' => 'team', 'id' => 't'],
        ]), 'assignee.type');
    }

    public function test_call_update_rejects_unknown_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(Calls::class)->update(self::UUID, ['title' => 'x']), 'calls.update does not accept: title');
    }

    // -- call outcomes / activity types ---------------------------------------

    /**
     * callOutcomes.list takes no filter; `ids` was sent and ignored until v2.2.12.
     */
    public function test_call_outcomes_reject_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(CallOutcomes::class)->list(['ids' => ['x']]), 'no filters');
    }

    public function test_call_outcomes_by_ids_filters_client_side(): void
    {
        $this->api->queueResponse(['data' => [
            ['id' => 'a', 'name' => 'Reached'],
            ['id' => 'b', 'name' => 'Voicemail'],
        ]]);

        $result = $this->resource(CallOutcomes::class)->byIds(['b']);

        $this->assertLastEndpoint('callOutcomes.list');
        $this->assertLastBodyMissing('filter');
        $this->assertSame([['id' => 'b', 'name' => 'Voicemail']], $result['data']);
    }

    public function test_activity_types_wrap_a_string_id(): void
    {
        $this->resource(ActivityTypes::class)->list(['ids' => 'type-uuid']);

        $this->assertLastBodyHas('filter.ids', ['type-uuid']);
    }

    public function test_activity_types_reject_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(ActivityTypes::class)->list(['name' => 'Meeting']), 'name');
    }
}
