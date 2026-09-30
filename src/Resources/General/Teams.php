<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use BadMethodCallException;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Teams extends Resource
{
    use ValidatesWritePayload;

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

    /**
     * Sort fields teams.list accepts. Until v2.2.14 the sort was passed
     * through unchecked.
     */
    protected array $availableSortFields = [
        'name' => 'Team name',
    ];

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
        $unknown = array_diff(array_keys($options), ['sort', 'sort_order']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'teams.list does not support: '.implode(', ', $unknown).'. Supported: sort, sort_order.'
            );
        }

        $params = [];

        if ($filters !== []) {
            $params['filter'] = $this->buildFilters($filters);
        }

        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build the filter object for teams.list
     *
     * Until v2.2.14 unknown keys and a string `ids` were dropped without a word.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'teams.list');

        if (isset($filters['ids']) && ! is_array($filters['ids'])) {
            $filters['ids'] = [$filters['ids']];
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }

    /**
     * Build the sort array for teams.list (name only)
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
        return $this->availableSortFields;
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
