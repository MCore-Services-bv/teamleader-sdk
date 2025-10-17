<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use McoreServices\TeamleaderSDK\Resources\Resource;

class ActivityTypes extends Resource
{
    protected string $description = 'Manage activity types in Teamleader Focus Calendar';

    // Resource capabilities - ActivityTypes are read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none for activity types)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of activity type UUIDs',
    ];

    // Usage examples specific to activity types
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all activity types',
            'code' => '$activityTypes = $teamleader->activityTypes()->list();',
        ],
        'filter_by_ids' => [
            'description' => 'Get specific activity types by IDs',
            'code' => '$activityTypes = $teamleader->activityTypes()->byIds(["uuid1", "uuid2"]);',
        ],
        'find_by_name' => [
            'description' => 'Find activity type by name',
            'code' => '$activityType = $teamleader->activityTypes()->findByName("Meeting");',
        ],
    ];

    /**
     * Get the base path for the activity types resource
     */
    protected function getBasePath(): string
    {
        return 'activityTypes';
    }

    /**
     * List activity types with filtering and pagination
     */
    protected function buildQueryParams(
        array $baseParams = [],
        array $filters = [],
        $sort = null,
        string $sortOrder = 'asc',
        int $pageSize = 20,
        int $pageNumber = 1,
        $includes = null
    ): array {
        $params = $baseParams;

        // Build filter object
        if (! empty($filters)) {
            $params['filter'] = [];

            if (isset($filters['ids']) && is_array($filters['ids'])) {
                $params['filter']['ids'] = $filters['ids'];
            }
        }

        // Build page object
        $params['page'] = [
            'size' => $pageSize,
            'number' => $pageNumber,
        ];

        return $params;
    }

    /**
     * Get activity types by specific IDs
     */
    public function byIds(array $ids, array $options = []): array
    {
        return $this->list(
            array_merge(['ids' => $ids], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Find activity type by name (case-insensitive search)
     */
    public function findByName(string $name, array $options = []): ?array
    {
        $response = $this->list([], $options);

        if (isset($response['error']) && $response['error']) {
            return null;
        }

        $activityTypes = $response['data'] ?? [];

        foreach ($activityTypes as $activityType) {
            if (isset($activityType['name']) &&
                strcasecmp($activityType['name'], $name) === 0) {
                return $activityType;
            }
        }

        return null;
    }

    /**
     * Get all activity types (convenience method)
     */
    public function all(array $options = []): array
    {
        // Get a large page size to fetch all activity types at once
        $options['page_size'] = $options['page_size'] ?? 100;

        return $this->list([], $options);
    }

    /**
     * Check if activity type exists by ID
     */
    public function exists(string $id): bool
    {
        $response = $this->byIds([$id]);

        return ! empty($response['data']) && count($response['data']) > 0;
    }

    /**
     * Get activity types as select options for forms
     */
    public function selectOptions(array $options = []): array
    {
        $response = $this->all($options);

        if (isset($response['error']) && $response['error']) {
            return [];
        }

        $activityTypes = $response['data'] ?? [];
        $selectOptions = [];

        foreach ($activityTypes as $activityType) {
            $selectOptions[] = [
                'value' => $activityType['id'],
                'label' => $activityType['name'],
            ];
        }

        return $selectOptions;
    }
}
