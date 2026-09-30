<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Projects\ExternalParties;
use McoreServices\TeamleaderSDK\Resources\Projects\Groups;
use McoreServices\TeamleaderSDK\Resources\Projects\LegacyMilestones;
use McoreServices\TeamleaderSDK\Resources\Projects\LegacyProjects;
use McoreServices\TeamleaderSDK\Resources\Projects\Materials;
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectLines;
use McoreServices\TeamleaderSDK\Resources\Projects\Projects;
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectTasks;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Payload tests for the Projects category, against specification 1.221.0.
 */
final class ProjectsPayloadTest extends ResourceTestCase
{
    /**
     * The four projects-v2 resources that share ProjectsV2Resource.
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function assignable(): array
    {
        return [
            'projects' => [Projects::class, 'projects-v2/projects'],
            'groups' => [Groups::class, 'projects-v2/projectGroups'],
            'tasks' => [ProjectTasks::class, 'projects-v2/tasks'],
            'materials' => [Materials::class, 'projects-v2/materials'],
        ];
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

    // -- shared: assign / unassign ---------------------------------------------

    #[DataProvider('assignable')]
    public function test_assign_sends_the_assignee_object(string $class, string $base): void
    {
        $this->resource($class)->assignUser('record-uuid', 'user-uuid');

        $this->assertLastEndpoint("{$base}.assign");
        $this->assertLastBody(['id' => 'record-uuid', 'assignee' => ['type' => 'user', 'id' => 'user-uuid']]);
    }

    #[DataProvider('assignable')]
    public function test_unassign_team(string $class, string $base): void
    {
        $this->resource($class)->unassignTeam('record-uuid', 'team-uuid');

        $this->assertLastEndpoint("{$base}.unassign");
        $this->assertLastBodyHas('assignee.type', 'team');
    }

    #[DataProvider('assignable')]
    public function test_assignee_type_is_checked(string $class, string $base): void
    {
        $this->expectRejected(fn () => $this->resource($class)->assign('record-uuid', 'company', 'x'), 'assignee.type');
    }

    // -- tasks -------------------------------------------------------------------

    /**
     * Before v2.2.9 any filter was a fatal error: list() called an undefined
     * buildFilters().
     */
    public function test_task_list_with_ids_filter_works(): void
    {
        $this->resource(ProjectTasks::class)->list(['ids' => 'task-uuid'], ['page_size' => 5]);

        $this->assertLastEndpoint('projects-v2/tasks.list');
        $this->assertLastBody(['filter' => ['ids' => ['task-uuid']], 'page' => ['size' => 5, 'number' => 1]]);
    }

