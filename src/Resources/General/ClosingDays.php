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
            'code' => '$closingDays = $teamleader->closingDays()->list();'
        ],
        'list_filtered' => [
            'description' => 'Get closing days within a date range',
            'code' => '$closingDays = $teamleader->closingDays()->list([\'date_after\' => \'2023-12-01\', \'date_before\' => \'2023-12-31\']);'
        ],
        'add_closing_day' => [
            'description' => 'Add a new closing day',
            'code' => '$result = $teamleader->closingDays()->create([\'day\' => \'2024-02-01\']);'
        ],
        'delete_closing_day' => [
            'description' => 'Delete a closing day',
            'code' => '$result = $teamleader->closingDays()->delete(\'closing-day-uuid\');'
        ],
        'current_month' => [
            'description' => 'Get closing days for current month',
            'code' => '$closingDays = $teamleader->closingDays()->forMonth(date(\'Y-m\'));'
        ]
    ];

    /**
     * Delete a closing day
     *
     * @param string $id Closing day UUID
     * @return array
     */
    public function delete($id, ...$additionalParams): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Closing day ID is required for deletion');
        }

        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
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
     * @param string $yearMonth Format: YYYY-MM
     * @return array
     */
    public function forMonth(string $yearMonth): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            throw new InvalidArgumentException('Month format must be YYYY-MM');
        }

        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        return $this->list([
            'date_after' => $startDate,
            'date_before' => $endDate
        ]);
    }

    /**
     * List closing days with enhanced filtering and pagination
     *
     * @param array $filters Filters to apply
     * @param array $options Additional options (pagination)
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
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

        // Add pagination to includes if requested
        if (isset($options['include_pagination']) && $options['include_pagination']) {
            $params['include'] = 'pagination';
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Build filters array for the API request
     *
     * @param array $filters
     * @return array
     */
    private function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle date_before filter
        if (isset($filters['date_before'])) {
            if (!$this->isValidDate($filters['date_before'])) {
                throw new InvalidArgumentException('date_before must be in YYYY-MM-DD format');
            }
            $apiFilters['date_before'] = $filters['date_before'];
        }

        // Handle date_after filter
        if (isset($filters['date_after'])) {
            if (!$this->isValidDate($filters['date_after'])) {
                throw new InvalidArgumentException('date_after must be in YYYY-MM-DD format');
            }
            $apiFilters['date_after'] = $filters['date_after'];
        }

        return $apiFilters;
    }

    /**
     * Validate date format
     *
     * @param string $date
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Get closing days for a specific year
     *
     * @param int|string $year
     * @return array
     */
    public function forYear($year): array
    {
        if (!is_numeric($year) || $year < 1900 || $year > 2100) {
            throw new InvalidArgumentException('Year must be a valid 4-digit year');
        }

        return $this->list([
            'date_after' => $year . '-01-01',
            'date_before' => $year . '-12-31'
        ]);
    }

    /**
     * Get closing days within a date range
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array
     */
    public function forDateRange(string $startDate, string $endDate): array
    {
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            throw new InvalidArgumentException('Both dates must be in YYYY-MM-DD format');
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            throw new InvalidArgumentException('Start date must be before or equal to end date');
        }

        return $this->list([
            'date_after' => $startDate,
            'date_before' => $endDate
        ]);
    }

    /**
     * Get upcoming closing days (from today forward)
     *
     * @param int $daysAhead Number of days to look ahead (default: 30)
     * @return array
     */
    public function upcoming(int $daysAhead = 30): array
    {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime("+{$daysAhead} days"));

        return $this->list([
            'date_after' => $today,
            'date_before' => $futureDate
        ]);
    }

    /**
     * Check if a specific date is a closing day
     *
     * @param string $date Date in YYYY-MM-DD format
     * @return bool
     */
    public function isClosingDay(string $date): bool
    {
        if (!$this->isValidDate($date)) {
            throw new InvalidArgumentException('Date must be in YYYY-MM-DD format');
        }

        $result = $this->list([
            'date_after' => $date,
            'date_before' => $date
        ]);

        return !empty($result['data']) && count($result['data']) > 0;
    }

    /**
     * Get available filter fields
     *
     * @return array
     */
    public function getAvailableFilters(): array
    {
        return [
            'date_before' => 'End of the period (inclusive)',
            'date_after' => 'Start of the period (inclusive)'
        ];
    }

    /**
     * Bulk add multiple closing days
     *
     * @param array $dates Array of dates in YYYY-MM-DD format
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
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Add a closing day (alias for create)
     *
     * @param string $day Date in YYYY-MM-DD format
     * @return array
     */
    public function add(string $day): array
    {
        return $this->create(['day' => $day]);
    }

    /**
     * Add a closing day (create method override)
     *
     * @param array $data Data containing 'day' field
     * @return array
     */
    public function create(array $data): array
    {
        // Validate required field
        if (!isset($data['day']) || empty($data['day'])) {
            throw new InvalidArgumentException('The "day" field is required to create a closing day');
        }

        // Validate date format
        if (!$this->isValidDate($data['day'])) {
            throw new InvalidArgumentException('The "day" field must be a valid date in YYYY-MM-DD format');
        }

        return $this->api->request('POST', $this->getBasePath() . '.add', $data);
    }

    /**
     * Get holidays for common countries (convenience method)
     * Note: This would typically be combined with external holiday APIs
     *
     * @param int $year
     * @param string $country Country code (for future extension)
     * @return array Suggested dates for common holidays
     */
    public function getCommonHolidays(int $year, string $country = 'BE'): array
    {
        // Basic holidays for Belgium (can be extended)
        $holidays = [
            'New Year\'s Day' => $year . '-01-01',
            'Christmas Day' => $year . '-12-25',
            'Boxing Day' => $year . '-12-26',
        ];

        // Add Easter-based holidays (simplified calculation)
        $easter = easter_date($year);
        $holidays['Easter Monday'] = date('Y-m-d', $easter + 86400);

        return $holidays;
    }

    /**
     * Override the default validation
     *
     * @param array $data
     * @param string $operation
     * @return array
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        if ($operation === 'create') {
            // Validate required fields for creation
            if (!isset($data['day'])) {
                throw new InvalidArgumentException('The "day" field is required');
            }

            if (!$this->isValidDate($data['day'])) {
                throw new InvalidArgumentException('The "day" field must be a valid date in YYYY-MM-DD format');
            }
        }

        return parent::validateData($data, $operation);
    }

    /**
     * Override getSuggestedIncludes as closing days don't have includes
     *
     * @return array
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Closing days don't have sideloadable relationships
    }
}
