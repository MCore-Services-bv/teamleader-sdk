<?php

namespace McoreServices\TeamleaderSDK\Resources\Tasks;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectTasks;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

/**
 * Tasks — `tasks.*`, the stand-alone task list (with optional links to a
 * customer, deal, ticket, legacy milestone or project).
 *
 * Tasks inside a project in the current project system are a different
 * resource: {@see ProjectTasks}.
 */
class Tasks extends Resource
{
    use ValidatesWritePayload;

    /** Body fields tasks.create accepts */
    public const CREATE_FIELDS = [
        'title', 'description', 'due_on', 'work_type_id', 'milestone_id', 'project_id',
        'deal_id', 'ticket_id', 'estimated_duration', 'assignee', 'customer', 'custom_fields',
    ];

    /** Body fields tasks.update accepts, besides `id` — the same set as create */
    public const UPDATE_FIELDS = self::CREATE_FIELDS;

    public const REQUIRED_ON_CREATE = ['title', 'due_on', 'work_type_id'];

    public const ASSIGNEE_TYPES = ['team', 'user'];

    public const CUSTOMER_TYPES = ['contact', 'company'];

    /** `estimated_duration.unit` — minutes only */
    public const DURATION_UNITS = ['min'];

    /** `priority` on info responses (read-only; not a create or update field) */
    public const PRIORITIES = ['A', 'B', 'C', 'D'];

    protected string $description = 'Manage tasks in Teamleader Focus';

    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    protected array $availableIncludes = [];

    protected array $defaultIncludes = [];

    protected array $commonFilters = [
        'ids' => 'Array of task UUIDs',
        'user_id' => 'Tasks assigned to this user or to a team they belong to; null for unassigned tasks',
        'milestone_id' => 'Filter by milestone UUID (old projects module)',
        'completed' => 'Filter by completion status (boolean)',
        'scheduled' => 'Filter by scheduled status (boolean)',
        'due_by' => 'Tasks due on or before this date (YYYY-MM-DD)',
        'due_from' => 'Tasks due on or after this date (YYYY-MM-DD)',
        'term' => 'Search term (searches the description)',
        'customer' => 'Filter by customer: [type => contact|company, id => uuid]',
    ];

    /**
     * Sort fields tasks.list accepts.
     *
     * Until v2.2.10 this advertised `name`, which the API does not accept,
     * and left out both real fields.
     */
    protected array $availableSortFields = [
        'created_at' => 'Creation date',
        'due_on' => 'Due date',
    ];

    // Kept for backwards compatibility — see the constants above
    protected array $assigneeTypes = self::ASSIGNEE_TYPES;

    protected array $customerTypes = self::CUSTOMER_TYPES;

    protected array $priorityLevels = self::PRIORITIES;

    protected array $timeUnits = self::DURATION_UNITS;

