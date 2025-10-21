<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use McoreServices\TeamleaderSDK\Resources\Resource;

class Users extends Resource
{
    protected string $description = 'Manage users in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = false;  // Based on API docs, no create endpoint

    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint

    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint

    protected bool $supportsBatch = false;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'external_rate' => 'Include external hourly rates for the user',
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of user UUIDs to filter by',
        'term' => 'Search filter on first name, last name, email and function',
        'status' => 'Filter by user status (active, deactivated)',
    ];

    // Usage examples specific to users
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all users',
            'code' => '$users = $teamleader->users()->list();',
        ],
        'list_active' => [
            'description' => 'Get only active users',
            'code' => '$users = $teamleader->users()->list([\'status\' => [\'active\']]);',
        ],
        'search_users' => [
            'description' => 'Search users by term',
            'code' => '$users = $teamleader->users()->list([\'term\' => \'John\']);',
        ],
        'sorted_list' => [
            'description' => 'Get users sorted by first name',
            'code' => '$users = $teamleader->users()->list([], [\'sort\' => [[\'field\' => \'first_name\', \'order\' => \'asc\']]]);',
        ],
        'get_single' => [
            'description' => 'Get a single user with external rate',
            'code' => '$user = $teamleader->users()->info(\'user-uuid-here\', \'external_rate\');',
        ],
        'get_current_user' => [
            'description' => 'Get current authenticated user',
            'code' => '$currentUser = $teamleader->users()->me();',
        ],
        'get_user_schedule' => [
            'description' => 'Get user week schedule',
            'code' => '$schedule = $teamleader->users()->getWeekSchedule(\'user-uuid-here\');',
        ],
        'get_user_days_off' => [
            'description' => 'Get user days off',
            'code' => '$daysOff = $teamleader->users()->listDaysOff(\'user-uuid-here\', [\'starts_after\' => \'2023-10-01\']);',
        ],
    ];

    /**
     * Get the base path for the users resource
     */
    protected function getBasePath(): string
    {
        return 'users';
    }

    /**
     * List users with enhanced filtering and sorting
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (sorting, pagination)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get user information
     *
     * @param  string  $id  User UUID
     * @param  mixed  $includes  Includes (external_rate)
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        // Handle includes
        if (! empty($includes)) {
            if (is_array($includes)) {
                $params['includes'] = implode(',', $includes);
            } else {
                $params['includes'] = $includes;
            }
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Get current authenticated user
     */
    public function me(): array
    {
        return $this->api->request('POST', $this->getBasePath().'.me');
    }

    /**
     * Get user week schedule
     * Only available with the Weekly working schedule feature
     *
     * @param  string  $id  User UUID
     */
    public function getWeekSchedule(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.getWeekSchedule', [
            'id' => $id,
        ]);
    }

    /**
     * List user days off
     *
     * @param  string  $id  User UUID
     * @param  array  $filters  Filter options (starts_after, ends_before)
     * @param  array  $options  Pagination options
     */
    public function listDaysOff(string $id, array $filters = [], array $options = []): array
    {
        $params = ['id' => $id];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = [];

            if (isset($filters['starts_after'])) {
                $params['filter']['starts_after'] = $filters['starts_after'];
            }

            if (isset($filters['ends_before'])) {
                $params['filter']['ends_before'] = $filters['ends_before'];
            }
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.listDaysOff', $params);
    }

    /**
     * Get active users only
     */
    public function active(): array
    {
        return $this->list(['status' => ['active']]);
    }

    /**
     * Get deactivated users only
     */
    public function deactivated(): array
    {
        return $this->list(['status' => ['deactivated']]);
    }

    /**
     * Search users by term
     *
     * @param  string  $term  Search term
     */
    public function search(string $term): array
    {
        return $this->list(['term' => $term]);
    }

    /**
     * Get users by specific IDs
     *
     * @param  array  $ids  Array of user UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Include external rate in response
     *
     * @return static
     */
    public function withExternalRate()
    {
        return $this->with('external_rate');
    }

    /**
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle IDs filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        // Handle term filter
        if (isset($filters['term'])) {
            $apiFilters['term'] = $filters['term'];
        }

        // Handle status filter
        if (isset($filters['status'])) {
            if (is_string($filters['status'])) {
                $apiFilters['status'] = [$filters['status']];
            } elseif (is_array($filters['status'])) {
                $apiFilters['status'] = $filters['status'];
            }
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     *
     * @param array|string $sort
     * @param string $order
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        // If already in correct format, return as-is
        if (is_array($sort) && isset($sort[0]['field'])) {
            return $sort;
        }

        // Handle simple string sort
        if (is_string($sort)) {
            return [
                [
                    'field' => $sort,
                    'order' => 'asc',
                ],
            ];
        }

        // Handle associative array
        if (is_array($sort)) {
            $sortArray = [];
            foreach ($sort as $field => $order) {
                if (is_numeric($field) && is_array($order)) {
                    // Already in correct format
                    $sortArray[] = $order;
                } else {
                    $sortArray[] = [
                        'field' => $field,
                        'order' => $order,
                    ];
                }
            }

            return $sortArray;
        }

        return [];
    }

    /**
     * Get available sort fields for users (based on API documentation)
     */
    public function getAvailableSortFields(): array
    {
        return [
            'first_name' => 'Sort by first name',
            'last_name' => 'Sort by last name',
            'email' => 'Sort by email address',
            'function' => 'Sort by user function/role',
        ];
    }

    /**
     * Get available status values for filtering
     */
    public function getAvailableStatuses(): array
    {
        return ['active', 'deactivated'];
    }

    /**
     * Override getSuggestedIncludes for users
     */
    protected function getSuggestedIncludes(): array
    {
        return ['external_rate'];
    }
}
