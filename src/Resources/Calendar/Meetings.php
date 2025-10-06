<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use McoreServices\TeamleaderSDK\Resources\Resource;
use InvalidArgumentException;

class Meetings extends Resource
{
    protected string $description = 'Manage meetings in Teamleader Focus Calendar';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = true;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'tracked_time',
        'estimated_time'
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of meeting UUIDs to filter by',
        'employee_id' => 'Filter by assigned employee UUID',
        'start_date' => 'Filter meetings from this date (YYYY-MM-DD)',
        'end_date' => 'Filter meetings up to this date (YYYY-MM-DD)',
        'milestone_id' => 'Filter by project milestone UUID',
        'term' => 'Search meetings by title or description',
        'recurrence_id' => 'Filter by recurring meeting series UUID'
    ];

    // Usage examples specific to meetings
    protected array $usageExamples = [
        'list_meetings' => [
            'description' => 'Get list of meetings with filtering',
            'code' => '$meetings = $teamleader->meetings()->list([\'employee_id\' => \'employee-uuid\']);'
        ],
        'get_meeting_details' => [
            'description' => 'Get meeting details with tracked time',
            'code' => '$meeting = $teamleader->meetings()->withTrackedTime()->info(\'meeting-uuid\');'
        ],
        'create_meeting' => [
            'description' => 'Schedule a new meeting',
            'code' => '$meeting = $teamleader->meetings()->schedule([...]);'
        ],
        'complete_meeting' => [
            'description' => 'Mark a meeting as complete',
            'code' => '$teamleader->meetings()->complete(\'meeting-uuid\');'
        ],
        'create_report' => [
            'description' => 'Create a report for completed meeting',
            'code' => '$report = $teamleader->meetings()->createReport(\'meeting-uuid\', [...]);'
        ]
    ];

    /**
     * Get meeting information
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        if (!empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.info', $params);
    }

    /**
     * Get the base path for the meetings resource
     */
    protected function getBasePath(): string
    {
        return 'meetings';
    }

    /**
     * Schedule a new meeting
     */
    public function schedule(array $data): array
    {
        $validatedData = $this->validateScheduleData($data);
        return $this->api->request('POST', $this->getBasePath() . '.schedule', $validatedData);
    }

    /**
     * Validate data for meeting scheduling
     */
    protected function validateScheduleData(array $data): array
    {
        // Required fields
        if (empty($data['title'])) {
            throw new InvalidArgumentException('Meeting title is required');
        }
        if (empty($data['starts_at'])) {
            throw new InvalidArgumentException('Meeting start time is required');
        }
        if (empty($data['ends_at'])) {
            throw new InvalidArgumentException('Meeting end time is required');
        }
        if (empty($data['attendees']) || !is_array($data['attendees'])) {
            throw new InvalidArgumentException('At least one attendee is required');
        }
        if (empty($data['customer'])) {
            throw new InvalidArgumentException('Customer information is required');
        }

        // Validate attendees
        $hasUserAttendee = false;
        foreach ($data['attendees'] as $attendee) {
            if (isset($attendee['type']) && $attendee['type'] === 'user') {
                $hasUserAttendee = true;
                break;
            }
        }
        if (!$hasUserAttendee) {
            throw new InvalidArgumentException('At least one user attendee must be present');
        }

        return $data;
    }

    /**
     * Update an existing meeting
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $validatedData = $this->validateUpdateData($data);
        return $this->api->request('POST', $this->getBasePath() . '.update', $validatedData);
    }

    /**
     * Validate data for meeting updates
     */
    protected function validateUpdateData(array $data): array
    {
        if (empty($data['id'])) {
            throw new InvalidArgumentException('Meeting ID is required for updates');
        }

        // If attendees are provided, validate them
        if (isset($data['attendees']) && !empty($data['attendees'])) {
            $hasUserAttendee = false;
            foreach ($data['attendees'] as $attendee) {
                if (isset($attendee['type']) && $attendee['type'] === 'user') {
                    $hasUserAttendee = true;
                    break;
                }
            }
            if (!$hasUserAttendee) {
                throw new InvalidArgumentException('At least one user attendee must be present when updating attendees');
            }
        }

        return $data;
    }

    /**
     * Delete a meeting
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Mark meeting as complete
     */
    public function complete($id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.complete', ['id' => $id]);
    }

    /**
     * Create a report for a meeting
     */
    public function createReport($meetingId, array $reportData): array
    {
        $data = array_merge(['id' => $meetingId], $reportData);
        $validatedData = $this->validateReportData($data);
        return $this->api->request('POST', $this->getBasePath() . '.createReport', $validatedData);
    }

    /**
     * Validate data for meeting reports
     */
    protected function validateReportData(array $data): array
    {
        if (empty($data['id'])) {
            throw new InvalidArgumentException('Meeting ID is required for reports');
        }
        if (empty($data['attach_to'])) {
            throw new InvalidArgumentException('Attachment target is required for reports');
        }
        if (empty($data['attach_to']['type']) || empty($data['attach_to']['id'])) {
            throw new InvalidArgumentException('Report attachment must specify type and id');
        }

        $validTypes = ['contact', 'company', 'deal'];
        if (!in_array($data['attach_to']['type'], $validTypes)) {
            throw new InvalidArgumentException('Report can only be attached to: ' . implode(', ', $validTypes));
        }

        return $data;
    }

    /**
     * Include tracked time in the next request
     */
    public function withTrackedTime(): self
    {
        return $this->with('tracked_time');
    }

    /**
     * Include estimated time in the next request
     */
    public function withEstimatedTime(): self
    {
        return $this->with('estimated_time');
    }

    /**
     * Get meetings for a specific employee
     */
    public function forEmployee($employeeId, array $options = []): array
    {
        return $this->list(array_merge(['employee_id' => $employeeId], $options['filters'] ?? []), $options);
    }

    /**
     * List meetings with enhanced filtering and sorting
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = $this->buildFilterParams($filters, $options);
        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Build filter parameters for API request
     */
    protected function buildFilterParams(array $filters, array $options): array
    {
        $params = [];

        if (!empty($filters)) {
            $params['filter'] = $filters;
        }

        // Pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1
            ];
        }

        // Sorting
        if (isset($options['sort'])) {
            $params['sort'] = $options['sort'];
        }

        // Includes
        if (isset($options['include'])) {
            $params['includes'] = $options['include'];
        }

        // Apply pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $params;
    }

    /**
     * Get meetings for today
     */
    public function today(array $options = []): array
    {
        $today = date('Y-m-d');
        return $this->inDateRange($today, $today, $options);
    }

    /**
     * Get meetings in date range
     */
    public function inDateRange(string $startDate, string $endDate, array $options = []): array
    {
        return $this->list(array_merge([
            'start_date' => $startDate,
            'end_date' => $endDate
        ], $options['filters'] ?? []), $options);
    }

    /**
     * Search meetings by term
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(array_merge(['term' => $term], $options['filters'] ?? []), $options);
    }
}
