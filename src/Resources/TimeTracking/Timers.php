<?php

namespace McoreServices\TeamleaderSDK\Resources\TimeTracking;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Timers extends Resource
{
    use ValidatesWritePayload;

    /** Body fields timers.start and timers.update accept — none of them required */
    public const WRITE_FIELDS = ['work_type_id', 'started_at', 'description', 'subject', 'invoiceable'];

    /** `subject.type` on timers.start and timers.update */
    public const SUBJECT_TYPES = ['company', 'contact', 'event', 'todo', 'milestone', 'ticket'];

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
    protected array $availableSubjectTypes = self::SUBJECT_TYPES;

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
]);',
        ],
        'start_timer_for_ticket' => [
            'description' => 'Start a timer for a ticket',
            'code' => '$timer = $teamleader->timers()->startForSubject(
    \'ticket\',
    \'ticket-uuid\',
    \'work-type-uuid\',
    [\'description\' => \'Fixing bug\', \'invoiceable\' => true]
);',
        ],
        'get_current' => [
            'description' => 'Get the currently running timer',
            'code' => '$currentTimer = $teamleader->timers()->current();',
        ],
        'update_current' => [
            'description' => 'Update the current timer description',
            'code' => '$result = $teamleader->timers()->updateCurrent([\'description\' => \'Updated description\']);',
        ],
        'stop_timer' => [
            'description' => 'Stop the current timer',
            'code' => '$result = $teamleader->timers()->stop();',
        ],
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
     * @param  array  $data  Timer data
     *
     * @throws InvalidArgumentException
     */
    public function start(array $data): array
    {
        $this->validateStartData($data);

        return $this->api->request('POST', $this->getBasePath().'.start', $data);
    }

    /**
     * Start a timer for a specific subject (convenience method)
     *
     * @param  string  $subjectType  Type of subject (company, contact, event, todo, milestone, ticket)
     * @param  string  $subjectId  UUID of the subject
     * @param  string  $workTypeId  UUID of the work type
     * @param  array  $options  Additional options (description, invoiceable, started_at)
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
                'id' => $subjectId,
            ],
        ], $options);

        return $this->start($data);
    }

    /**
     * Get the current running timer
     */
    public function current(): array
    {
        return $this->api->request('POST', $this->getBasePath().'.current');
    }

    /**
     * Stop the current timer
     * This will add a new time tracking entry in the background
     */
    public function stop(): array
    {
        return $this->api->request('POST', $this->getBasePath().'.stop');
    }

    /**
     * Update the current timer
     * Only possible if there is a timer running
     */
    public function updateCurrent(array $data): array
    {
        $this->validateUpdateData($data);

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Alias for updateCurrent()
     *
     * The usage examples called `timers()->update()` until v2.2.11, when no
     * such method existed; it is kept so code written from them works.
     *
     * @param  array  $data  Fields to change on the running timer
     */
    public function update(array $data): array
    {
        return $this->updateCurrent($data);
    }

    /**
     * Check if there is a timer currently running
     */
    public function isRunning(): bool
    {
        try {
            $response = $this->current();

            return ! empty($response['data']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate data for starting a timer
     *
     * timers.start requires nothing: without a subject or work type the timer
     * simply runs unlinked, and started_at defaults to now. Until v2.2.11 the
     * SDK required both a subject and a work_type_id.
     *
     * @throws InvalidArgumentException
     */
    private function validateStartData(array $data): void
    {
        $this->rejectUnknownFields($data, self::WRITE_FIELDS, 'timers.start');
        $this->validateCommonFields($data);
    }

    /**
     * Validate data for updating the running timer
     *
     * work_type_id, description and subject take null to clear them.
     *
     * @throws InvalidArgumentException
     */
    private function validateUpdateData(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('At least one field must be provided for update');
        }

        $this->rejectUnknownFields($data, self::WRITE_FIELDS, 'timers.update');
        $this->validateCommonFields($data);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateCommonFields(array $data): void
    {
        if (isset($data['subject'])) {
            if (! is_array($data['subject']) || ! isset($data['subject']['type']) || empty($data['subject']['id'])) {
                throw new InvalidArgumentException('Subject must be an array with type and id');
            }

            $this->validateSubjectType($data['subject']['type']);
        }

        if (isset($data['started_at']) && ! $this->isValidDateTime($data['started_at'])) {
            throw new InvalidArgumentException('started_at must be in ISO 8601 format');
        }

        if (isset($data['invoiceable']) && ! is_bool($data['invoiceable'])) {
            throw new InvalidArgumentException('invoiceable must be a boolean');
        }
    }

    /**
     * Validate subject type
     *
     * @throws InvalidArgumentException
     */
    private function validateSubjectType(string $type): void
    {
        if (! in_array($type, $this->availableSubjectTypes)) {
            throw new InvalidArgumentException(
                "Invalid subject type '{$type}'. Available types: ".
                implode(', ', $this->availableSubjectTypes)
            );
        }
    }

    /**
     * Validate datetime format (ISO 8601)
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
     */
    public function getAvailableSubjectTypes(): array
    {
        return $this->availableSubjectTypes;
    }
}
