<?php

namespace McoreServices\TeamleaderSDK\Resources\Tasks;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Tasks extends Resource
{
    protected string $description = 'Manage tasks in Teamleader Focus';

    // Resource capabilities - Tasks support full CRUD operations plus special operations
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false; // No includes mentioned in API docs

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of task UUIDs',
        'user_id' => 'Filter by assigned user (or team member). Use null for unassigned tasks',
        'milestone_id' => 'Filter by milestone UUID (old projects module)',
        'completed' => 'Filter by completion status (boolean)',
        'scheduled' => 'Filter by scheduled status (boolean)',
        'due_by' => 'Filter tasks due by this date (YYYY-MM-DD)',
        'due_from' => 'Filter tasks due from this date (YYYY-MM-DD)',
        'term' => 'Search term (searches in description)',
        'customer' => 'Filter by customer (object with type and id)',
    ];

    // Available sort fields
    protected array $availableSortFields = [
        'name' => 'Sort by task name',
    ];

    // Valid assignee types
    protected array $assigneeTypes = [
        'user',
        'team',
    ];

    // Valid customer types
    protected array $customerTypes = [
        'contact',
        'company',
    ];

    // Valid priority levels
    protected array $priorityLevels = [
        'A',
        'B',
        'C',
        'D',
    ];

    // Valid time units
    protected array $timeUnits = [
        'min',
    ];

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

    /**
     * Get the base path for the tasks resource
     */
    protected function getBasePath(): string
    {
        return 'tasks';
    }

    /**
     * List tasks with filtering, sorting, and pagination
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Build filter object
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        // Apply sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get task information
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Create a new task
     *
     * Required fields: title, due_on, work_type_id
     */
    public function create(array $data): array
    {
        $this->validateTaskData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update an existing task
     *
     * All fields except id are optional.
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
     * @param  string  $startsAt  Start datetime in ISO 8601 format
     * @param  string  $endsAt  End datetime in ISO 8601 format
     */
    public function schedule(string $id, string $startsAt, string $endsAt): array
    {
        $this->validateDateTimeFormat($startsAt, 'starts_at');
        $this->validateDateTimeFormat($endsAt, 'ends_at');

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
     * Validate task data for create/update operations
     *
     * @param  string  $operation  'create' or 'update'
     *
     * @throws InvalidArgumentException
     */
    protected function validateTaskData(array $data, string $operation = 'create'): void
    {
        // Required fields for create
        if ($operation === 'create') {
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required for creating a task');
            }
            if (empty($data['due_on'])) {
                throw new InvalidArgumentException('due_on is required for creating a task');
            }
            if (empty($data['work_type_id'])) {
                throw new InvalidArgumentException('work_type_id is required for creating a task');
            }

            // Validate date format
            $this->validateDateFormat($data['due_on'], 'due_on');
        }

        // Required field for update
        if ($operation === 'update') {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('id is required for updating a task');
            }

            // Validate date format if provided
            if (isset($data['due_on'])) {
                $this->validateDateFormat($data['due_on'], 'due_on');
            }
        }

        // Validate assignee structure if provided
        if (isset($data['assignee'])) {
            if ($data['assignee'] !== null) {
                if (! is_array($data['assignee'])) {
                    throw new InvalidArgumentException('assignee must be an array or null');
                }
                if (! isset($data['assignee']['type']) || ! in_array($data['assignee']['type'], $this->assigneeTypes)) {
                    throw new InvalidArgumentException(
                        'Invalid assignee type. Must be one of: '.implode(', ', $this->assigneeTypes)
                    );
                }
                if (! isset($data['assignee']['id']) || empty($data['assignee']['id'])) {
                    throw new InvalidArgumentException('Assignee id is required');
                }
            }
        }

        // Validate customer structure if provided
        if (isset($data['customer']) && is_array($data['customer'])) {
            if (! isset($data['customer']['type']) || ! in_array($data['customer']['type'], $this->customerTypes)) {
                throw new InvalidArgumentException(
                    'Invalid customer type. Must be one of: '.implode(', ', $this->customerTypes)
                );
            }
            if (! isset($data['customer']['id']) || empty($data['customer']['id'])) {
                throw new InvalidArgumentException('Customer id is required');
            }
        }

        // Validate estimated_duration structure if provided
        if (isset($data['estimated_duration']) && is_array($data['estimated_duration'])) {
            if (! isset($data['estimated_duration']['unit']) || ! in_array($data['estimated_duration']['unit'], $this->timeUnits)) {
                throw new InvalidArgumentException(
                    'Invalid estimated_duration unit. Must be one of: '.implode(', ', $this->timeUnits)
                );
            }
            if (! isset($data['estimated_duration']['value']) || ! is_numeric($data['estimated_duration']['value'])) {
                throw new InvalidArgumentException('estimated_duration value is required and must be numeric');
            }
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @throws InvalidArgumentException
     */
    protected function validateDateFormat(string $date, string $fieldName): void
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}$/';
        if (! preg_match($pattern, $date)) {
            throw new InvalidArgumentException(
                "{$fieldName} must be in YYYY-MM-DD format (e.g., 2025-02-15)"
            );
        }
    }

    /**
     * Validate datetime format (ISO 8601)
     *
     * @throws InvalidArgumentException
     */
    protected function validateDateTimeFormat(string $datetime, string $fieldName): void
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';
        if (! preg_match($pattern, $datetime)) {
            throw new InvalidArgumentException(
                "{$fieldName} must be in ISO 8601 format (e.g., 2025-02-04T16:00:00+00:00)"
            );
        }
    }

    /**
     * Validate customer type
     *
     * @throws InvalidArgumentException
     */
    protected function validateCustomerType(string $type): void
    {
        if (! in_array($type, $this->customerTypes)) {
            throw new InvalidArgumentException(
                'Invalid customer type. Must be one of: '.implode(', ', $this->customerTypes)
            );
        }
    }

    /**
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        foreach ($filters as $key => $value) {
            // Pass through complex filter structures as-is
            if (in_array($key, ['customer'])) {
                $apiFilters[$key] = $value;
            } elseif ($key === 'ids' && is_array($value)) {
                $apiFilters[$key] = $value;
            } elseif ($key === 'user_id' && $value === null) {
                // Explicitly set null for unassigned tasks
                $apiFilters[$key] = null;
            } else {
                $apiFilters[$key] = $value;
            }
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        if (isset($sort['field'])) {
            // Single sort field
            return [[
                'field' => $sort['field'],
                'order' => $sort['order'] ?? 'asc',
            ]];
        }

        // Multiple sort fields
        return array_map(function ($item) {
            return [
                'field' => $item['field'],
                'order' => $item['order'] ?? 'asc',
            ];
        }, $sort);
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
