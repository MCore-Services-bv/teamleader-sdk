<?php

namespace McoreServices\TeamleaderSDK\Resources\Planning;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Planning\Concerns\ChecksPlanningFilters;
use McoreServices\TeamleaderSDK\Resources\Resource;

class UserAvailability extends Resource
{
    use ChecksPlanningFilters;

    /** Top-level keys daily() and total() accept; page_size / page_number are shorthand for `page` */
    public const PARAM_KEYS = ['period', 'filter', 'page', 'page_size', 'page_number'];

    protected string $description = 'Retrieve user availability information from Teamleader Focus';

    // Resource capabilities — read-only, no CRUD
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

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
        'assignees' => 'Filter by assignees (array of objects with type and id)',
    ];

    // Valid assignee types
    protected array $assigneeTypes = [
        'team',
        'user',
    ];

    // Maximum allowed period durations
    protected int $maxDailyDays = 100;

    protected int $maxTotalDays = 20000;

    // Usage examples specific to user availability
    protected array $usageExamples = [
        'daily_all_users' => [
            'description' => 'Get daily availability for all users over a date range',
            'code' => '$availability = $teamleader->userAvailability()->daily([
    \'period\' => [
        \'start_date\' => \'2024-01-01\',
        \'end_date\'   => \'2024-01-07\',
    ],
]);',
        ],
        'daily_filtered_by_user' => [
            'description' => 'Get daily availability for specific users',
            'code' => '$availability = $teamleader->userAvailability()->daily([
    \'period\' => [
        \'start_date\' => \'2024-01-01\',
        \'end_date\'   => \'2024-01-07\',
    ],
    \'filter\' => [
        \'assignees\' => [
            [\'type\' => \'user\', \'id\' => \'66abace2-62af-0836-a927-fe3f44b9b47b\'],
        ],
    ],
]);',
        ],
        'daily_with_pagination' => [
            'description' => 'Get daily availability with pagination',
            'code' => '$availability = $teamleader->userAvailability()->daily([
    \'period\' => [
        \'start_date\' => \'2024-01-01\',
        \'end_date\'   => \'2024-01-31\',
    ],
    \'page\' => [
        \'size\'   => 50,
        \'number\' => 1,
    ],
]);',
        ],
        'total_all_users' => [
            'description' => 'Get total availability for all users over a date range',
            'code' => '$availability = $teamleader->userAvailability()->total([
    \'period\' => [
        \'start_date\' => \'2024-01-01\',
        \'end_date\'   => \'2024-03-31\',
    ],
]);',
        ],
        'total_filtered_by_team' => [
            'description' => 'Get total availability for a specific team',
            'code' => '$availability = $teamleader->userAvailability()->total([
    \'period\' => [
        \'start_date\' => \'2024-01-01\',
        \'end_date\'   => \'2024-12-31\',
    ],
    \'filter\' => [
        \'assignees\' => [
            [\'type\' => \'team\', \'id\' => \'team-uuid\'],
        ],
    ],
]);',
        ],
    ];

    /**
     * Get the base path for the user availability resource
     */
    protected function getBasePath(): string
    {
        return 'userAvailability';
    }

    /**
     * Returns the daily availability for all users.
     *
     * Maximum period duration: 100 days.
     *
     * @param  array  $params  Request parameters
     *                         - period (array, required): Period object with start_date and end_date (YYYY-MM-DD)
     *                         - filter (array, optional): Filter object
     *                         - assignees (array, optional): Array of assignee objects with type ('user'|'team') and id
     *                         - page (array, optional): Pagination object with size (default: 20) and number (default: 1)
     *
     * @throws InvalidArgumentException
     */
    public function daily(array $params): array
    {
        $this->validateParams($params, 'daily');

        $body = $this->buildRequestBody($params);

        return $this->api->request('POST', $this->getBasePath().'.daily', $body);
    }

    /**
     * Returns the total availability for all users.
     *
     * Maximum period duration: 20,000 days.
     *
     * @param  array  $params  Request parameters
     *                         - period (array, required): Period object with start_date and end_date (YYYY-MM-DD)
     *                         - filter (array, optional): Filter object
     *                         - assignees (array, optional): Array of assignee objects with type ('user'|'team') and id
     *                         - page (array, optional): Pagination object with size (default: 20) and number (default: 1)
     *
     * @throws InvalidArgumentException
     */
    public function total(array $params): array
    {
        $this->validateParams($params, 'total');

        $body = $this->buildRequestBody($params);

        return $this->api->request('POST', $this->getBasePath().'.total', $body);
    }

    /**
     * Convenience method: get daily availability for a specific user
     *
     * @param  string  $userId  User UUID
     * @param  string  $startDate  Start date in YYYY-MM-DD format
     * @param  string  $endDate  End date in YYYY-MM-DD format
     * @param  array  $options  Additional options (page, etc.)
     */
    public function dailyForUser(string $userId, string $startDate, string $endDate, array $options = []): array
    {
        return $this->daily(array_merge([
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'filter' => ['assignees' => [['type' => 'user', 'id' => $userId]]],
        ], $options));
    }

    /**
     * Convenience method: get total availability for a specific user
     *
     * @param  string  $userId  User UUID
     * @param  string  $startDate  Start date in YYYY-MM-DD format
     * @param  string  $endDate  End date in YYYY-MM-DD format
     * @param  array  $options  Additional options (page, etc.)
     */
    public function totalForUser(string $userId, string $startDate, string $endDate, array $options = []): array
    {
        return $this->total(array_merge([
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'filter' => ['assignees' => [['type' => 'user', 'id' => $userId]]],
        ], $options));
    }

    /**
     * Convenience method: get daily availability for a specific team
     *
     * @param  string  $teamId  Team UUID
     * @param  string  $startDate  Start date in YYYY-MM-DD format
     * @param  string  $endDate  End date in YYYY-MM-DD format
     * @param  array  $options  Additional options (page, etc.)
     */
    public function dailyForTeam(string $teamId, string $startDate, string $endDate, array $options = []): array
    {
        return $this->daily(array_merge([
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'filter' => ['assignees' => [['type' => 'team', 'id' => $teamId]]],
        ], $options));
    }

    /**
     * Convenience method: get total availability for a specific team
     *
     * @param  string  $teamId  Team UUID
     * @param  string  $startDate  Start date in YYYY-MM-DD format
     * @param  string  $endDate  End date in YYYY-MM-DD format
     * @param  array  $options  Additional options (page, etc.)
     */
    public function totalForTeam(string $teamId, string $startDate, string $endDate, array $options = []): array
    {
        return $this->total(array_merge([
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'filter' => ['assignees' => [['type' => 'team', 'id' => $teamId]]],
        ], $options));
    }

    /**
     * Build the request body from the given params
     */
    protected function buildRequestBody(array $params): array
    {
        $body = [
            'period' => [
                'start_date' => $params['period']['start_date'],
                'end_date' => $params['period']['end_date'],
            ],
        ];

        if (! empty($params['filter'])) {
            $body['filter'] = $params['filter'];
        }

        $page = $params['page'] ?? [];

        if (isset($params['page_size'])) {
            $page['size'] = $params['page_size'];
        }

        if (isset($params['page_number'])) {
            $page['number'] = $params['page_number'];
        }

        if ($page !== []) {
            $body['page'] = [
                'size' => (int) ($page['size'] ?? 20),
                'number' => (int) ($page['number'] ?? 1),
            ];
        }

        return $body;
    }

    /**
     * Validate request parameters for daily() or total()
     *
     * @param  string  $endpoint  'daily' or 'total'
     *
     * @throws InvalidArgumentException
     */
    protected function validateParams(array $params, string $endpoint): void
    {
        // Unknown keys (top level and inside `filter`) were dropped without a
        // word until v2.2.16, so a mistyped filter returned every user.
        $unknown = array_diff(array_keys($params), self::PARAM_KEYS);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                "userAvailability.{$endpoint} does not accept: ".implode(', ', $unknown)
                .'. Accepted: period, filter, page (or page_size / page_number).'
            );
        }

        if (isset($params['filter'])) {
            if (! is_array($params['filter'])) {
                throw new InvalidArgumentException("filter must be ['assignees' => [...]]");
            }

            $unknownFilters = array_diff(array_keys($params['filter']), ['assignees']);

            if ($unknownFilters !== []) {
                throw new InvalidArgumentException(
                    "Unsupported filter key for userAvailability.{$endpoint}: ".implode(', ', $unknownFilters).'. Supported: assignees.'
                );
            }
        }

        // period is required
        if (empty($params['period']) || ! is_array($params['period'])) {
            throw new InvalidArgumentException('period is required and must be an object');
        }

        if (empty($params['period']['start_date'])) {
            throw new InvalidArgumentException('period.start_date is required');
        }

        if (empty($params['period']['end_date'])) {
            throw new InvalidArgumentException('period.end_date is required');
        }

        $this->validateDateFormat($params['period']['start_date'], 'period.start_date');
        $this->validateDateFormat($params['period']['end_date'], 'period.end_date');

        // Validate period length
        $start = new \DateTime($params['period']['start_date']);
        $end = new \DateTime($params['period']['end_date']);

        if ($end < $start) {
            throw new InvalidArgumentException('period.end_date must be on or after period.start_date');
        }

        $days = (int) $start->diff($end)->days;
        $maxDays = $endpoint === 'daily' ? $this->maxDailyDays : $this->maxTotalDays;

        if ($days > $maxDays) {
            throw new InvalidArgumentException(
                "Period duration exceeds the maximum of {$maxDays} days for {$endpoint} endpoint (requested: {$days} days)"
            );
        }

        // Validate assignees if provided
        if (isset($params['filter']['assignees'])) {
            $this->checkedAssignees($params['filter']['assignees'], "userAvailability.{$endpoint}", false);
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @throws InvalidArgumentException
     */
    protected function validateDateFormat(string $date, string $fieldName): void
    {
        $this->checkedDate($date, $fieldName);
    }

    /**
     * Override list() — not supported for this resource
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new \BadMethodCallException(
            'UserAvailability does not support list(). Use daily() or total() instead.'
        );
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'daily' => [
                'description' => 'Per-user daily availability breakdown for the requested period (HTTP 200)',
                'fields' => [
                    'data' => 'Array of user availability objects',
                    'data[].user' => 'User reference {id, type: user}',
                    'data[].availabilities' => 'Array of daily availability objects',
                    'data[].availabilities[].date' => 'Date (YYYY-MM-DD)',
                    'data[].availabilities[].availability' => 'Availability object',
                    'data[].availabilities[].availability.gross_time_available' => 'Total time based on working hours {unit: minutes, value}',
                    'data[].availabilities[].availability.net_time_available' => 'Gross time minus days off {unit: minutes, value}',
                    'data[].availabilities[].availability.planned_time' => 'Time already planned {unit: minutes, value}',
                    'data[].availabilities[].availability.unplanned_time' => 'Net time minus planned time {unit: minutes, value}',
                ],
            ],
            'total' => [
                'description' => 'Per-user total availability summed over the requested period (HTTP 200)',
                'fields' => [
                    'data' => 'Array of user availability objects',
                    'data[].user' => 'User reference {id, type: user}',
                    'data[].availability' => 'Totalled availability object',
                    'data[].availability.gross_time_available' => 'Total time based on working hours {unit: minutes, value}',
                    'data[].availability.net_time_available' => 'Gross time minus days off {unit: minutes, value}',
                    'data[].availability.planned_time' => 'Time already planned {unit: minutes, value}',
                    'data[].availability.unplanned_time' => 'Net time minus planned time {unit: minutes, value}',
                ],
            ],
        ];
    }
}
