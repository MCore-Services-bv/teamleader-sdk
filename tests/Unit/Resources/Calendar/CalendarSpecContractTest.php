<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Calendar;

use McoreServices\TeamleaderSDK\Resources\Calendar\Calls;
use McoreServices\TeamleaderSDK\Resources\Calendar\Events;
use McoreServices\TeamleaderSDK\Resources\Calendar\Meetings;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of the Calendar category, compared against
 * the specification fixture.
 */
#[Group('spec-contract')]
final class CalendarSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function writeFields(): array
    {
        return [
            'events.create' => ['events.create', Events::CREATE_FIELDS],
            'events.update' => ['events.update', [...Events::UPDATE_FIELDS, 'id']],
            'meetings.schedule' => ['meetings.schedule', Meetings::SCHEDULE_FIELDS],
            'meetings.update' => ['meetings.update', [...Meetings::UPDATE_FIELDS, 'id']],
            'meetings.createReport' => ['meetings.createReport', [...Meetings::REPORT_FIELDS, 'id']],
            'calls.add' => ['calls.add', Calls::ADD_FIELDS],
            'calls.update' => ['calls.update', [...Calls::UPDATE_FIELDS, 'id']],
        ];
    }

    #[DataProvider('writeFields')]
    public function test_write_fields_match_the_specification(string $endpoint, array $fields): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint($endpoint)['request']['properties'], $fields);
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function required(): array
    {
        return [
            'events.create' => ['events.create', Events::REQUIRED_ON_CREATE],
            'meetings.schedule' => ['meetings.schedule', Meetings::REQUIRED_ON_SCHEDULE],
            'calls.add' => ['calls.add', Calls::REQUIRED_ON_ADD],
        ];
    }

    #[DataProvider('required')]
    public function test_required_fields_match(string $endpoint, array $fields): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint($endpoint)['request']['required'], $fields);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function enums(): array
    {
        return [
            'events attendees' => ['events.create', 'attendees[].type', Events::ATTENDEE_TYPES],
            'events links' => ['events.create', 'links[].type', Events::LINK_TYPES],
            'events filter attendee' => ['events.list', 'filter.attendee.type', Events::FILTER_ATTENDEE_TYPES],
            'events filter link' => ['events.list', 'filter.link.type', Events::LINK_TYPES],
            'meetings attendees' => ['meetings.schedule', 'attendees[].type', Meetings::ATTENDEE_TYPES],
            'meetings customer' => ['meetings.update', 'customer.type', Meetings::CUSTOMER_TYPES],
            'meetings report target' => ['meetings.createReport', 'attach_to.type', Meetings::REPORT_TARGET_TYPES],
            'calls customer' => ['calls.add', 'participant.customer.type', Calls::CUSTOMER_TYPES],
            'calls assignee' => ['calls.update', 'assignee.type', Calls::ASSIGNEE_TYPES],
        ];
    }

    #[DataProvider('enums')]
    public function test_enum_matches_the_specification(string $endpoint, string $path, array $constant): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "{$endpoint} no longer declares an enum at {$path}.");
        $this->assertEqualsCanonicalizing($declared, $constant);
    }

    public function test_meeting_includes_match(): void
    {
        $request = self::spec()->endpoint('meetings.list')['request'];

        $this->assertEqualsCanonicalizing(
            array_values(array_unique([...$request['includes'], ...$request['response_includes']])),
            Meetings::INCLUDES
        );
    }
}
