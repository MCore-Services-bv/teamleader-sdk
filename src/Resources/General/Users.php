<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Users extends Resource
{
    use ValidatesWritePayload;

    /** `filter.status[]` on users.list */
    public const STATUSES = ['active', 'deactivated'];

    /** Includes users.info accepts; users.list takes none */
    public const INFO_INCLUDES = ['external_rate'];

    /** Filter keys users.listDaysOff accepts */
    public const DAYS_OFF_FILTERS = ['starts_after', 'ends_before'];

    protected string $description = 'Manage users in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = false;  // Based on API docs, no create endpoint

    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint

    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint

    protected bool $supportsBatch = false;

    // Available includes for sideloading
    /**
     * A flat list since v2.2.14; it was a name => description map, the only
     * resource that declared its includes that way.
     */
    protected array $availableIncludes = [];

    /** users.info takes `external_rate`; users.list takes no includes */
    protected array $infoIncludes = self::INFO_INCLUDES;

    protected bool $supportsSideloading = true;

    /**
     * Sort fields users.list accepts. Until v2.2.14 the sort was passed
     * through unchecked.
     */
    protected array $availableSortFields = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'email' => 'Email address',
        'function' => 'Function',
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
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'sort', 'sort_order']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'users.list does not support: '.implode(', ', $unknown)
                .'. Supported: page_size, page_number, sort, sort_order. Includes go on info().'
            );
        }

        $params = [];

        if ($filters !== []) {
            $params['filter'] = $this->buildFilters($filters);
        }

        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get one user
     *
     * @param  string  $id  User UUID
     * @param  mixed  $includes  external_rate
     *
     * @throws InvalidArgumentException On an include users.info does not accept
     */
    public function info($id, $includes = null): array
    {
        $pending = $this->getPendingIncludes();
        $this->pendingIncludes = [];

        $includes = $this->assertIncludes([...(array) ($includes ?? []), ...$pending], self::INFO_INCLUDES, 'users.info');

        return $this->api->request('POST', $this->getBasePath().'.info', $this->applyIncludes(['id' => $id], $includes));
    }

    /**
     * Get current authenticated user
     */
    public function me(): array
    {
        return $this->api->request('POST', $this->getBasePath().'.me');
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
        $unknownOptions = array_diff(array_keys($options), ['page_size', 'page_number']);

        if ($unknownOptions !== []) {
            throw new InvalidArgumentException(
                'users.listDaysOff does not support: '.implode(', ', $unknownOptions).'. Supported: page_size, page_number.'
            );
        }

        // Unknown keys were dropped without a word until v2.2.14.
        $unknown = array_diff(array_keys($filters), self::DAYS_OFF_FILTERS);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter key for users.listDaysOff: '.implode(', ', $unknown)
                .'. Supported: '.implode(', ', self::DAYS_OFF_FILTERS).'.'
            );
        }

        $params = ['id' => $id];
        $filter = array_filter($filters, fn ($value) => $value !== null);

        if ($filter !== []) {
            $params['filter'] = $filter;
        }

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        // listDaysOff documents a total count in `meta` with includes=pagination
        $params['includes'] = 'pagination';

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
     * Build the filter object for users.list
     *
     * Until v2.2.14 unknown keys and a string `ids` were dropped without a
     * word, and status values were not checked.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'users.list');

        $apiFilters = [];

        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? array_values($filters['ids']) : [$filters['ids']];
        }

        if (isset($filters['term'])) {
            $apiFilters['term'] = $filters['term'];
        }

        if (isset($filters['status'])) {
            $apiFilters['status'] = is_array($filters['status']) ? array_values($filters['status']) : [$filters['status']];

            foreach ($apiFilters['status'] as $index => $status) {
                $this->assertEnum($status, self::STATUSES, "filter.status[{$index}]", 'users.list');
            }
        }

        return $apiFilters;
    }

    /**
     * Build the sort array for users.list
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
     * Get available sort fields for users (based on API documentation)
     */
    public function getAvailableSortFields(): array
    {
        return $this->availableSortFields;
    }

    /**
     * Get available status values for filtering
     */
    public function getAvailableStatuses(): array
    {
        return self::STATUSES;
    }

    /**
     * Override getSuggestedIncludes for users
     */
    protected function getSuggestedIncludes(): array
    {
        return ['external_rate'];
    }
}
