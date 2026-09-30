<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Tasks;

use McoreServices\TeamleaderSDK\Resources\Tasks\Tasks;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of tasks.*, compared against the
 * specification fixture.
 */
#[Group('spec-contract')]
final class TasksSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    public function test_create_fields_match(): void
    {
        $declared = self::spec()->endpoint('tasks.create')['request'];

        $this->assertEqualsCanonicalizing($declared['properties'], Tasks::CREATE_FIELDS);
        $this->assertEqualsCanonicalizing($declared['required'], Tasks::REQUIRED_ON_CREATE);
    }

    public function test_update_fields_match(): void
    {
        $declared = self::spec()->endpoint('tasks.update')['request']['properties'];

        $this->assertEqualsCanonicalizing($declared, [...Tasks::UPDATE_FIELDS, 'id']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function enums(): array
    {
        return [
            'create assignee' => ['tasks.create', 'assignee.type', Tasks::ASSIGNEE_TYPES],
            'create customer' => ['tasks.create', 'customer.type', Tasks::CUSTOMER_TYPES],
            'create duration' => ['tasks.create', 'estimated_duration.unit', Tasks::DURATION_UNITS],
            'update assignee' => ['tasks.update', 'assignee.type', Tasks::ASSIGNEE_TYPES],
            'update duration' => ['tasks.update', 'estimated_duration.unit', Tasks::DURATION_UNITS],
            'list customer' => ['tasks.list', 'filter.customer.type', Tasks::CUSTOMER_TYPES],
        ];
    }

    #[DataProvider('enums')]
    public function test_enum_matches_the_specification(string $endpoint, string $path, array $constant): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "{$endpoint} no longer declares an enum at {$path}.");
        $this->assertEqualsCanonicalizing($declared, $constant, "{$path} on {$endpoint} has drifted from the specification.");
    }
}
