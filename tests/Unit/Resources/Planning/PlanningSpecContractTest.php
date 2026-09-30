<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Planning;

use McoreServices\TeamleaderSDK\Resources\Planning\PlannableItems;
use McoreServices\TeamleaderSDK\Resources\Planning\Reservations;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Field lists and enums of the Planning category, compared against the
 * specification fixture.
 */
#[Group('spec-contract')]
final class PlanningSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    public function test_reservation_write_fields_match(): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('reservations.create')['request']['properties'], Reservations::CREATE_FIELDS);
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('reservations.update')['request']['properties'], [...Reservations::UPDATE_FIELDS, 'id']);
        $this->assertEqualsCanonicalizing(
            self::spec()->endpoint('reservations.create')['request']['enums']['duration.unit'],
            Reservations::DURATION_UNITS
        );
    }

    public function test_plannable_item_enums_match(): void
    {
        $enums = self::spec()->endpoint('plannableItems.list')['request']['enums'];

        $this->assertEqualsCanonicalizing($enums['filter.types[]'], PlannableItems::TYPES);
        $this->assertEqualsCanonicalizing($enums['filter.completion_statuses[]'], PlannableItems::COMPLETION_STATUSES);
        $this->assertEqualsCanonicalizing($enums['filter.planned_time_statuses[]'], PlannableItems::PLANNED_TIME_STATUSES);
    }

    public function test_reservation_source_types_match(): void
    {
        $enums = self::spec()->endpoint('reservations.list')['request']['enums'];

        $this->assertEqualsCanonicalizing($enums['filter.source_types[]'], Reservations::SOURCE_TYPES);
        $this->assertEqualsCanonicalizing($enums['filter.sources[].type'], Reservations::SOURCE_TYPES);
    }
}
