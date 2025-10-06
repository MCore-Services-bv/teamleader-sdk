<?php

namespace McoreServices\TeamleaderSDK\Resources\TimeTracking;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Timers extends Resource
{
    protected string $description = 'Manage time tracking timers in Teamleader Focus';

    // Resource capabilities - Timers support limited operations
    protected bool $supportsCreation = true;   // Can start timers
    protected bool $supportsUpdate = true;     // Can update current timer
    protected bool $supportsDeletion = false;  // No delete endpoint, use stop instead
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = false;
    protected bool $supportsSideloading = false;

    // Available includes (none for timers)
    protected array $availableIncludes = [];

    // Common filters (none for timers)
    protected array $commonFilters = [];

    // Available subject types for timers
    protected array $availableSubjectTypes = [
        'company',
        'contact',
        'event',
        'todo',
        'milestone',
        'ticket'
    ];

    // Usage examples specific to timers
    protected array $usageExamples = [
        'start_timer' => [
            'description' => 'Start a new timer for a company',
            'code' => '$timer = $teamleader->timers()->start([
    \'work_type_id\' => \'work-type-uuid\',
    \'subject\' => [
        \'type\' => \'company\',
        \'id\' => \'company-uuid\'
    ],
    \'description\' => \'Working on project\',
    \'invoiceable\' => true
]);'
        ],
        'start_timer_for_ticket' => [
            'description' => 'Start a timer for a ticket',
            'code' => '$timer = $teamleader->timers()->startForSubject(
    \'ticket\',
    \'ticket-uuid\',
    \'work-type-uuid\',
    [\'description\' => \'Fixing bug\', \'invoiceable\' => true]
);'
        ],
        'get_current' => [
            'description' => 'Get the currently running timer',
            'code' => '$currentTimer = $teamleader->timers()->current();'
        ],
        'update_current' => [
            'description' => 'Update the current timer description',
            'code' => '$result = $teamleader->timers()->update([\'description\' => \'Updated description\']);'
        ],
        'stop_timer' => [
            'description' => 'Stop the current timer',
            'code' => '$result = $teamleader->timers()->stop();'
        ]
    ];

    /**
     * Get the base path for the timers resource
     */
    protected function getBasePath(): string
    {
        return 'timers';
    }

    /**
     * Start a new timer
     *
     * @param array $data Timer data
     * @return array
     * @throws InvalidArgumentException
     */
    public function start(array $data): array
    {
        $this->validateStartData($data);

        return $this->api->request('POST', $this->getBasePath() . '.start', $data);
    }

    /**
     * Start a timer for a specific subject (convenience method)
     *
     * @param string $subjectType Type of subject (company, contact, event, todo, milestone, ticket)
     * @param string $subjectId UUID of the subject
     * @param string $workTypeId UUID of the work type
     * @param array $options Additional options (description, invoiceable, started_at)
     * @return array
     */
    public function startForSubject(
        string $subjectType,
        string $subjectId,
        string $workTypeId,
        array $options = []
    ): array {
        $this->validateSubjectType($subjectType);

        $data = array_merge([
            'work_type_id' => $workTypeId,
            'subject' => [
                'type' => $subjectType,
                'id' => $subjectId
            ]
        ], $options);

        return $this->start($data);
    }

    /**
     * Get the current running timer
     *
     * @return array
     */
    public function current(): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.current');
    }

    /**
     * Stop the current timer
     * This will add a new time tracking entry in the background
     *
     * @return array
     */
    public function stop(): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.stop');
    }

    /**
     * Update the current timer
     * Only possible if there is a timer running
     */
    public function updateCurrent(array $data): array
    {
        $this->validateUpdateData($data);
        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Check if there is a timer currently running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        try {
            $response = $this->current();
            return !empty($response['data']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate data for starting a timer
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    private function validateStartData(array $data): void
    {
        // Subject is required and must have type and id
        if (!isset($data['subject']) || !is_array($data['subject'])) {
            throw new InvalidArgumentException('Subject is required and must be an array');
        }

        if (!isset($data['subject']['type']) || !isset($data['subject']['id'])) {
            throw new InvalidArgumentException('Subject must contain both type and id');
        }

        $this->validateSubjectType($data['subject']['type']);

        // work_type_id is required
        if (!isset($data['work_type_id']) || empty($data['work_type_id'])) {
            throw new InvalidArgumentException('work_type_id is required');
        }

        // If started_at is provided, validate format
        if (isset($data['started_at']) && !$this->isValidDateTime($data['started_at'])) {
            throw new InvalidArgumentException('started_at must be in ISO 8601 format');
        }

        // If invoiceable is provided, validate it's boolean
        if (isset($data['invoiceable']) && !is_bool($data['invoiceable'])) {
            throw new InvalidArgumentException('invoiceable must be a boolean');
        }
    }

    /**
     * Validate data for updating a timer
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    private function validateUpdateData(array $data): void
    {
        // At least one field must be provided for update
        if (empty($data)) {
            throw new InvalidArgumentException('At least one field must be provided for update');
        }

        // If subject is provided, validate it
        if (isset($data['subject'])) {
            if (!is_array($data['subject']) || !isset($data['subject']['type']) || !isset($data['subject']['id'])) {
                throw new InvalidArgumentException('Subject must be an array with type and id');
            }
            $this->validateSubjectType($data['subject']['type']);
        }

        // If started_at is provided, validate format
        if (isset($data['started_at']) && !$this->isValidDateTime($data['started_at'])) {
            throw new InvalidArgumentException('started_at must be in ISO 8601 format');
        }

        // If invoiceable is provided, validate it's boolean
        if (isset($data['invoiceable']) && !is_bool($data['invoiceable'])) {
            throw new InvalidArgumentException('invoiceable must be a boolean');
        }
    }

    /**
     * Validate subject type
     *
     * @param string $type
     * @throws InvalidArgumentException
     */
    private function validateSubjectType(string $type): void
    {
        if (!in_array($type, $this->availableSubjectTypes)) {
            throw new InvalidArgumentException(
                "Invalid subject type '{$type}'. Available types: " .
                implode(', ', $this->availableSubjectTypes)
            );
        }
    }

    /**
     * Validate datetime format (ISO 8601)
     *
     * @param string $datetime
     * @return bool
     */
    private function isValidDateTime(string $datetime): bool
    {
        try {
            new \DateTime($datetime);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available subject types
     *
     * @return array
     */
    public function getAvailableSubjectTypes(): array
    {
        return $this->availableSubjectTypes;
    }
}
