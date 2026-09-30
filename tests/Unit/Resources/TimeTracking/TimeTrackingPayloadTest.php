<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\TimeTracking;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\TimeTracking\Timers;
use McoreServices\TeamleaderSDK\Resources\TimeTracking\TimeTracking;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for timeTracking.* and timers.*, against specification
 * 1.221.0. The v2.1.2 filter and sort regressions live in
 * TimeTrackingResourceTest.
 */
final class TimeTrackingPayloadTest extends ResourceTestCase
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

    // -- includes --------------------------------------------------------------

    /**
     * The `includes` option key was ignored before v2.2.11; only `include` was read.
     */
    public function test_list_reads_the_includes_option_key(): void
    {
        $this->resource(TimeTracking::class)->list([], ['includes' => 'relates_to']);

        $this->assertLastBodyHas('includes', 'relates_to');
    }

    public function test_unknown_include_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->list([], ['include' => 'user']), 'materials, relates_to');
    }

    public function test_info_include_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->info('entry-uuid', 'custom_fields'));
    }

    public function test_unknown_list_option_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->list([], ['sort_by' => 'starts_on']), 'sort_by');
    }

    // -- filters ---------------------------------------------------------------

    /**
     * "For tracked time without a subject type, provide null."
     */
    public function test_subject_types_accept_null_for_entries_without_a_subject(): void
    {
        $this->resource(TimeTracking::class)->forSubjectTypes([null, 'ticket']);

        $this->assertLastBodyHas('filter.subject_types', [null, 'ticket']);
    }

    // -- add -------------------------------------------------------------------

    public function test_add_with_started_on_and_duration(): void
    {
        $this->resource(TimeTracking::class)->create(['started_on' => '2026-01-15', 'duration' => 600]);

        $this->assertLastEndpoint('timeTracking.add');
        $this->assertLastBody(['started_on' => '2026-01-15', 'duration' => 600]);
    }

    public function test_add_rejects_unknown_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->create([
            'started_on' => '2026-01-15', 'duration' => 600, 'invoiced' => true,
        ]), 'timeTracking.add does not accept: invoiced');
    }

    public function test_add_end_must_follow_start(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->create([
            'started_at' => '2026-01-15T10:00:00Z', 'ended_at' => '2026-01-15T09:00:00Z',
        ]), 'ended_at');
    }

    public function test_add_rejects_a_datetime_without_timezone(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->create([
            'started_at' => '2026-01-15 10:00:00', 'duration' => 600,
        ]), 'started_at');
    }

    // -- update ----------------------------------------------------------------

    public function test_update_requires_duration_and_a_start(): void
    {
        $this->expectRejected(
            fn () => $this->resource(TimeTracking::class)->update('entry-uuid', ['description' => 'Call']),
            'duration'
        );
    }

    public function test_update_takes_exactly_one_start(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->update('entry-uuid', [
            'duration' => 60, 'started_at' => '2026-01-15T10:00:00+01:00', 'started_on' => '2026-01-15',
        ]), 'exactly one');
    }

    public function test_update_can_clear_subject_and_description(): void
    {
        $this->resource(TimeTracking::class)->update('entry-uuid', [
            'started_at' => '2026-01-15T10:00:00+01:00',
            'duration' => 60,
            'subject' => null,
            'description' => null,
        ]);

        $this->assertLastEndpoint('timeTracking.update');
        $this->assertLastBodyHas('subject');
        $this->assertNull($this->lastBody()['subject']);
    }

    public function test_update_rejects_ended_at(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->update('entry-uuid', [
            'started_at' => '2026-01-15T10:00:00+01:00', 'duration' => 60, 'ended_at' => '2026-01-15T11:00:00+01:00',
        ]), 'ended_at');
    }

    public function test_resume_checks_started_at(): void
    {
        $this->expectRejected(fn () => $this->resource(TimeTracking::class)->resume('entry-uuid', 'tomorrow'), 'started_at');
    }

    // -- timers ----------------------------------------------------------------

    /**
     * timers.start requires nothing; the SDK required a subject and a work type until v2.2.11.
     */
    public function test_timer_starts_without_subject_or_work_type(): void
    {
        $this->resource(Timers::class)->start([]);

        $this->assertLastEndpoint('timers.start');
    }

    public function test_timer_rejects_unknown_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(Timers::class)->start(['user_id' => self::UUID]), 'user_id');
    }

    /**
     * The usage example called update(), which did not exist until v2.2.11.
     */
    public function test_timer_update_is_an_alias_for_update_current(): void
    {
        $this->resource(Timers::class)->update(['description' => 'Standup', 'subject' => null]);

        $this->assertLastEndpoint('timers.update');
        $this->assertLastBody(['description' => 'Standup', 'subject' => null]);
    }

    public function test_timer_subject_type_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Timers::class)->start([
            'subject' => ['type' => 'nextgenTask', 'id' => self::UUID],
        ]), 'nextgenTask');
    }
}
