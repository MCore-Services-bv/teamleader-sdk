<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use DateTime;
use Exception;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class ClosingDays extends Resource
{
    protected string $description = 'Manage closing days in Teamleader Focus';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = false;    // No update endpoint in API

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsSideloading = false; // No includes mentioned in API docs

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = false;    // No sorting mentioned in API docs

    protected bool $supportsPagination = true;

    // This resource sends includes=pagination on every list call, so the API
    // returns a meta block carrying the total match count
    protected bool $requestsPaginationMeta = true;

    // Available includes for sideloading (none for closing days)
    protected array $availableIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'date_before' => 'End of the period for which to return closing days (inclusive)',
        'date_after' => 'Start of the period for which to return closing days (inclusive)',
    ];

    // Usage examples specific to closing days
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all closing days',
            'code' => '$closingDays = $teamleader->closingDays()->list();',
        ],
        'list_filtered' => [
            'description' => 'Get closing days within a date range',
            'code' => '$closingDays = $teamleader->closingDays()->list([\'date_after\' => \'2023-12-01\', \'date_before\' => \'2023-12-31\']);',
        ],
        'add_closing_day' => [
            'description' => 'Add a new closing day',
            'code' => '$result = $teamleader->closingDays()->create([\'day\' => \'2024-02-01\']);',
        ],
        'delete_closing_day' => [
            'description' => 'Delete a closing day',
            'code' => '$result = $teamleader->closingDays()->delete(\'closing-day-uuid\');',
        ],
        'current_month' => [
            'description' => 'Get closing days for current month',
            'code' => '$closingDays = $teamleader->closingDays()->forMonth(date(\'Y-m\'));',
        ],
    ];

    /**
     * Delete a closing day
     *
     * @param  string  $id  Closing day UUID
     */
    public function delete($id, ...$additionalParams): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Closing day ID is required for deletion');
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Get the base path for the closing days resource
     */
    protected function getBasePath(): string
    {
        return 'closingDays';
    }

    /**
     * Get closing days for a specific month
     *
     * @param  string  $yearMonth  Format: YYYY-MM
     */
    public function forMonth(string $yearMonth): array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            throw new InvalidArgumentException('Month format must be YYYY-MM');
        }

        $startDate = $yearMonth.'-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        return $this->list([
            'date_after' => $startDate,
            'date_before' => $endDate,
        ]);
    }

    /**
     * List closing days with enhanced filtering and pagination
     *
     * Pagination metadata is always requested. The API only returns
     * `meta.matches` when `includes=pagination` is sent, it costs nothing extra,
     * and without it there is no total count — the only end-of-list signal would
     * be a page shorter than the requested page size.
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $this->rejectUnsupportedListArguments([], $options);

        $params = [];

        // Apply filters
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

        // Request pagination metadata.
        //
        // This was previously sent as `include` (singular) and only when the
        // caller passed include_pagination. The API silently ignores the
        // singular key — the same defect fixed SDK-wide in v1.2.3 — so the
        // option never worked. The `include_pagination` option is now redundant
        // but harmless; metadata is always requested.
        $params['includes'] = 'pagination';

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build filters array for the API request
     *
     * Unknown filter keys throw rather than being dropped. The API ignores
     * filter keys it does not recognise and answers 200 with the full
     * unfiltered set, so a mistyped key silently returns every closing day.
     *
     * @throws InvalidArgumentException When a filter key is not supported
     */
    protected function buildFilters(array $filters): array
    {
        $unknown = array_diff(array_keys($filters), array_keys($this->commonFilters));

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key')
                .' for closingDays.list: '.implode(', ', $unknown)
                .'. Supported: '.implode(', ', array_keys($this->commonFilters)).'.'
            );
        }

        $apiFilters = [];

        // Handle date_before filter
        if (isset($filters['date_before'])) {
            if (! $this->isValidDate($filters['date_before'])) {
                throw new InvalidArgumentException('date_before must be in YYYY-MM-DD format');
            }
            $apiFilters['date_before'] = $filters['date_before'];
        }

        // Handle date_after filter
        if (isset($filters['date_after'])) {
            if (! $this->isValidDate($filters['date_after'])) {
                throw new InvalidArgumentException('date_after must be in YYYY-MM-DD format');
            }
            $apiFilters['date_after'] = $filters['date_after'];
        }

        return $apiFilters;
    }

    /**
     * Validate date format
     */
    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Get closing days for a specific year
     *
     * @param  int|string  $year
     */
    public function forYear($year): array
    {
        if (! is_numeric($year) || $year < 1900 || $year > 2100) {
            throw new InvalidArgumentException('Year must be a valid 4-digit year');
        }

        return $this->list([
            'date_after' => $year.'-01-01',
            'date_before' => $year.'-12-31',
        ]);
    }

    /**
     * Get closing days within a date range
     *
     * @param  string  $startDate  Start date (YYYY-MM-DD)
     * @param  string  $endDate  End date (YYYY-MM-DD)
     */
    public function forDateRange(string $startDate, string $endDate): array
    {
        if (! $this->isValidDate($startDate) || ! $this->isValidDate($endDate)) {
            throw new InvalidArgumentException('Both dates must be in YYYY-MM-DD format');
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            throw new InvalidArgumentException('Start date must be before or equal to end date');
        }

        return $this->list([
            'date_after' => $startDate,
            'date_before' => $endDate,
        ]);
    }

    /**
     * Get upcoming closing days (from today forward)
     *
     * @param  int  $daysAhead  Number of days to look ahead (default: 30)
     */
    public function upcoming(int $daysAhead = 30): array
    {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime("+{$daysAhead} days"));

        return $this->list([
            'date_after' => $today,
            'date_before' => $futureDate,
        ]);
    }

    /**
     * Check if a specific date is a closing day
     *
     * @param  string  $date  Date in YYYY-MM-DD format
     */
    public function isClosingDay(string $date): bool
    {
        if (! $this->isValidDate($date)) {
            throw new InvalidArgumentException('Date must be in YYYY-MM-DD format');
        }

        $result = $this->list([
            'date_after' => $date,
            'date_before' => $date,
        ]);

        return ! empty($result['data']) && count($result['data']) > 0;
    }

    /**
     * Get available filter fields
     */
    public function getAvailableFilters(): array
    {
        return [
            'date_before' => 'End of the period (inclusive)',
            'date_after' => 'Start of the period (inclusive)',
        ];
    }

    /**
     * Bulk add multiple closing days
     *
     * @param  array  $dates  Array of dates in YYYY-MM-DD format
     * @return array Results of all create operations
     */
    public function bulkAdd(array $dates): array
    {
        $results = [];

        foreach ($dates as $date) {
            try {
                $results[] = $this->add($date);
            } catch (Exception $e) {
                $results[] = [
                    'error' => true,
                    'date' => $date,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Add a closing day (alias for create)
     *
     * @param  string  $day  Date in YYYY-MM-DD format
     */
    public function add(string $day): array
    {
        return $this->create(['day' => $day]);
    }

    /**
     * Add a closing day (create method override)
     *
     * @param  array  $data  Data containing 'day' field
     */
    public function create(array $data): array
    {
        // Validate required field
        if (! isset($data['day']) || empty($data['day'])) {
            throw new InvalidArgumentException('The "day" field is required to create a closing day');
        }

        // Validate date format
        if (! $this->isValidDate($data['day'])) {
            throw new InvalidArgumentException('The "day" field must be a valid date in YYYY-MM-DD format');
        }

        return $this->api->request('POST', $this->getBasePath().'.add', $data);
    }

    /**
     * Get holidays for common countries (convenience method)
     * Note: This would typically be combined with external holiday APIs
     *
     * @param  string  $country  Country code (for future extension)
     * @return array Suggested dates for common holidays
     */
    public function getCommonHolidays(int $year, string $country = 'BE'): array
    {
        // Belgium only. The $country parameter is accepted for forward
        // compatibility but is not yet used — every date below is Belgian.
        $holidays = [
            'New Year\'s Day' => $year.'-01-01',
            'Christmas Day' => $year.'-12-25',
            'Boxing Day' => $year.'-12-26',
        ];

        // Easter Monday needs ext-calendar, which is not present in every PHP
        // build. Without it this method used to raise
        // "Call to undefined function easter_date()"; the fixed dates above are
        // still returned.
        if (function_exists('easter_date')) {
            $holidays['Easter Monday'] = date('Y-m-d', easter_date($year) + 86400);
        }

        return $holidays;
    }

    /**
     * Override the default validation
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        if ($operation === 'create') {
            // Validate required fields for creation
            if (! isset($data['day'])) {
                throw new InvalidArgumentException('The "day" field is required');
            }

            if (! $this->isValidDate($data['day'])) {
                throw new InvalidArgumentException('The "day" field must be a valid date in YYYY-MM-DD format');
            }
        }

        return parent::validateData($data, $operation);
    }

    /**
     * Override getSuggestedIncludes as closing days don't have includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Closing days don't have sideloadable relationships
    }
}