    public function test_task_list_rejects_unknown_filters_and_options(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectTasks::class)->list(['project_id' => 'x']), 'project_id');
    }

    public function test_task_list_rejects_sort(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectTasks::class)->list([], ['sort' => 'title']), 'sort');
    }

    public function test_task_create_with_work_type_rate_needs_a_work_type(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectTasks::class)->create([
            'project_id' => 'p', 'title' => 'T', 'billing_method' => 'work_type_rate',
        ]), 'work_type_id');
    }

    /**
     * "Cannot be null" — on update the work type may be left out; the task
     * keeps the one it has. Before v2.2.9 it was required here too.
     */
    public function test_task_update_to_work_type_rate_may_omit_the_work_type(): void
    {
        $this->resource(ProjectTasks::class)->update('task-uuid', ['billing_method' => 'work_type_rate']);

        $this->assertLastBody(['billing_method' => 'work_type_rate', 'id' => 'task-uuid']);
    }

    public function test_task_update_rejects_create_only_fields(): void
    {
        $this->expectRejected(
            fn () => $this->resource(ProjectTasks::class)->update('task-uuid', ['group_id' => 'g']),
            'projects-v2/tasks.update does not accept: group_id'
        );
    }

    public function test_task_money_currency_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectTasks::class)->create([
            'project_id' => 'p', 'title' => 'T', 'custom_rate' => ['amount' => 50, 'currency' => 'AUD'],
        ]), 'custom_rate.currency');
    }

    public function test_task_time_estimated_unit_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectTasks::class)->update('task-uuid', [
            'time_estimated' => ['value' => 2, 'unit' => 'days'],
        ]), 'time_estimated.unit');
    }

    public function test_task_info_takes_no_includes(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectTasks::class)->info('task-uuid', 'custom_fields'));
    }

    public function test_task_delete_strategy_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectTasks::class)->delete('task-uuid', 'delete_everything'));
    }

    // -- materials ---------------------------------------------------------------

    public function test_material_delete(): void
    {
        $this->resource(Materials::class)->delete('material-uuid');

        $this->assertLastEndpoint('projects-v2/materials.delete');
        $this->assertLastBody(['id' => 'material-uuid']);
    }

    public function test_material_duplicate(): void
    {
        $this->resource(Materials::class)->duplicate('material-uuid');

        $this->assertLastEndpoint('projects-v2/materials.duplicate');
        $this->assertLastBody(['origin_id' => 'material-uuid']);
    }

    public function test_material_list_pages(): void
    {
        $this->resource(Materials::class)->list(['ids' => ['a', 'b']], ['page_number' => 2]);

        $this->assertLastBody(['filter' => ['ids' => ['a', 'b']], 'page' => ['size' => 20, 'number' => 2]]);
    }

    public function test_material_list_rejects_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(Materials::class)->list(['project_id' => 'p']), 'project_id');
    }

    public function test_material_status_is_update_only(): void
    {
        $this->expectRejected(fn () => $this->resource(Materials::class)->create([
            'project_id' => 'p', 'title' => 'T', 'status' => 'done',
        ]), 'status');
    }

    public function test_material_money_may_be_cleared(): void
    {
        $this->resource(Materials::class)->update('material-uuid', ['unit_price' => null]);

        $this->assertLastBody(['unit_price' => null, 'id' => 'material-uuid']);
    }

    public function test_material_money_currency_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Materials::class)->update('material-uuid', [
            'unit_cost' => ['amount' => 1, 'currency' => 'eur'],
        ]), 'unit_cost.currency');
    }

    // -- groups --------------------------------------------------------------------

    public function test_group_list_pages_and_wraps_ids(): void
    {
        $this->resource(Groups::class)->list(['ids' => 'group-uuid', 'project_id' => 'p'], ['page_size' => 10]);

        $this->assertLastBody([
            'filter' => ['ids' => ['group-uuid'], 'project_id' => 'p'],
            'page' => ['size' => 10, 'number' => 1],
        ]);
    }

    public function test_group_list_rejects_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(Groups::class)->list(['title' => 'Design']), 'title');
    }

    public function test_group_update_wraps_a_plain_billing_method(): void
    {
        $this->resource(Groups::class)->update('group-uuid', ['billing_method' => 'fixed_price']);

        $this->assertLastBodyHas('billing_method', ['value' => 'fixed_price', 'update_strategy' => 'none']);
    }

    public function test_group_update_strategy_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Groups::class)->update('group-uuid', [
            'billing_method' => ['value' => 'fixed_price', 'update_strategy' => 'all'],
        ]), 'update_strategy');
    }

    public function test_group_color_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Groups::class)->create([
            'project_id' => 'p', 'title' => 'T', 'color' => '#FFFFFF',
        ]), 'color');
    }

    public function test_group_rejects_unknown_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(Groups::class)->update('group-uuid', ['project_id' => 'p']), 'project_id');
    }

    // -- project lines -------------------------------------------------------------

    /**
     * Before v2.2.9 unassigned() sent no assignee filter at all and returned
     * every line.
     */
    public function test_unassigned_sends_a_null_assignee_entry(): void
    {
        $this->resource(ProjectLines::class)->unassigned('project-uuid');

        $this->assertLastEndpoint('projects-v2/projectLines.list');
        $this->assertLastBody(['project_id' => 'project-uuid', 'filter' => ['assignees' => [null]]]);
    }

    public function test_lines_take_flat_filters(): void
    {
        $this->resource(ProjectLines::class)->list([
            'project_id' => 'project-uuid',
            'types' => ['nextgenTask'],
            'assignees' => [null, ['type' => 'team', 'id' => 'team-uuid']],
        ]);

        $this->assertLastBody([
            'project_id' => 'project-uuid',
            'filter' => [
                'types' => ['nextgenTask'],
                'assignees' => [null, ['type' => 'team', 'id' => 'team-uuid']],
            ],
        ]);
    }

    public function test_lines_still_take_the_nested_filter_form(): void
    {
        $this->resource(ProjectLines::class)->list([
            'project_id' => 'project-uuid',
            'filter' => ['types' => ['nextgenMaterial']],
        ]);

        $this->assertLastBody(['project_id' => 'project-uuid', 'filter' => ['types' => ['nextgenMaterial']]]);
    }

    public function test_lines_fluent_interface(): void
    {
        $this->resource(ProjectLines::class)
            ->forProject('project-uuid')
            ->tasksOnly()
            ->assignedTo('user', 'user-uuid')
            ->get();

        $this->assertLastBody([
            'project_id' => 'project-uuid',
            'filter' => ['types' => ['nextgenTask'], 'assignees' => [['type' => 'user', 'id' => 'user-uuid']]],
        ]);
    }

    public function test_lines_reject_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectLines::class)->list([
            'project_id' => 'project-uuid', 'status' => 'done',
        ]), 'status');
    }

    public function test_lines_line_type_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectLines::class)->list([
            'project_id' => 'project-uuid', 'types' => ['task'],
        ]), 'filter.types[0]');
    }

    public function test_lines_require_a_project(): void
    {
        $this->expectRejected(fn () => $this->resource(ProjectLines::class)->list(['types' => ['nextgenTask']]), 'project_id');
    }

    // -- projects ------------------------------------------------------------------

    public function test_project_list_requests_the_pagination_meta(): void
    {
        $this->resource(Projects::class)->list();

        $this->assertLastEndpoint('projects-v2/projects.list');
        $this->assertLastBody(['includes' => 'pagination']);
    }

    public function test_project_list_combines_includes_and_validates_sort(): void
    {
        $this->resource(Projects::class)->list(
            ['status' => 'running', 'ids' => 'project-uuid'],
            ['include' => 'custom_fields', 'sort' => 'title', 'sort_order' => 'asc']
        );

        $this->assertLastBody([
            'filter' => ['status' => 'running', 'ids' => ['project-uuid']],
            'sort' => [['field' => 'title', 'order' => 'asc']],
            'includes' => 'custom_fields,pagination',
        ]);
    }

    public function test_project_sort_field_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Projects::class)->list([], ['sort' => 'name']), 'name');
    }

    public function test_project_status_filter_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Projects::class)->list(['status' => 'active']), 'filter.status');
    }

    public function test_project_info_accepts_legacy_project_only(): void
    {
        $this->resource(Projects::class)->info('project-uuid', 'legacy_project');
        $this->assertLastBody(['id' => 'project-uuid', 'includes' => 'legacy_project']);
    }

    public function test_project_info_rejects_custom_fields_include(): void
    {
        $this->expectRejected(fn () => $this->resource(Projects::class)->info('project-uuid', 'custom_fields'), 'legacy_project');
    }

    public function test_project_update_rejects_create_only_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(Projects::class)->update('project-uuid', [
            'customers' => [['type' => 'company', 'id' => 'c']],
        ]), 'customers');
    }

    public function test_project_update_wraps_billing_method_and_checks_durations(): void
    {
        $this->resource(Projects::class)->update('project-uuid', [
            'billing_method' => 'non_billable',
            'time_budget' => ['value' => 40, 'unit' => 'hours'],
        ]);

        $this->assertLastBodyHas('billing_method', ['value' => 'non_billable', 'update_strategy' => 'none']);
        $this->assertLastBodyHas('time_budget.unit', 'hours');
    }

    public function test_project_create_checks_customer_types(): void
    {
        $this->expectRejected(fn () => $this->resource(Projects::class)->create([
            'title' => 'T', 'customers' => [['type' => 'user', 'id' => 'u']],
        ]), 'customers[0].type');
    }

    public function test_project_close_strategy_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Projects::class)->close('project-uuid', 'done'), 'closing_strategy');
    }

    // -- legacy projects -----------------------------------------------------------

    public function test_legacy_project_status_filter_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyProjects::class)->list(['status' => 'open']), 'filter.status');
    }

    public function test_legacy_project_sort_order_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyProjects::class)->list([], ['sort' => 'title', 'sort_order' => 'up']), 'sort order');
    }

    public function test_legacy_project_half_a_customer_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyProjects::class)->list(['customer.type' => 'company']), 'customer.id');
    }

    public function test_legacy_project_update_fields_are_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyProjects::class)->update('project-uuid', ['milestones' => []]), 'milestones');
    }

    public function test_legacy_project_customer_may_be_unlinked(): void
    {
        $this->resource(LegacyProjects::class)->update('project-uuid', ['customer' => null, 'status' => 'done']);

        $this->assertLastBody(['customer' => null, 'status' => 'done', 'id' => 'project-uuid']);
    }

    public function test_legacy_project_participant_role_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyProjects::class)->addParticipant(
            'project-uuid', ['type' => 'user', 'id' => 'u'], 'owner'
        ), 'role');
    }

    public function test_legacy_project_milestones_need_their_required_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyProjects::class)->create([
            'title' => 'T',
            'starts_on' => '2026-01-01',
            'milestones' => [['name' => 'Kick-off']],
            'participants' => [['participant' => ['type' => 'user', 'id' => 'u'], 'role' => 'decision_maker']],
        ]), 'milestones[0].due_on');
    }

    // -- legacy milestones ---------------------------------------------------------

    /**
     * Before v2.2.9 a plain field name was a PHP warning, and an unknown field
     * was silently replaced by due_on.
     */
    public function test_milestone_sort_accepts_a_field_name(): void
    {
        $this->resource(LegacyMilestones::class)->list(['project_id' => 'p'], ['sort' => 'starts_on']);

        $this->assertLastBody(['filter' => ['project_id' => 'p'], 'sort' => [['field' => 'starts_on', 'order' => 'asc']]]);
    }

    public function test_milestone_sort_field_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyMilestones::class)->list([], ['sort' => [['field' => 'name']]]), 'name');
    }

    public function test_milestone_status_filter_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyMilestones::class)->list(['status' => 'done']), 'filter.status');
    }

    public function test_fixed_price_milestone_needs_a_price(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyMilestones::class)->create([
            'project_id' => 'p', 'name' => 'N', 'due_on' => '2026-01-01',
            'responsible_user_id' => 'u', 'billing_method' => 'fixed_price',
        ]), 'price');
    }

    public function test_fixed_price_milestone(): void
    {
        $this->resource(LegacyMilestones::class)->create([
            'project_id' => 'p', 'name' => 'N', 'due_on' => '2026-01-01', 'responsible_user_id' => 'u',
            'billing_method' => 'fixed_price', 'price' => ['amount' => 100, 'currency' => 'EUR'],
        ]);

        $this->assertLastEndpoint('milestones.create');
        $this->assertLastBodyHas('price.amount', 100);
    }

    public function test_milestone_update_rejects_create_only_fields(): void
    {
        $this->expectRejected(fn () => $this->resource(LegacyMilestones::class)->update('milestone-uuid', [
            'billing_method' => 'fixed_price',
        ]), 'billing_method');
    }

    // -- external parties ----------------------------------------------------------

    public function test_external_party_fields_are_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(ExternalParties::class)->update('party-uuid', ['role' => 'PM']), 'role');
    }

    public function test_external_party_add(): void
    {
        $this->resource(ExternalParties::class)->addToProject('project-uuid', 'contact', 'contact-uuid', 'PM');

        $this->assertLastEndpoint('projects-v2/externalParties.addToProject');
        $this->assertLastBody([
            'project_id' => 'project-uuid',
            'customer' => ['type' => 'contact', 'id' => 'contact-uuid'],
            'function' => 'PM',
        ]);
    }
}
