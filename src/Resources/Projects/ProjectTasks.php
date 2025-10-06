<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class ProjectTasks extends Resource
{
    protected string $description = 'Manage tasks in Teamleader Focus projects';

    // Resource capabilities - Tasks support full CRUD operations plus special operations
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = true;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of task UUIDs'
    ];

    // Valid billing methods
    protected array $billingMethods = [
        'user_rate',
        'work_type_rate',
        'custom_rate',
        'fixed_price',
        'parent_fixed_price',
        'non_billable'
    ];

    // Valid status values
    protected array $statusValues = [
        'to_do',
        'in_progress',
        'on_hold',
        'done'
    ];

    // Valid assignee types
    protected array $assigneeTypes = [
        'user',
        'team'
    ];

    // Valid time units
    protected array $timeUnits = [
        'hours',
        'minutes',
        'seconds'
    ];

    // Valid currency codes
    protected array $currencyCodes = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK',
        'EUR', 'GBP', 'INR', 'ISK', 'JPY', 'MAD', 'MXN', 'NOK',
        'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR'
    ];

    // Valid delete strategies
    protected array $deleteStrategies = [
        'unlink_time_tracking',
        'delete_time_tracking'
    ];

    // Usage examples specific to tasks
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all tasks',
            'code' => '$tasks = $teamleader->projectTasks()->list();'
        ],
        'create_task' => [
            'description' => 'Create a new task',
            'code' => '$task = $teamleader->projectTasks()->create([
    \'project_id\' => \'uuid\',
    \'title\' => \'Write API documentation\',
    \'billing_method\' => \'user_rate\'
]);'
        ],
        'update_status' => [
            'description' => 'Update task status',
            'code' => '$task = $teamleader->projectTasks()->update(\'task-uuid\', [
    \'status\' => \'in_progress\'
]);'
        ],
        'assign_user' => [
            'description' => 'Assign a user to a task',
            'code' => '$teamleader->projectTasks()->assign(\'task-uuid\', \'user\', \'user-uuid\');'
        ],
        'delete_task' => [
            'description' => 'Delete a task and unlink time tracking',
            'code' => '$teamleader->projectTasks()->delete(\'task-uuid\', \'unlink_time_tracking\');'
        ]
    ];

    /**
     * Get the base path for the tasks resource
     */
    protected function getBasePath(): string
    {
        return 'projects-v2/tasks';
    }

    /**
     * List tasks with filtering and pagination
     *
     * @param array $filters Filters to apply
     * @param array $options Additional options (pagination)
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Build filter object
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1
            ];
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get detailed information about a specific task
     *
     * @param string $id Task UUID
     * @param array|string|null $includes Not used for tasks (no sideloading support)
     * @return array
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.info', [
            'id' => $id
        ]);
    }

    /**
     * Create a new task
     *
     * @param array $data Task data
     * @return array
     * @throws InvalidArgumentException
     */
    public function create(array $data): array
    {
        $this->validateTaskData($data, 'create');

        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
    }

    /**
     * Update an existing task
     *
     * @param string $id Task UUID
     * @param array $data Data to update
     * @return array
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateTaskData($data, 'update');

        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Delete a task
     *
     * @param string $id Task UUID
     * @param mixed ...$additionalParams Additional parameters (first param should be delete strategy)
     * @return array
     * @throws InvalidArgumentException
     */
    public function delete($id, ...$additionalParams): array
    {
        // Extract delete strategy from additional params, default to 'unlink_time_tracking'
        $deleteStrategy = $additionalParams[0] ?? 'unlink_time_tracking';

        if (!in_array($deleteStrategy, $this->deleteStrategies)) {
            throw new InvalidArgumentException(
                'Invalid delete_strategy. Must be one of: ' . implode(', ', $this->deleteStrategies)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.delete', [
            'id' => $id,
            'delete_strategy' => $deleteStrategy
        ]);
    }

    /**
     * Assign a user or team to a task
     *
     * @param string $taskId Task UUID
     * @param string $assigneeType Type of assignee ('user' or 'team')
     * @param string $assigneeId UUID of the user or team
     * @return array
     * @throws InvalidArgumentException
     */
    public function assign(string $taskId, string $assigneeType, string $assigneeId): array
    {
        if (!in_array($assigneeType, $this->assigneeTypes)) {
            throw new InvalidArgumentException(
                'Invalid assignee type. Must be one of: ' . implode(', ', $this->assigneeTypes)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.assign', [
            'id' => $taskId,
            'assignee' => [
                'type' => $assigneeType,
                'id' => $assigneeId
            ]
        ]);
    }

    /**
     * Unassign a user or team from a task
     *
     * @param string $taskId Task UUID
     * @param string $assigneeType Type of assignee ('user' or 'team')
     * @param string $assigneeId UUID of the user or team
     * @return array
     * @throws InvalidArgumentException
     */
    public function unassign(string $taskId, string $assigneeType, string $assigneeId): array
    {
        if (!in_array($assigneeType, $this->assigneeTypes)) {
            throw new InvalidArgumentException(
                'Invalid assignee type. Must be one of: ' . implode(', ', $this->assigneeTypes)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.unassign', [
            'id' => $taskId,
            'assignee' => [
                'type' => $assigneeType,
                'id' => $assigneeId
            ]
        ]);
    }

    /**
     * Duplicate a task (without time trackings)
     *
     * @param string $originId The UUID of the task to duplicate
     * @return array
     */
    public function duplicate(string $originId): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.duplicate', [
            'origin_id' => $originId
        ]);
    }

    /**
     * Get tasks by specific IDs
     *
     * @param array $ids Array of task UUIDs
     * @return array
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Convenience method: Assign a user to a task
     *
     * @param string $taskId Task UUID
     * @param string $userId User UUID
     * @return array
     */
    public function assignUser(string $taskId, string $userId): array
    {
        return $this->assign($taskId, 'user', $userId);
    }

    /**
     * Convenience method: Assign a team to a task
     *
     * @param string $taskId Task UUID
     * @param string $teamId Team UUID
     * @return array
     */
    public function assignTeam(string $taskId, string $teamId): array
    {
        return $this->assign($taskId, 'team', $teamId);
    }

    /**
     * Convenience method: Unassign a user from a task
     *
     * @param string $taskId Task UUID
     * @param string $userId User UUID
     * @return array
     */
    public function unassignUser(string $taskId, string $userId): array
    {
        return $this->unassign($taskId, 'user', $userId);
    }

    /**
     * Convenience method: Unassign a team from a task
     *
     * @param string $taskId Task UUID
     * @param string $teamId Team UUID
     * @return array
     */
    public function unassignTeam(string $taskId, string $teamId): array
    {
        return $this->unassign($taskId, 'team', $teamId);
    }

    /**
     * Convenience method: Update task status
     *
     * @param string $taskId Task UUID
     * @param string $status New status
     * @return array
     */
    public function updateStatus(string $taskId, string $status): array
    {
        return $this->update($taskId, ['status' => $status]);
    }

    /**
     * Validate task data for create/update operations
     *
     * @param array $data
     * @param string $operation 'create' or 'update'
     * @throws InvalidArgumentException
     */
    protected function validateTaskData(array $data, string $operation = 'create'): void
    {
        // Required fields for create
        if ($operation === 'create') {
            if (empty($data['project_id'])) {
                throw new InvalidArgumentException('project_id is required for creating a task');
            }
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required for creating a task');
            }
        }

        // Required field for update
        if ($operation === 'update') {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('id is required for updating a task');
            }
        }

        // Validate billing_method if provided
        if (isset($data['billing_method']) && !in_array($data['billing_method'], $this->billingMethods)) {
            throw new InvalidArgumentException(
                'Invalid billing_method. Must be one of: ' . implode(', ', $this->billingMethods)
            );
        }

        // Validate work_type_id requirement for work_type_rate billing
        if (isset($data['billing_method']) && $data['billing_method'] === 'work_type_rate') {
            if (empty($data['work_type_id'])) {
                throw new InvalidArgumentException(
                    'work_type_id is required when billing_method is work_type_rate'
                );
            }
        }

        // Validate status if provided
        if (isset($data['status']) && !in_array($data['status'], $this->statusValues)) {
            throw new InvalidArgumentException(
                'Invalid status. Must be one of: ' . implode(', ', $this->statusValues)
            );
        }

        // Validate time_estimated structure if provided
        if (isset($data['time_estimated'])) {
            if (!is_array($data['time_estimated'])) {
                throw new InvalidArgumentException('time_estimated must be an object with value and unit');
            }
            if (!isset($data['time_estimated']['value']) || !isset($data['time_estimated']['unit'])) {
                throw new InvalidArgumentException('time_estimated requires both value and unit');
            }
            if (!in_array($data['time_estimated']['unit'], $this->timeUnits)) {
                throw new InvalidArgumentException(
                    'Invalid time unit. Must be one of: ' . implode(', ', $this->timeUnits)
                );
            }
        }

        // Validate monetary amounts structure if provided
        $monetaryFields = ['fixed_price', 'external_budget', 'internal_budget', 'custom_rate'];
        foreach ($monetaryFields as $field) {
            if (isset($data[$field])) {
                if (!is_array($data[$field])) {
                    throw new InvalidArgumentException("{$field} must be an object with amount and currency");
                }
                if (!isset($data[$field]['amount']) || !isset($data[$field]['currency'])) {
                    throw new InvalidArgumentException("{$field} requires both amount and currency");
                }
                if (!in_array($data[$field]['currency'], $this->currencyCodes)) {
                    throw new InvalidArgumentException(
                        'Invalid currency code. Must be one of: ' . implode(', ', $this->currencyCodes)
                    );
                }
            }
        }

        // Validate assignees structure if provided
        if (isset($data['assignees']) && is_array($data['assignees'])) {
            foreach ($data['assignees'] as $assignee) {
                if (!isset($assignee['type']) || !in_array($assignee['type'], $this->assigneeTypes)) {
                    throw new InvalidArgumentException(
                        'Invalid assignee type. Must be one of: ' . implode(', ', $this->assigneeTypes)
                    );
                }
                if (!isset($assignee['id'])) {
                    throw new InvalidArgumentException('Each assignee must have an id');
                }
            }
        }

        // Validate date formats if provided
        if (isset($data['start_date']) && !$this->isValidDate($data['start_date'])) {
            throw new InvalidArgumentException('start_date must be in Y-m-d format (e.g., 2023-01-18)');
        }
        if (isset($data['end_date']) && !$this->isValidDate($data['end_date'])) {
            throw new InvalidArgumentException('end_date must be in Y-m-d format (e.g., 2023-03-22)');
        }
    }

    /**
     * Validate date format (Y-m-d)
     *
     * @param string $date
     * @return bool
     */
    protected function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Get response structure documentation
     *
     * @return array
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created task ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created task',
                    'data.type' => 'Resource type (always "task")'
                ]
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
                    'data.custom_fields' => 'Array of custom field values'
                ]
            ],
            'list' => [
                'description' => 'Array of tasks with pagination',
                'fields' => [
                    'data' => 'Array of task objects with structure similar to info endpoint'
                ]
            ]
        ];
    }
}
