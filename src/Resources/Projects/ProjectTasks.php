<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Tasks\Tasks;

/**
 * Tasks in the current ("nextgen") project system — `projects-v2/tasks.*`.
 *
 * For the older task system, see {@see Tasks}.
 */
class ProjectTasks extends ProjectsV2Resource
{
    /** Body fields tasks.create accepts */
    public const CREATE_FIELDS = [
        'project_id', 'title', 'group_id', 'work_type_id', 'task_type_id', 'description',
        'billing_method', 'fixed_price', 'external_budget', 'internal_budget', 'custom_rate',
        'start_date', 'end_date', 'time_estimated', 'assignees',
    ];

    /** Body fields tasks.update accepts, besides `id` */
    public const UPDATE_FIELDS = [
        'work_type_id', 'task_type_id', 'status', 'title', 'description', 'billing_method',
        'fixed_price', 'external_budget', 'internal_budget', 'custom_rate',
        'start_date', 'end_date', 'time_estimated',
    ];

    public const BILLING_METHODS = [
        'user_rate', 'work_type_rate', 'custom_rate', 'fixed_price', 'parent_fixed_price', 'non_billable',
    ];

    public const STATUSES = ['to_do', 'in_progress', 'on_hold', 'done'];

    public const DELETE_STRATEGIES = ['unlink_time_tracking', 'delete_time_tracking'];

    private const MONEY_FIELDS = ['fixed_price', 'external_budget', 'internal_budget', 'custom_rate'];

    protected string $description = 'Manage tasks in Teamleader Focus projects';

    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    protected array $availableIncludes = [];

    protected array $defaultIncludes = [];

    protected array $commonFilters = [
        'ids' => 'Array of task UUIDs',
    ];

    // Kept for backwards compatibility — see the constants above
    protected array $billingMethods = self::BILLING_METHODS;

    protected array $statusValues = self::STATUSES;

    protected array $assigneeTypes = self::ASSIGNEE_TYPES;

    protected array $timeUnits = self::TIME_UNITS;

    protected array $currencyCodes = self::CURRENCIES;

