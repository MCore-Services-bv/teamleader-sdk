<?php

namespace McoreServices\TeamleaderSDK\Resources\TimeTracking;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class TimeTracking extends Resource
{
    protected string $description = 'Manage time tracking entries in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = true;
    protected bool $supportsSorting = true;
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = true;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'materials',
        'relates_to'
    ];

    // Default includes
    protected array $defaultIncludes = [];

    // Available subject types
    protected array $availableSubjectTypes = [
        'company',
        'contact',
        'event',
        'milestone',
        'nextgenTask',
        'ticket',
        'todo'
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of time tracking entry UUIDs',
        'user_id' => 'Filter by user UUID',
        'started_after' => 'Start of period (ISO 8601 datetime)',
        'started_before' => 'End of period (ISO 8601 datetime)',
        'ended_after' => 'Start of period for ended entries (ISO 8601 datetime)',
        'ended_before' => 'End of period for ended entries (ISO 8601 datetime)',
        'subject' => 'Filter by subject (id and type)',
        'subject_types' => 'Filter by subject types array',
        'relates_to' => 'Filter by related entity (milestone or project)'
    ];

    // Usage examples
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all time tracking entries',
            'code' => '$entries = $teamleader->timeTracking()->list();'
        ],
        'filter_by_user' => [
            'description' => 'Get entries for specific user',
            'code' => '$entries = $teamleader->timeTracking()->forUser("user-uuid");'
        ],
        'filter_by_date_range' => [
            'description' => 'Get entries within date range',
            'code' => '$entries = $teamleader->timeTracking()->betweenDates("2024-01-01", "2024-01-31");'
        ],
        'with_materials' => [
            'description' => 'Get entries with materials',
            'code' => '$entries = $teamleader->timeTracking()->withMaterials()->list();'
        ],
        'create_entry' => [
            'description' => 'Create a time tracking entry',
            'code' => '$entry = $teamleader->timeTracking()->create([
    "started_at" => "2024-01-15T10:00:00+00:00",
    "duration" => 3600,
    "subject" => [
        "id" => "company-uuid",
        "type" => "company"
    ]
]);'
        ]
    ];

    /**
     * Get the base path for the time tracking resource
     */
    protected function getBasePath(): string
    {
        return 'timeTracking';
    }

    /**
     * List time tracking entries with enhanced filtering and sorting
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = $this->buildQueryParams(
            [],
            $filters,
            $options['sort'] ?? null,
            $options['sort_order'] ?? 'asc',
            $options['page_size'] ?? 20,
            $options['page_number'] ?? 1,
            $options['include'] ?? null
        );

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get time tracking entry information
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        if (!empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.info', $params);
    }

    /**
     * Create a new time tracking entry
     * Supports three variants:
     * 1. started_at + duration
     * 2. started_at + ended_at
     * 3. started_on + duration (duration tracking mode)
     */
    public function create(array $data): array
    {
        $validatedData = $this->validateTimeTrackingData($data, 'create');
        return $this->api->request('POST', $this->getBasePath() . '.add', $validatedData);
    }

    /**
     * Update a time tracking entry
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $validatedData = $this->validateTimeTrackingData($data, 'update');
        return $this->api->request('POST', $this->getBasePath() . '.update', $validatedData);
    }

    /**
     * Delete a time tracking entry
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Resume a timer based on previously tracked time
     */
    public function resume(string $id, ?string $startedAt = null): array
    {
        $params = ['id' => $id];

        if ($startedAt !== null) {
            $params['started_at'] = $startedAt;
        }

        return $this->api->request('POST', $this->getBasePath() . '.resume', $params);
    }

    /**
     * Filter entries by user
     */
    public function forUser(string $userId, array $options = []): array
    {
        return $this->list(
            array_merge(['user_id' => $userId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter entries by subject
     */
    public function forSubject(string $subjectId, string $subjectType, array $options = []): array
    {
        if (!in_array($subjectType, $this->availableSubjectTypes)) {
            throw new InvalidArgumentException(
                'Invalid subject type. Must be one of: ' . implode(', ', $this->availableSubjectTypes)
            );
        }

        return $this->list(
            array_merge(
                ['subject' => ['id' => $subjectId, 'type' => $subjectType]],
                $options['filters'] ?? []
            ),
            $options
        );
    }

    /**
     * Filter entries by subject types
     */
    public function forSubjectTypes(array $subjectTypes, array $options = []): array
    {
        foreach ($subjectTypes as $type) {
            if (!in_array($type, $this->availableSubjectTypes)) {
                throw new InvalidArgumentException(
                    'Invalid subject type: ' . $type . '. Must be one of: ' . implode(', ', $this->availableSubjectTypes)
                );
            }
        }

        return $this->list(
            array_merge(['subject_types' => $subjectTypes], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter entries by date range (started)
     */
    public function betweenDates(string $startDate, string $endDate, array $options = []): array
    {
        return $this->list(
            array_merge([
                'started_after' => $startDate,
                'started_before' => $endDate
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter entries by ended date range
     */
    public function endedBetween(string $startDate, string $endDate, array $options = []): array
    {
        return $this->list(
            array_merge([
                'ended_after' => $startDate,
                'ended_before' => $endDate
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter entries related to a milestone or project
     */
    public function relatedTo(string $entityId, string $entityType, array $options = []): array
    {
        $validTypes = ['milestone', 'project'];
        if (!in_array($entityType, $validTypes)) {
            throw new InvalidArgumentException(
                'Invalid relates_to type. Must be one of: ' . implode(', ', $validTypes)
            );
        }

        return $this->list(
            array_merge(
                ['relates_to' => ['id' => $entityId, 'type' => $entityType]],
                $options['filters'] ?? []
            ),
            $options
        );
    }

    /**
     * Fluent interface: Include materials
     */
    public function withMaterials(): self
    {
        return $this->with('materials');
    }

    /**
     * Fluent interface: Include relates_to
     */
    public function withRelations(): self
    {
        return $this->with('relates_to');
    }

    /**
     * Validate time tracking data
     */
    protected function validateTimeTrackingData(array $data, string $operation): array
    {
        // ID is required for update
        if ($operation === 'update' && !isset($data['id'])) {
            throw new InvalidArgumentException('ID is required for update operation');
        }

        // Validate time tracking variant
        if ($operation === 'create' || isset($data['started_at']) || isset($data['started_on']) || isset($data['ended_at'])) {
            $this->validateTimeTrackingVariant($data);
        }

        // Validate subject if provided
        if (isset($data['subject'])) {
            $this->validateSubject($data['subject']);
        }

        // Validate work_type_id format if provided
        if (isset($data['work_type_id']) && !$this->isValidUuid($data['work_type_id'])) {
            throw new InvalidArgumentException('Invalid work_type_id format. Must be a valid UUID');
        }

        // Validate user_id format if provided
        if (isset($data['user_id']) && !$this->isValidUuid($data['user_id'])) {
            throw new InvalidArgumentException('Invalid user_id format. Must be a valid UUID');
        }

        // Validate invoiceable if provided
        if (isset($data['invoiceable']) && !is_bool($data['invoiceable'])) {
            throw new InvalidArgumentException('Invoiceable must be a boolean value');
        }

        // Validate duration if provided (must be positive integer)
        if (isset($data['duration']) && (!is_int($data['duration']) || $data['duration'] <= 0)) {
            throw new InvalidArgumentException('Duration must be a positive integer (seconds)');
        }

        return $data;
    }

    /**
     * Validate time tracking variant
     */
    protected function validateTimeTrackingVariant(array $data): void
    {
        $hasStartedAt = isset($data['started_at']);
        $hasEndedAt = isset($data['ended_at']);
        $hasStartedOn = isset($data['started_on']);
        $hasDuration = isset($data['duration']);

        // Variant 1: started_at + duration
        // Variant 2: started_at + ended_at
        // Variant 3: started_on + duration

        if ($hasStartedAt && $hasEndedAt && !$hasDuration) {
            // Variant 2: started_at + ended_at
            return;
        }

        if ($hasStartedAt && $hasDuration && !$hasEndedAt) {
            // Variant 1: started_at + duration
            return;
        }

        if ($hasStartedOn && $hasDuration && !$hasStartedAt && !$hasEndedAt) {
            // Variant 3: started_on + duration
            return;
        }

        throw new InvalidArgumentException(
            'Invalid time tracking variant. Must provide either: ' .
            '1) started_at + duration, ' .
            '2) started_at + ended_at, or ' .
            '3) started_on + duration'
        );
    }

    /**
     * Validate subject structure
     */
    protected function validateSubject(array $subject): void
    {
        if (!isset($subject['id']) || !isset($subject['type'])) {
            throw new InvalidArgumentException('Subject must contain both id and type');
        }

        if (!$this->isValidUuid($subject['id'])) {
            throw new InvalidArgumentException('Subject id must be a valid UUID');
        }

        if (!in_array($subject['type'], $this->availableSubjectTypes)) {
            throw new InvalidArgumentException(
                'Invalid subject type. Must be one of: ' . implode(', ', $this->availableSubjectTypes)
            );
        }
    }

    /**
     * Build filters array for the API request
     */
    protected function applyFilters(array $params = [], array $filters = [])
    {
        if (empty($filters)) {
            return $params;
        }

        $apiFilters = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }

            switch ($key) {
                case 'ids':
                    if (is_array($value)) {
                        $apiFilters['ids'] = $value;
                    }
                    break;

                case 'user_id':
                case 'started_after':
                case 'started_before':
                case 'ended_after':
                case 'ended_before':
                    $apiFilters[$key] = $value;
                    break;

                case 'subject':
                    if (is_array($value) && isset($value['id']) && isset($value['type'])) {
                        $apiFilters['subject'] = $value;
                    }
                    break;

                case 'subject_types':
                    if (is_array($value)) {
                        $apiFilters['subject_types'] = $value;
                    }
                    break;

                case 'relates_to':
                    if (is_array($value) && isset($value['id']) && isset($value['type'])) {
                        $apiFilters['relates_to'] = $value;
                    }
                    break;

                default:
                    // Pass through any other filters as-is
                    $apiFilters[$key] = $value;
                    break;
            }
        }

        if (!empty($apiFilters)) {
            $params['filter'] = $apiFilters;
        }

        return $params;
    }

    /**
     * Apply sorting to the API request
     */
    protected function applySorting(array $params, ?string $field, string $order = 'asc'): array
    {
        if (!$field) {
            return $params;
        }

        // Only starts_on is supported for sorting according to API docs
        $validSortFields = ['starts_on'];

        if (!in_array($field, $validSortFields)) {
            throw new InvalidArgumentException(
                'Invalid sort field. Only "starts_on" is supported'
            );
        }

        $params['sort'] = [
            [
                'field' => $field,
                'order' => strtolower($order) === 'desc' ? 'desc' : 'asc'
            ]
        ];

        return $params;
    }

    /**
     * Helper: Check if string is valid UUID
     */
    protected function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
