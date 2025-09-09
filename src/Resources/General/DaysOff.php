<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use BadMethodCallException;
use DateTime;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class DaysOff extends Resource
{
    protected string $description = 'Manage days off for users in Teamleader Focus';

    // Resource capabilities - based on API docs, this resource only supports bulk operations
    protected bool $supportsPagination = false;
    protected bool $supportsFiltering = false;
    protected bool $supportsSorting = false;
    protected bool $supportsSideloading = false;
    protected bool $supportsCreation = false;  // Only bulk import supported
    protected bool $supportsUpdate = false;
    protected bool $supportsDeletion = false;  // Only bulk delete supported
    protected bool $supportsBatch = true;      // Main functionality

    // Available includes for sideloading - none based on API docs
    protected array $availableIncludes = [];

    // Common filters - none for this resource
    protected array $commonFilters = [];

    // Usage examples specific to days off
    protected array $usageExamples = [
        'bulk_import' => [
            'description' => 'Import multiple days off for a user',
            'code' => '$result = $teamleader->daysOff()->bulkImport($userId, $leaveTypeId, $days);'
        ],
        'bulk_delete' => [
            'description' => 'Delete multiple days off for a user',
            'code' => '$result = $teamleader->daysOff()->bulkDelete($userId, $dayOffIds);'
        ],
        'single_day_import' => [
            'description' => 'Import a single day off',
            'code' => '$result = $teamleader->daysOff()->importSingleDay($userId, $leaveTypeId, $startsAt, $endsAt);'
        ]
    ];

    /**
     * Delete multiple days off for a user
     *
     * @param string $userId The user ID that owns the days off
     * @param array $dayOffIds Array of day off IDs to delete
     * @return array
     */
    public function bulkDelete(string $userId, array $dayOffIds): array
    {
        $this->validateUserId($userId);
        $this->validateDayOffIds($dayOffIds);

        $data = [
            'user_id' => $userId,
            'ids' => $dayOffIds
        ];

        return $this->api->request('POST', $this->getBasePath() . '.bulkDelete', $data);
    }

    /**
     * Validate user ID format
     */
    private function validateUserId(string $userId): void
    {
        if (empty($userId)) {
            throw new InvalidArgumentException('User ID is required');
        }

        // Check if it looks like a UUID
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $userId)) {
            throw new InvalidArgumentException('User ID must be a valid UUID format');
        }
    }

    /**
     * Validate day off IDs array
     */
    private function validateDayOffIds(array $dayOffIds): void
    {
        if (empty($dayOffIds)) {
            throw new InvalidArgumentException('At least one day off ID must be provided');
        }

        foreach ($dayOffIds as $index => $id) {
            if (!is_string($id) || empty($id)) {
                throw new InvalidArgumentException("Day off ID at index {$index} must be a non-empty string");
            }

            // Check if it looks like a UUID
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                throw new InvalidArgumentException("Day off ID at index {$index} must be a valid UUID format");
            }
        }
    }

    /**
     * Get the base path for the days off resource
     */
    protected function getBasePath(): string
    {
        return 'daysOff';
    }

    /**
     * Import a single day off - convenience method
     *
     * @param string $userId The user ID
     * @param string $leaveTypeId The leave type ID
     * @param string $startsAt Start datetime (ISO 8601 format)
     * @param string $endsAt End datetime (ISO 8601 format)
     * @return array
     */
    public function importSingleDay(string $userId, string $leaveTypeId, string $startsAt, string $endsAt): array
    {
        $days = [
            [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt
            ]
        ];

        return $this->bulkImport($userId, $leaveTypeId, $days);
    }

    /**
     * Import (create) multiple days off for a user
     *
     * @param string $userId The user ID to create days off for
     * @param string $leaveTypeId The leave type ID (from dayOffTypes resource)
     * @param array $days Array of day objects with starts_at and ends_at
     * @return array
     */
    public function bulkImport(string $userId, string $leaveTypeId, array $days): array
    {
        $this->validateUserId($userId);
        $this->validateLeaveTypeId($leaveTypeId);
        $this->validateDays($days);

        $data = [
            'user_id' => $userId,
            'leave_type_id' => $leaveTypeId,
            'days' => $days
        ];

        return $this->api->request('POST', $this->getBasePath() . '.import', $data);
    }

    /**
     * Validate leave type ID format
     */
    private function validateLeaveTypeId(string $leaveTypeId): void
    {
        if (empty($leaveTypeId)) {
            throw new InvalidArgumentException('Leave type ID is required');
        }

        // Check if it looks like a UUID
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $leaveTypeId)) {
            throw new InvalidArgumentException('Leave type ID must be a valid UUID format');
        }
    }

    /**
     * Validate days array format
     */
    private function validateDays(array $days): void
    {
        if (empty($days)) {
            throw new InvalidArgumentException('At least one day must be provided');
        }

        foreach ($days as $index => $day) {
            if (!is_array($day)) {
                throw new InvalidArgumentException("Day at index {$index} must be an array");
            }

            if (!isset($day['starts_at']) || !isset($day['ends_at'])) {
                throw new InvalidArgumentException("Day at index {$index} must have both starts_at and ends_at");
            }

            // Validate datetime format
            if (!$this->isValidDatetime($day['starts_at'])) {
                throw new InvalidArgumentException("Day at index {$index} has invalid starts_at format. Expected ISO 8601 format.");
            }

            if (!$this->isValidDatetime($day['ends_at'])) {
                throw new InvalidArgumentException("Day at index {$index} has invalid ends_at format. Expected ISO 8601 format.");
            }

            // Validate that start is before end
            if (strtotime($day['starts_at']) >= strtotime($day['ends_at'])) {
                throw new InvalidArgumentException("Day at index {$index} has starts_at after or equal to ends_at");
            }
        }
    }

    /**
     * Validate datetime string format
     */
    private function isValidDatetime(string $datetime): bool
    {
        // Check basic ISO 8601 format with timezone
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $datetime)) {
            return false;
        }

        // Try to parse the datetime to ensure it's valid
        return strtotime($datetime) !== false;
    }

    /**
     * Import a date range of days off
     *
     * @param string $userId The user ID
     * @param string $leaveTypeId The leave type ID
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param string $startTime Start time (H:i:s format)
     * @param string $endTime End time (H:i:s format)
     * @param string $timezone Timezone (default: +00:00)
     * @param array $excludeWeekends Whether to exclude weekends (default: true)
     * @return array
     */
    public function importDateRange(
        string $userId,
        string $leaveTypeId,
        string $startDate,
        string $endDate,
        string $startTime = '08:00:00',
        string $endTime = '18:00:00',
        string $timezone = '+00:00',
        bool   $excludeWeekends = true
    ): array
    {
        $dates = [];
        $currentDate = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);

        while ($currentDate <= $endDateTime) {
            // Skip weekends if requested
            if ($excludeWeekends && in_array($currentDate->format('N'), [6, 7])) {
                $currentDate->modify('+1 day');
                continue;
            }

            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        return $this->importMultipleDays($userId, $leaveTypeId, $dates, $startTime, $endTime, $timezone);
    }

    /**
     * Import multiple days off with the same duration pattern
     *
     * @param string $userId The user ID
     * @param string $leaveTypeId The leave type ID
     * @param array $dates Array of date strings (Y-m-d format)
     * @param string $startTime Start time (H:i:s format, e.g., '08:00:00')
     * @param string $endTime End time (H:i:s format, e.g., '18:00:00')
     * @param string $timezone Timezone (default: +00:00)
     * @return array
     */
    public function importMultipleDays(
        string $userId,
        string $leaveTypeId,
        array  $dates,
        string $startTime = '08:00:00',
        string $endTime = '18:00:00',
        string $timezone = '+00:00'
    ): array
    {
        $days = [];

        foreach ($dates as $date) {
            $days[] = [
                'starts_at' => "{$date}T{$startTime}{$timezone}",
                'ends_at' => "{$date}T{$endTime}{$timezone}"
            ];
        }

        return $this->bulkImport($userId, $leaveTypeId, $days);
    }

    /**
     * Override standard methods since this resource doesn't support them
     */
    public function list(array $filters = [], array $options = [])
    {
        throw new BadMethodCallException('DaysOff resource does not support list operations. Use bulkImport or bulkDelete instead.');
    }

    public function info($id, $includes = null)
    {
        throw new BadMethodCallException('DaysOff resource does not support info operations. Use bulkImport or bulkDelete instead.');
    }

    public function create(array $data)
    {
        throw new BadMethodCallException('DaysOff resource does not support create operations. Use bulkImport instead.');
    }

    public function update($id, array $data)
    {
        throw new BadMethodCallException('DaysOff resource does not support update operations. Use bulkImport or bulkDelete instead.');
    }

    public function delete($id)
    {
        throw new BadMethodCallException('DaysOff resource does not support delete operations. Use bulkDelete instead.');
    }

    /**
     * Get available date/time formatting helpers
     */
    public function getFormattingHelpers(): array
    {
        return [
            'iso_format' => 'Y-m-d\TH:i:sP',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'common_timezones' => [
                'UTC' => '+00:00',
                'CET' => '+01:00',
                'CEST' => '+02:00',
                'EST' => '-05:00',
                'PST' => '-08:00'
            ],
            'common_work_hours' => [
                'full_day' => ['08:00:00', '18:00:00'],
                'morning' => ['08:00:00', '12:00:00'],
                'afternoon' => ['13:00:00', '18:00:00'],
                'half_day' => ['08:00:00', '12:30:00']
            ]
        ];
    }

    /**
     * Format datetime for API
     */
    public function formatDatetime(DateTime $datetime): string
    {
        return $datetime->format('Y-m-d\TH:i:sP');
    }

    /**
     * Create datetime from date and time strings
     */
    public function createDatetime(string $date, string $time, string $timezone = '+00:00'): string
    {
        return "{$date}T{$time}{$timezone}";
    }

    /**
     * Override getSuggestedIncludes since this resource doesn't support includes
     */
    protected function getSuggestedIncludes(): array
    {
        return [];
    }
}
