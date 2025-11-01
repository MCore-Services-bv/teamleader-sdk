<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use BadMethodCallException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Teams extends Resource
{
    protected string $description = 'Manage teams in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsPagination = false;  // Based on API docs, no pagination

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = true;

    protected bool $supportsSideloading = false;  // No includes mentioned

    protected bool $supportsCreation = false;    // Only list endpoint available

    protected bool $supportsUpdate = false;      // Only list endpoint available

    protected bool $supportsDeletion = false;    // Only list endpoint available

    protected bool $supportsBatch = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of team UUIDs to filter by',
        'term' => 'Filter by team name',
        'team_lead_id' => 'Filter teams by team leader user ID',
    ];

    // Usage examples specific to teams
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all teams',
            'code' => '$teams = $teamleader->teams()->list();',
        ],
        'search_teams' => [
            'description' => 'Search teams by name',
            'code' => '$teams = $teamleader->teams()->list([\'term\' => \'Designers\']);',
        ],
        'teams_by_lead' => [
            'description' => 'Get teams by team leader',
            'code' => '$teams = $teamleader->teams()->list([\'team_lead_id\' => \'user-uuid\']);',
        ],
        'sorted_teams' => [
            'description' => 'Get teams sorted by name',
            'code' => '$teams = $teamleader->teams()->list([], [\'sort\' => [[\'field\' => \'name\', \'order\' => \'asc\']]]);',
        ],
        'specific_teams' => [
            'description' => 'Get specific teams by ID',
            'code' => '$teams = $teamleader->teams()->list([\'ids\' => [\'team-uuid-1\', \'team-uuid-2\']]);',
        ],
    ];

    /**
     * Search teams by name
     *
     * @param  string  $term  Search term
     */
    public function search(string $term): array
    {
        return $this->list(['term' => $term]);
    }

    /**
     * List teams with filtering and sorting
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (sorting)
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

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
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

        // Handle term filter (name search)
        if (isset($filters['term'])) {
            $apiFilters['term'] = $filters['term'];
        }

        // Handle team lead filter
        if (isset($filters['team_lead_id'])) {
            $apiFilters['team_lead_id'] = $filters['team_lead_id'];
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     *
     * @param  array|string  $sort
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
     * Get the base path for the teams resource
     */
    protected function getBasePath(): string
    {
        return 'teams';
    }

    /**
     * Get teams by specific IDs
     *
     * @param  array  $ids  Array of team UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get teams by team leader
     *
     * @param  string  $teamLeadId  User UUID of the team leader
     */
    public function byTeamLead(string $teamLeadId): array
    {
        return $this->list(['team_lead_id' => $teamLeadId]);
    }

    /**
     * Get teams sorted by name
     *
     * @param  string  $order  Sort order (asc or desc)
     */
    public function sortedByName(string $order = 'asc'): array
    {
        return $this->list([], [
            'sort' => [
                [
                    'field' => 'name',
                    'order' => $order,
                ],
            ],
        ]);
    }

    /**
     * Get available sort fields for teams (based on API documentation)
     */
    public function getAvailableSortFields(): array
    {
        return [
            'name' => 'Sort by team name',
        ];
    }

    /**
     * Override info method with compatible signature but throw exception
     */
    public function info($id, $includes = null)
    {
        throw new BadMethodCallException('The teams resource does not support individual team info retrieval. Use list() method instead.');
    }

    /**
     * Override create method with compatible signature but throw exception
     */
    public function create(array $data)
    {
        throw new BadMethodCallException('The teams resource does not support team creation via API.');
    }

    /**
     * Override update method with compatible signature but throw exception
     */
    public function update($id, array $data)
    {
        throw new BadMethodCallException('The teams resource does not support team updates via API.');
    }

    /**
     * Override delete method with compatible signature but throw exception
     * Fixed signature to match parent class
     */
    public function delete($id, ...$additionalParams): array
    {
        throw new BadMethodCallException('The teams resource does not support team deletion via API.');
    }

    /**
     * Override the default validation since teams have limited operations
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Teams are read-only in this API, so no validation needed for create/update
        return $data;
    }

    /**
     * Override getSuggestedIncludes as teams don't have includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Teams don't have sideloadable relationships in the API
    }
}