    // Usage examples specific to tasks
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all tasks',
            'code' => '$tasks = $teamleader->tasks()->list();',
        ],
        'list_for_user' => [
            'description' => 'Get tasks for a specific user',
            'code' => '$tasks = $teamleader->tasks()->forUser("user-uuid");',
        ],
        'list_incomplete' => [
            'description' => 'Get incomplete tasks',
            'code' => '$tasks = $teamleader->tasks()->incomplete();',
        ],
        'create_task' => [
            'description' => 'Create a new task',
            'code' => '$task = $teamleader->tasks()->create([
    "title" => "Review code changes",
    "due_on" => "2025-02-15",
    "work_type_id" => "work-type-uuid"
]);',
        ],
        'update_task' => [
            'description' => 'Update a task',
            'code' => '$task = $teamleader->tasks()->update("task-uuid", [
    "title" => "Updated task title"
]);',
        ],
        'complete_task' => [
            'description' => 'Mark a task as complete',
            'code' => '$result = $teamleader->tasks()->complete("task-uuid");',
        ],
        'reopen_task' => [
            'description' => 'Reopen a completed task',
            'code' => '$result = $teamleader->tasks()->reopen("task-uuid");',
        ],
        'schedule_task' => [
            'description' => 'Schedule a task in calendar',
            'code' => '$event = $teamleader->tasks()->schedule(
    "task-uuid",
    "2025-02-15T09:00:00+00:00",
    "2025-02-15T10:00:00+00:00"
);',
        ],
    ];

    protected function getBasePath(): string
    {
        return 'tasks';
    }

    /**
     * List tasks
     *
     * @param  array  $filters  ids, user_id (null = unassigned), milestone_id, completed,
     *                          scheduled, due_by, due_from, term, customer
     * @param  array  $options  page_size, page_number, sort (created_at|due_on), sort_order
     *
     * @throws InvalidArgumentException On an unknown filter key, option, value or sort field
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'sort', 'sort_order', 'filters']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'tasks.list does not support: '.implode(', ', $unknown)
                .'. Supported: page_size, page_number, sort, sort_order.'
            );
        }

        $params = [];

        if ($filters !== []) {
            $params['filter'] = $this->buildFilters($filters);
        }

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'asc');
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
        $this->assertIncludes($includes, [], 'tasks.info');

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Create a task
     *
     * Requires title, due_on and work_type_id.
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
     * Every field is optional. `assignee` null unassigns the task;
     * milestone_id, project_id, deal_id and ticket_id take null to unlink.
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
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
        ]);
    }

    /**
     * Mark a task as complete
     *
     * @param  string  $id  Task UUID
     */
    public function complete(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.complete', [
            'id' => $id,
        ]);
    }

    /**
     * Reopen a task that had been marked as complete
     *
     * @param  string  $id  Task UUID
     */
    public function reopen(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.reopen', [
            'id' => $id,
        ]);
    }

    /**
     * Schedule a task in your calendar
     *
     * @param  string  $id  Task UUID
     * @param  string  $startsAt  ISO 8601 datetime, e.g. 2025-02-04T16:00:00+00:00
     * @param  string  $endsAt  ISO 8601 datetime, after $startsAt
     *
     * @throws InvalidArgumentException On a malformed datetime, or an end before the start
     */
    public function schedule(string $id, string $startsAt, string $endsAt): array
    {
        $this->validateDateTimeFormat($startsAt, 'starts_at');
        $this->validateDateTimeFormat($endsAt, 'ends_at');

        if (strtotime($endsAt) <= strtotime($startsAt)) {
            throw new InvalidArgumentException('ends_at must be after starts_at');
        }

        return $this->api->request('POST', $this->getBasePath().'.schedule', [
            'id' => $id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * Get tasks for a specific user
     *
     * @param  string  $userId  User UUID
     * @param  array  $options  Additional options
     */
    public function forUser(string $userId, array $options = []): array
    {
        return $this->list(
            array_merge(['user_id' => $userId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get unassigned tasks
     *
     * @param  array  $options  Additional options
     */
    public function unassigned(array $options = []): array
    {
        return $this->list(
            array_merge(['user_id' => null], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get completed tasks
     *
     * @param  array  $options  Additional options
     */
    public function completed(array $options = []): array
    {
        return $this->list(
            array_merge(['completed' => true], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get incomplete tasks
     *
     * @param  array  $options  Additional options
     */
    public function incomplete(array $options = []): array
    {
        return $this->list(
            array_merge(['completed' => false], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get scheduled tasks
     *
     * @param  array  $options  Additional options
     */
    public function scheduled(array $options = []): array
    {
        return $this->list(
            array_merge(['scheduled' => true], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get tasks for a milestone
     *
     * @param  string  $milestoneId  Milestone UUID
     * @param  array  $options  Additional options
     */
    public function forMilestone(string $milestoneId, array $options = []): array
    {
        return $this->list(
            array_merge(['milestone_id' => $milestoneId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get tasks for a customer
     *
     * @param  string  $customerType  Type of customer ('contact' or 'company')
     * @param  string  $customerId  UUID of the customer
     * @param  array  $options  Additional options
     */
    public function forCustomer(string $customerType, string $customerId, array $options = []): array
    {
        $this->validateCustomerType($customerType);

        return $this->list(
            array_merge([
                'customer' => [
                    'type' => $customerType,
                    'id' => $customerId,
                ],
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get tasks due within a date range
     *
     * @param  string  $dueFrom  Start date (YYYY-MM-DD)
     * @param  string  $dueBy  End date (YYYY-MM-DD)
     * @param  array  $options  Additional options
     */
    public function dueBetween(string $dueFrom, string $dueBy, array $options = []): array
    {
        $this->validateDateFormat($dueFrom, 'due_from');
        $this->validateDateFormat($dueBy, 'due_by');

        return $this->list(
            array_merge([
                'due_from' => $dueFrom,
                'due_by' => $dueBy,
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Search tasks by term (searches in description)
     *
     * @param  string  $term  Search term
     * @param  array  $options  Additional options
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(
            array_merge(['term' => $term], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get tasks by specific IDs
     *
     * @param  array  $ids  Array of task UUIDs
     * @param  array  $options  Additional options
     */
    public function byIds(array $ids, array $options = []): array
    {
        return $this->list(
            array_merge(['ids' => $ids], $options['filters'] ?? []),
            $options
        );
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
        $endpoint = 'tasks.'.$operation;

        if ($operation === 'create') {
            foreach (self::REQUIRED_ON_CREATE as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException("{$field} is required for creating a task");
                }
            }

            $this->rejectUnknownFields($data, self::CREATE_FIELDS, $endpoint);
        } else {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('id is required for updating a task');
            }

            $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], $endpoint);
        }

        if (isset($data['due_on'])) {
            $this->validateDateFormat($data['due_on'], 'due_on');
        }

        // null unassigns
        if (isset($data['assignee'])) {
            if (! is_array($data['assignee']) || empty($data['assignee']['id']) || ! isset($data['assignee']['type'])) {
                throw new InvalidArgumentException("assignee must be ['type' => user|team, 'id' => uuid], or null to unassign");
            }

            $this->assertEnum($data['assignee']['type'], self::ASSIGNEE_TYPES, 'assignee.type', $endpoint);
        }

        if (isset($data['customer'])) {
            if (! is_array($data['customer']) || empty($data['customer']['id']) || ! isset($data['customer']['type'])) {
                throw new InvalidArgumentException("customer must be ['type' => contact|company, 'id' => uuid]");
            }

            $this->assertEnum($data['customer']['type'], self::CUSTOMER_TYPES, 'customer.type', $endpoint);
        }

        if (isset($data['estimated_duration'])) {
            $duration = $data['estimated_duration'];

            if (! is_array($duration) || ! isset($duration['value']) || ! is_numeric($duration['value'])) {
                throw new InvalidArgumentException("estimated_duration must be ['value' => number, 'unit' => 'min']");
            }

            $this->assertEnum($duration['unit'] ?? null, self::DURATION_UNITS, 'estimated_duration.unit', $endpoint);

            if (! isset($duration['unit'])) {
                throw new InvalidArgumentException("estimated_duration needs a unit: 'min'");
            }
        }
    }

    /**
     * Validate a Y-m-d date
     *
     * Checks the date exists, not just its shape: 2025-02-30 is rejected.
     *
     * @throws InvalidArgumentException
     */
    protected function validateDateFormat(string $date, string $fieldName): void
    {
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException(
                "{$fieldName} must be a date in YYYY-MM-DD format (e.g., 2025-02-15)"
            );
        }
    }

    /**
     * Validate an ISO 8601 datetime with a timezone
     *
     * Accepts an offset (+01:00) or Z, with optional fractional seconds.
     * Before v2.2.10, Z was rejected.
     *
     * @throws InvalidArgumentException
     */
    protected function validateDateTimeFormat(string $datetime, string $fieldName): void
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/';

        if (! preg_match($pattern, $datetime) || strtotime($datetime) === false) {
            throw new InvalidArgumentException(
                "{$fieldName} must be in ISO 8601 format with a timezone (e.g., 2025-02-04T16:00:00+00:00)"
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateCustomerType(string $type): void
    {
        $this->assertEnum($type, self::CUSTOMER_TYPES, 'customer.type', 'tasks.list');
    }

    /**
     * Build the filter object for tasks.list
     *
     * Until v2.2.10 every key was passed through unchecked, so a mistyped key
     * (`assignee_id`, `status`) returned every task.
     *
     * @throws InvalidArgumentException On an unknown key or value
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'tasks.list');

        foreach (['completed', 'scheduled'] as $flag) {
            if (isset($filters[$flag]) && ! is_bool($filters[$flag])) {
                throw new InvalidArgumentException("{$flag} must be true or false.");
            }
        }

        foreach (['due_by', 'due_from'] as $field) {
            if (isset($filters[$field])) {
                $this->validateDateFormat($filters[$field], $field);
            }
        }

        if (isset($filters['customer'])) {
            if (! is_array($filters['customer']) || empty($filters['customer']['id']) || ! isset($filters['customer']['type'])) {
                throw new InvalidArgumentException("The customer filter must be ['type' => contact|company, 'id' => uuid].");
            }

            $this->assertEnum($filters['customer']['type'], self::CUSTOMER_TYPES, 'filter.customer.type', 'tasks.list');
        }

        if (isset($filters['ids']) && ! is_array($filters['ids'])) {
            $filters['ids'] = [$filters['ids']];
        }

        // user_id null means "unassigned" and is sent; any other null is dropped
        return array_filter(
            $filters,
            fn ($value, $key) => $value !== null || $key === 'user_id',
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Build the sort array for tasks.list
     *
     * Accepts a field name, a list of names, ['field' => ..., 'order' => ...]
     * or a list of those. Before v2.2.10 a field name was a TypeError, and
     * sort_order was ignored.
     *
     * @param  array|string  $sort
     *
     * @throws InvalidArgumentException On an unknown field or order
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        return $this->normaliseSort($sort, $order);
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
                    'data.title' => 'Task title',
                    'data.description' => 'Task description',
                    'data.completed' => 'Completion status (boolean)',
                    'data.completed_at' => 'Completion datetime (nullable)',
                    'data.due_on' => 'Due date (YYYY-MM-DD)',
                    'data.added_at' => 'Creation datetime (nullable)',
                    'data.estimated_duration' => 'Estimated duration object (nullable)',
                    'data.work_type' => 'Work type object (nullable)',
                    'data.assignee' => 'Assignee object (nullable)',
                    'data.customer' => 'Customer object (nullable)',
                    'data.milestone' => 'Milestone object (nullable)',
                    'data.deal' => 'Deal object (nullable)',
                    'data.project' => 'Project object (nullable)',
                    'data.ticket' => 'Ticket object (nullable)',
                    'data.custom_fields' => 'Array of custom field values',
                    'data.priority' => 'Priority level (A, B, C, or D)',
                ],
            ],
            'list' => [
                'description' => 'Array of tasks',
                'fields' => [
                    'data' => 'Array of task objects with structure similar to info endpoint',
                ],
            ],
            'schedule' => [
                'description' => 'Response contains the created calendar event',
                'fields' => [
                    'data.id' => 'UUID of the created event',
                    'data.type' => 'Resource type (always "event")',
                ],
            ],
        ];
    }
}
