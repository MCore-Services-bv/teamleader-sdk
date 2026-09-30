<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\TimeTracking;

use McoreServices\TeamleaderSDK\Resources\TimeTracking\Timers;
use McoreServices\TeamleaderSDK\Resources\TimeTracking\TimeTracking;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of the TimeTracking category, compared
 * against the specification fixture.
 */
#[Group('spec-contract')]
final class TimeTrackingSpecContractTest extends TestCase
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
            'timeTracking.add' => ['timeTracking.add', TimeTracking::ADD_FIELDS],
            'timeTracking.update' => ['timeTracking.update', [...TimeTracking::UPDATE_FIELDS, 'id']],
            'timers.start' => ['timers.start', Timers::WRITE_FIELDS],
            'timers.update' => ['timers.update', Timers::WRITE_FIELDS],
        ];
    }

    #[DataProvider('writeFields')]
    public function test_write_fields_match_the_specification(string $endpoint, array $fields): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint($endpoint)['request']['properties'], $fields);
    }

    public function test_update_requires_duration(): void
    {
        $this->assertEqualsCanonicalizing(['duration', 'id'], self::spec()->endpoint('timeTracking.update')['request']['required']);
    }

    public function test_timers_require_nothing(): void
    {
        $this->assertSame([], self::spec()->endpoint('timers.start')['request']['required']);
    }

    public function test_includes_match(): void
    {
        foreach (['timeTracking.list', 'timeTracking.info'] as $endpoint) {
            $this->assertEqualsCanonicalizing(self::spec()->endpoint($endpoint)['request']['includes'], TimeTracking::INCLUDES);
        }
    }

    public function test_enums_match(): void
    {
        $list = self::spec()->endpoint('timeTracking.list')['request']['enums'];

        $this->assertEqualsCanonicalizing($list['filter.relates_to.type'], TimeTracking::RELATES_TO_TYPES);
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('timers.start')['request']['enums']['subject.type'], Timers::SUBJECT_TYPES);
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('timers.update')['request']['enums']['subject.type'], Timers::SUBJECT_TYPES);
    }
}
