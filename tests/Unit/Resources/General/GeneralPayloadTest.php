<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\General;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\General\ClosingDays;
use McoreServices\TeamleaderSDK\Resources\General\DayOffTypes;
use McoreServices\TeamleaderSDK\Resources\General\DaysOff;
use McoreServices\TeamleaderSDK\Resources\General\Departments;
use McoreServices\TeamleaderSDK\Resources\General\DocumentTemplates;
use McoreServices\TeamleaderSDK\Resources\General\EmailTracking;
use McoreServices\TeamleaderSDK\Resources\General\Notes;
use McoreServices\TeamleaderSDK\Resources\General\Teams;
use McoreServices\TeamleaderSDK\Resources\General\Users;
use McoreServices\TeamleaderSDK\Resources\General\UserSchedules;
use McoreServices\TeamleaderSDK\Resources\General\WorkTypes;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the General category, against specification 1.221.0.
 */
final class GeneralPayloadTest extends ResourceTestCase
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

    // -- sorting: departments, teams, users ------------------------------------

    /**
     * The sort was passed through unchecked on all three until v2.2.14. A
     * field => order map is accepted everywhere normaliseSort() is used.
     */
    public function test_department_sort_accepts_a_field_order_map(): void
    {
        $this->resource(Departments::class)->list(['status' => 'active'], ['sort' => ['name' => 'desc']]);

        $this->assertLastBody([
            'filter' => ['status' => ['active']],
            'sort' => [['field' => 'name', 'order' => 'desc']],
        ]);
    }

    public function test_department_status_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Departments::class)->list(['status' => 'deleted']), 'filter.status[0]');
    }

    public function test_team_sort_field_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Teams::class)->list([], ['sort' => 'created_at']), 'created_at');
    }

    public function test_user_sort_by_field_name(): void
    {
        $this->resource(Users::class)->list(['ids' => 'user-uuid'], ['sort' => 'last_name']);

        $this->assertLastBody([
            'filter' => ['ids' => ['user-uuid']],
            'sort' => [['field' => 'last_name', 'order' => 'asc']],
        ]);
    }

    public function test_unknown_filters_throw(): void
    {
        $this->expectRejected(fn () => $this->resource(Teams::class)->list(['name' => 'Design']), 'name');
    }

    // -- users -----------------------------------------------------------------

    public function test_user_info_include_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Users::class)->info('user-uuid', 'teams'), 'external_rate');
    }

    public function test_user_fluent_include(): void
    {
        $this->resource(Users::class)->withExternalRate()->info('user-uuid');

        $this->assertLastBody(['id' => 'user-uuid', 'includes' => 'external_rate']);
    }

    public function test_user_days_off_requests_the_pagination_meta(): void
    {
        $this->resource(Users::class)->listDaysOff('user-uuid', ['starts_after' => '2026-01-01']);

        $this->assertLastBody(['id' => 'user-uuid', 'filter' => ['starts_after' => '2026-01-01'], 'includes' => 'pagination']);
    }

    // -- work types ------------------------------------------------------------

    /**
     * workTypes.list takes no sort; the SDK sent one (malformed) until v2.2.14.
     */
    public function test_work_types_reject_sort(): void
    {
        $this->expectRejected(fn () => $this->resource(WorkTypes::class)->list([], ['sort' => 'name']), 'sortedByName');
    }

    public function test_work_types_sorted_by_name_sorts_client_side(): void
    {
        $this->api->queueResponse(['data' => [['id' => '2', 'name' => 'Support'], ['id' => '1', 'name' => 'Design']]]);

        $result = $this->resource(WorkTypes::class)->sortedByName();

        $this->assertLastBodyMissing('sort');
        $this->assertSame(['Design', 'Support'], array_column($result['data'], 'name'));
    }

    // -- document templates ----------------------------------------------------

    public function test_document_type_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(DocumentTemplates::class)->byType('dept-uuid', 'creditnote'), 'filter.document_type');
    }

    // -- email tracking / notes ------------------------------------------------

    /**
     * Sent with an empty filter until v2.2.14; the API requires a subject.
     */
    public function test_email_tracking_requires_a_subject(): void
    {
        $this->expectRejected(fn () => $this->resource(EmailTracking::class)->list(), 'subject');
    }

    /**
     * The dotted keys were advertised but ignored until v2.2.14.
     */
    public function test_email_tracking_dotted_subject(): void
    {
        $this->resource(EmailTracking::class)->list(['subject.type' => 'deal', 'subject.id' => 'deal-uuid']);

        $this->assertLastBody(['filter' => ['subject' => ['type' => 'deal', 'id' => 'deal-uuid']]]);
    }

    public function test_email_tracking_rejects_unknown_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(EmailTracking::class)->create([
            'subject' => ['type' => 'contact', 'id' => self::UUID], 'content' => 'Hi', 'body' => 'Hi',
        ]), 'body');
    }

    /**
     * Notes can be listed on a legacy project but not created on one.
     */
    public function test_notes_cannot_be_created_on_a_legacy_project(): void
    {
        $this->expectRejected(fn () => $this->resource(Notes::class)->createForSubject('project', self::UUID, 'Text'), 'project');
    }

    public function test_notes_can_be_listed_on_a_legacy_project(): void
    {
        $this->resource(Notes::class)->forSubject('project', self::UUID);

        $this->assertLastBody(['filter' => ['subject' => ['type' => 'project', 'id' => self::UUID]]]);
    }

    public function test_note_update_changes_content_only(): void
    {
        $this->expectRejected(fn () => $this->resource(Notes::class)->update('note-uuid', ['notify' => []]), 'notes.update does not accept: notify');
    }

    // -- days off --------------------------------------------------------------

    public function test_full_days_off(): void
    {
        $this->resource(DaysOff::class)->importFullDays(self::UUID, self::UUID, ['2026-01-05', '2026-01-06']);

        $this->assertLastEndpoint('daysOff.import');
        $this->assertLastBodyHas('days', [['date' => '2026-01-05'], ['date' => '2026-01-06']]);
    }

    public function test_full_days_off_are_chunked_at_one_hundred(): void
    {
        $dates = array_map(fn ($i) => date('Y-m-d', strtotime("2026-01-01 +{$i} days")), range(0, 149));

        $this->resource(DaysOff::class)->importFullDays(self::UUID, self::UUID, $dates);

        $this->assertRequestCount(2);
        $this->assertCount(50, $this->lastBody()['days']);
    }

    public function test_timed_day_off_accepts_utc_z(): void
    {
        $this->resource(DaysOff::class)->importSingleDay(self::UUID, self::UUID, '2026-01-05T08:00:00Z', '2026-01-05T12:00:00Z');

        $this->assertLastEndpoint('daysOff.import');
    }

    public function test_day_off_import_is_capped(): void
    {
        $this->expectRejected(
            fn () => $this->resource(DaysOff::class)->bulkImport(self::UUID, self::UUID, array_fill(0, 101, ['date' => '2026-01-05'])),
            'at most 100'
        );
    }

    public function test_day_off_type_single_day_validity(): void
    {
        $this->resource(DayOffTypes::class)->createWithValidity('Leave', '#00B2B2', '2026-01-01', '2026-01-01');

        $this->assertLastBodyHas('date_validity', ['from' => '2026-01-01', 'until' => '2026-01-01']);
    }

    public function test_day_off_type_rejects_unknown_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(DayOffTypes::class)->update('type-uuid', ['label' => 'x']), 'label');
    }

    // -- closing days ----------------------------------------------------------

    public function test_belgian_holidays(): void
    {
        $holidays = $this->resource(ClosingDays::class)->getCommonHolidays(2026);

        $this->assertCount(10, $holidays);
        $this->assertSame('2026-04-06', $holidays['Easter Monday']);
        $this->assertSame('2026-05-14', $holidays['Ascension Day']);
        $this->assertSame('2026-05-25', $holidays['Whit Monday']);
        $this->assertSame('2026-07-21', $holidays['National Day']);
        $this->assertArrayNotHasKey('Boxing Day', $holidays);
    }

    public function test_holidays_for_another_country_throw(): void
    {
        $this->expectRejected(fn () => $this->resource(ClosingDays::class)->getCommonHolidays(2026, 'NL'), 'Belgium');
    }

    public function test_closing_day_add_takes_day_only(): void
    {
        $this->expectRejected(fn () => $this->resource(ClosingDays::class)->create(['day' => '2026-01-01', 'name' => 'x']), 'name');
    }

    // -- user schedules --------------------------------------------------------

    public function test_user_schedules_request_the_pagination_meta(): void
    {
        $this->resource(UserSchedules::class)->forUser('user-uuid', '2026-01-01', '2026-01-07');

        $this->assertLastBodyHas('includes', 'pagination');
    }

    public function test_user_schedules_reject_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(UserSchedules::class)->list([
            'user_ids' => ['user-uuid'], 'from' => '2026-01-01', 'until' => '2026-01-07', 'team_id' => 't',
        ]), 'team_id');
    }
}