    protected array $deleteStrategies = self::DELETE_STRATEGIES;

    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all tasks',
            'code' => '$tasks = $teamleader->projectTasks()->list();',
        ],
        'create_task' => [
            'description' => 'Create a new task',
            'code' => '$task = $teamleader->projectTasks()->create([
    \'project_id\' => \'uuid\',
    \'title\' => \'Write API documentation\',
    \'billing_method\' => \'user_rate\'
]);',
        ],
        'update_status' => [
            'description' => 'Update task status',
            'code' => '$task = $teamleader->projectTasks()->update(\'task-uuid\', [
    \'status\' => \'in_progress\'
]);',
        ],
        'assign_user' => [
            'description' => 'Assign a user to a task',
            'code' => '$teamleader->projectTasks()->assign(\'task-uuid\', \'user\', \'user-uuid\');',
        ],
        'delete_task' => [
            'description' => 'Delete a task and unlink time tracking',
            'code' => '$teamleader->projectTasks()->delete(\'task-uuid\', \'unlink_time_tracking\');',
        ],
    ];

    protected function getBasePath(): string
    {
        return 'projects-v2/tasks';
    }

    /**
     * List tasks
     *
     * Before v2.2.9 any filter was a fatal error: list() called a
     * buildFilters() method the class did not define.
     *
     * @param  array  $filters  ids (a UUID or a list of UUIDs)
     * @param  array  $options  page_size, page_number
     *
     * @throws InvalidArgumentException On an unknown filter key or option
     */
    public function list(array $filters = [], array $options = []): array
    {
        $endpoint = $this->getBasePath().'.list';

        $this->rejectUnknownFilters($filters, $endpoint);
        $this->rejectUnknownOptions($options, ['page_size', 'page_number'], $endpoint);

        $params = [];
        $filter = $this->wrapArrayFilters($filters, ['ids']);

        if ($filter !== []) {
            $params['filter'] = $filter;
        }

        if (($page = $this->pageFromOptions($options)) !== null) {
            $params['page'] = $page;
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get one task
     *
     * @param  string  $id  Task UUID
     * @param  mixed  $includes  tasks.info takes no includes; passing any throws
     */
    public function info($id, $includes = null): array
    {
        $this->assertIncludes($includes, [], $this->getBasePath().'.info');

        return $this->api->request('POST', $this->getBasePath().'.info', ['id' => $id]);
    }

    /**
     * Create a task
     *
     * Requires project_id and title. With billing_method `work_type_rate`, a
     * work_type_id is required as well.
     *
     * @throws InvalidArgumentException When a required field is missing, or a field or value is not accepted
     */
    public function create(array $data): array
    {
        $this->validateTaskData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update a task
     *
     * Every field is optional; null clears a nullable field.
     *
     * @param  string  $id  Task UUID
     *
     * @throws InvalidArgumentException When a field or value is not accepted
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateTaskData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a task
     *
     * @param  string  $id  Task UUID
     * @param  mixed  ...$additionalParams  Delete strategy: unlink_time_tracking (default) or delete_time_tracking
     *
     * @throws InvalidArgumentException
     */
    public function delete($id, ...$additionalParams): array
    {
        $strategy = $additionalParams[0] ?? 'unlink_time_tracking';
        $this->assertEnum($strategy, self::DELETE_STRATEGIES, 'delete_strategy', $this->getBasePath().'.delete');

        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
            'delete_strategy' => $strategy,
        ]);
    }

    /**
     * Duplicate a task (without its time trackings)
     *
     * @param  string  $originId  UUID of the task to duplicate
     */
    public function duplicate(string $originId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.duplicate', [
            'origin_id' => $originId,
        ]);
    }

    /**
     * Get tasks by ID
     *
     * @param  array  $ids  Task UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Update a task's status
     *
     * @param  string  $status  to_do, in_progress, on_hold or done
     */
    public function updateStatus(string $taskId, string $status): array
    {
        return $this->update($taskId, ['status' => $status]);
    }

    /**
     * Validate a create or update body against the specification
     *
     * @param  string  $operation  'create' or 'update'
     *
     * @throws InvalidArgumentException
     */
    protected function validateTaskData(array $data, string $operation = 'create'): void
    {
        $endpoint = $this->getBasePath().'.'.$operation;

        if ($operation === 'create') {
            if (empty($data['project_id'])) {
                throw new InvalidArgumentException('project_id is required for creating a task');
            }
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required for creating a task');
            }

            $this->rejectUnknownFields($data, self::CREATE_FIELDS, $endpoint);
        } else {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('id is required for updating a task');
            }

            $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], $endpoint);
        }

        $this->assertEnum($data['billing_method'] ?? null, self::BILLING_METHODS, 'billing_method', $endpoint);
        $this->assertEnum($data['status'] ?? null, self::STATUSES, 'status', $endpoint);

        // "Cannot be null if billing_method is work_type_rate." On create that
        // means one must be given (task_type_id is its deprecated name); on
        // update it may be omitted — the task keeps its work type — but not
        // cleared.
        if (($data['billing_method'] ?? null) === 'work_type_rate') {
            $missing = $operation === 'create'
                ? empty($data['work_type_id']) && empty($data['task_type_id'])
                : array_key_exists('work_type_id', $data) && $data['work_type_id'] === null;

            if ($missing) {
                throw new InvalidArgumentException('work_type_id is required when billing_method is work_type_rate');
            }
        }

        $this->assertMoney($data, self::MONEY_FIELDS, $endpoint);
        $this->assertDuration($data, ['time_estimated'], $endpoint);
        $this->assertAssignees($data, $endpoint);
        $this->assertDates($data, ['start_date', 'end_date'], $endpoint);
    }

    /**
     * Validate date format (Y-m-d)
     */
    protected function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created task ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created task',
                    'data.type' => 'Resource type (always "task")',
                ],
            ],
            'info' => [
                'description' => 'Complete task information',
                'fields' => [
                    'data.id' => 'Task UUID',
                    'data.project' => 'Project reference',
                    'data.group' => 'Group reference (nullable)',
                    'data.work_type' => 'Work type reference (nullable)',
                    'data.task_type' => 'DEPRECATED - Use work_type instead (nullable)',
                    'data.status' => 'Task status (to_do, in_progress, on_hold, done)',
                    'data.title' => 'Task title',
                    'data.description' => 'Task description (nullable)',
                    'data.billing_method' => 'Billing method',
                    'data.billing_status' => 'Billing status (not_billable, not_billed, partially_billed, fully_billed)',
                    'data.custom_rate' => 'Custom rate (nullable)',
                    'data.amount_billed' => 'Amount billed (nullable)',
                    'data.external_budget' => 'External budget (nullable)',
                    'data.external_budget_spent' => 'External budget spent (nullable)',
                    'data.internal_budget' => 'Internal budget (nullable)',
                    'data.price' => 'Calculated price (nullable)',
                    'data.unit_price' => 'Unit price (nullable)',
                    'data.fixed_price' => 'Fixed price (nullable)',
                    'data.cost' => 'Calculated cost (nullable)',
                    'data.unit_cost' => 'Unit cost (nullable)',
                    'data.margin' => 'Calculated margin (nullable)',
                    'data.margin_percentage' => 'Margin percentage (nullable)',
                    'data.assignees' => 'Array of assigned users/teams',
                    'data.start_date' => 'Start date (nullable)',
                    'data.end_date' => 'End date (nullable)',
                    'data.time_estimated' => 'Estimated time (nullable)',
                    'data.time_tracked' => 'Tracked time (nullable)',
                    'data.custom_fields' => 'Array of custom field values',
                ],
            ],
            'list' => [
                'description' => 'Array of tasks with pagination',
                'fields' => [
                    'data' => 'Array of task objects with structure similar to info endpoint',
                ],
            ],
        ];
    }
}
