<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Projects;

use McoreServices\TeamleaderSDK\Resources\Projects\ExternalParties;
use McoreServices\TeamleaderSDK\Resources\Projects\Groups;
use McoreServices\TeamleaderSDK\Resources\Projects\LegacyMilestones;
use McoreServices\TeamleaderSDK\Resources\Projects\LegacyProjects;
use McoreServices\TeamleaderSDK\Resources\Projects\Materials;
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectLines;
use McoreServices\TeamleaderSDK\Resources\Projects\Projects;
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectsV2Resource;
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectTasks;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of the Projects category, compared
 * against the specification fixture.
 */
#[Group('spec-contract')]
final class ProjectsSpecContractTest extends TestCase
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
            'projects.create' => ['projects-v2/projects.create', Projects::CREATE_FIELDS],
            'projects.update' => ['projects-v2/projects.update', [...Projects::UPDATE_FIELDS, 'id']],
            'projectGroups.create' => ['projects-v2/projectGroups.create', Groups::CREATE_FIELDS],
            'projectGroups.update' => ['projects-v2/projectGroups.update', [...Groups::UPDATE_FIELDS, 'id']],
            'tasks.create' => ['projects-v2/tasks.create', ProjectTasks::CREATE_FIELDS],
            'tasks.update' => ['projects-v2/tasks.update', [...ProjectTasks::UPDATE_FIELDS, 'id']],
            'materials.create' => ['projects-v2/materials.create', Materials::CREATE_FIELDS],
            'materials.update' => ['projects-v2/materials.update', [...Materials::UPDATE_FIELDS, 'id']],
            'externalParties.addToProject' => ['projects-v2/externalParties.addToProject', ExternalParties::ADD_FIELDS],
            'externalParties.update' => ['projects-v2/externalParties.update', [...ExternalParties::UPDATE_FIELDS, 'id']],
            'legacy projects.create' => ['projects.create', LegacyProjects::CREATE_FIELDS],
            'legacy projects.update' => ['projects.update', [...LegacyProjects::UPDATE_FIELDS, 'id']],
            'milestones.create' => ['milestones.create', LegacyMilestones::CREATE_FIELDS],
            'milestones.update' => ['milestones.update', [...LegacyMilestones::UPDATE_FIELDS, 'id']],
        ];
    }

    #[DataProvider('writeFields')]
    public function test_write_fields_match_the_specification(string $endpoint, array $fields): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['properties'];

        sort($declared);
        sort($fields);

        $this->assertSame($declared, $fields, "{$endpoint} fields have drifted from the specification.");
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function enums(): array
    {
        return [
            'projects billing_method' => ['projects-v2/projects.create', 'billing_method', Projects::BILLING_METHODS],
            'projects update billing_method' => ['projects-v2/projects.update', 'billing_method.value', Projects::BILLING_METHODS],
            'projects update_strategy' => ['projects-v2/projects.update', 'billing_method.update_strategy', ProjectsV2Resource::UPDATE_STRATEGIES],
            'projects color' => ['projects-v2/projects.create', 'color', ProjectsV2Resource::COLORS],
            'projects currency' => ['projects-v2/projects.create', 'fixed_price.currency', ProjectsV2Resource::CURRENCIES],
            'projects time unit' => ['projects-v2/projects.create', 'time_budget.unit', ProjectsV2Resource::TIME_UNITS],
            'projects customers type' => ['projects-v2/projects.create', 'customers[].type', Projects::CUSTOMER_TYPES],
            'projects assignees type' => ['projects-v2/projects.create', 'assignees[].type', ProjectsV2Resource::ASSIGNEE_TYPES],
            'projects filter status' => ['projects-v2/projects.list', 'filter.status', Projects::STATUSES],
            'projects delete' => ['projects-v2/projects.delete', 'delete_strategy', Projects::DELETE_STRATEGIES],
            'projects close' => ['projects-v2/projects.close', 'closing_strategy', Projects::CLOSING_STRATEGIES],
            'projects assign' => ['projects-v2/projects.assign', 'assignee.type', ProjectsV2Resource::ASSIGNEE_TYPES],
            'groups billing_method' => ['projects-v2/projectGroups.create', 'billing_method', Groups::BILLING_METHODS],
            'groups update billing_method' => ['projects-v2/projectGroups.update', 'billing_method.value', Groups::BILLING_METHODS],
            'groups color' => ['projects-v2/projectGroups.create', 'color', ProjectsV2Resource::COLORS],
            'groups delete' => ['projects-v2/projectGroups.delete', 'delete_strategy', Groups::DELETE_STRATEGIES],
            'tasks billing_method' => ['projects-v2/tasks.create', 'billing_method', ProjectTasks::BILLING_METHODS],
            'tasks status' => ['projects-v2/tasks.update', 'status', ProjectTasks::STATUSES],
            'tasks time unit' => ['projects-v2/tasks.create', 'time_estimated.unit', ProjectsV2Resource::TIME_UNITS],
            'tasks delete' => ['projects-v2/tasks.delete', 'delete_strategy', ProjectTasks::DELETE_STRATEGIES],
            'materials billing_method' => ['projects-v2/materials.create', 'billing_method', Materials::BILLING_METHODS],
            'materials status' => ['projects-v2/materials.update', 'status', Materials::STATUSES],
            'materials currency' => ['projects-v2/materials.create', 'unit_price.currency', ProjectsV2Resource::CURRENCIES],
            'lines types' => ['projects-v2/projectLines.list', 'filter.types[]', ProjectLines::LINE_TYPES],
            'lines assignees' => ['projects-v2/projectLines.list', 'filter.assignees[].type', ProjectLines::ASSIGNEE_TYPES],
            'external party customer' => ['projects-v2/externalParties.addToProject', 'customer.type', ExternalParties::CUSTOMER_TYPES],
            'legacy status' => ['projects.update', 'status', LegacyProjects::STATUSES],
            'legacy filter status' => ['projects.list', 'filter.status', LegacyProjects::STATUSES],
            'legacy roles' => ['projects.create', 'participants[].role', LegacyProjects::ROLES],
            'legacy customer' => ['projects.update', 'customer.type', LegacyProjects::CUSTOMER_TYPES],
            'legacy currency' => ['projects.update', 'budget.currency', LegacyProjects::CURRENCIES],
            'milestones status' => ['milestones.list', 'filter.status', LegacyMilestones::STATUSES],
            'milestones currency' => ['milestones.create', 'price.currency', LegacyMilestones::CURRENCIES],
        ];
    }

    #[DataProvider('enums')]
    public function test_enum_matches_the_specification(string $endpoint, string $path, array $constant): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "{$endpoint} no longer declares an enum at {$path}.");

        sort($declared);
        sort($constant);

        $this->assertSame($declared, $constant, "{$path} on {$endpoint} has drifted from the specification.");
    }

    /**
     * milestones.create's billing_method is split over two oneOf branches:
     * "With budget" enumerates non_invoiceable and time_and_materials, "With
     * price" types it as a string with example fixed_price. The fixture holds
     * the enumerated part only.
     */
    public function test_milestone_billing_methods_cover_the_specification(): void
    {
        $declared = self::spec()->endpoint('milestones.create')['request']['enums']['billing_method'];

        $this->assertSame([], array_values(array_diff($declared, LegacyMilestones::BILLING_METHODS)));
        $this->assertSame(['fixed_price'], array_values(array_diff(LegacyMilestones::BILLING_METHODS, $declared)));
    }

    public function test_project_includes_match(): void
    {
        $list = self::spec()->endpoint('projects-v2/projects.list')['request'];
        $info = self::spec()->endpoint('projects-v2/projects.info')['request'];

        $this->assertEqualsCanonicalizing($list['includes'], Projects::INCLUDES);
        $this->assertEqualsCanonicalizing($info['includes'], Projects::INFO_INCLUDES);
        $this->assertTrue($list['declares_pagination_meta']);
    }
}
