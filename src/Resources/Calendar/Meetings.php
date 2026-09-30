<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Meetings extends Resource
{
    use ValidatesWritePayload;

    /** Body fields meetings.schedule accepts */
    public const SCHEDULE_FIELDS = [
        'title', 'starts_at', 'ends_at', 'description', 'attendees', 'customer', 'location',
        'project_id', 'group_id', 'milestone_id', 'deal_id', 'work_order_id', 'custom_fields',
    ];

    /** Body fields meetings.update accepts, besides `id` — no work_order_id */
    public const UPDATE_FIELDS = [
        'title', 'starts_at', 'ends_at', 'description', 'attendees', 'customer', 'location',
        'project_id', 'group_id', 'milestone_id', 'deal_id', 'custom_fields',
    ];

    public const REQUIRED_ON_SCHEDULE = ['title', 'starts_at', 'ends_at', 'attendees'];

    /** Body fields meetings.createReport accepts, besides `id` */
    public const REPORT_FIELDS = ['attach_to', 'summary', 'custom_fields'];

    public const ATTENDEE_TYPES = ['user', 'contact'];

    public const CUSTOMER_TYPES = ['contact', 'company'];

    /** `attach_to.type` on meetings.createReport */
    public const REPORT_TARGET_TYPES = ['contact', 'company', 'deal'];

    /** Includes meetings.list and meetings.info accept */
    public const INCLUDES = ['tracked_time', 'estimated_time'];

    protected string $description = 'Manage meetings in Teamleader Focus Calendar';

    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = true;

    protected array $availableIncludes = self::INCLUDES;

    protected array $commonFilters = [
        'ids' => 'Array of meeting UUIDs to filter by',
        'employee_id' => 'Filter by assigned employee UUID',
        'start_date' => 'Filter meetings from this date (YYYY-MM-DD)',
        'end_date' => 'Filter meetings up to this date (YYYY-MM-DD)',
        'milestone_id' => 'Filter by legacy project milestone UUID (cannot be combined with group_id)',
        'group_id' => 'Filter by nextgen project group UUID (cannot be combined with milestone_id)',
        'term' => 'Search meetings by title or description',
        'recurrence_id' => 'Filter by recurring meeting series UUID',
    ];

    /**
     * Sort fields meetings.list accepts. Before v2.2.12 the sort option was
     * passed through unchecked.
     */
    protected array $availableSortFields = [
        'scheduled_at' => 'Scheduled start',
    ];

    // Usage examples specific to meetings
    protected array $usageExamples = [
        'list_meetings' => [
            'description' => 'Get list of meetings with filtering',
            'code' => '$meetings = $teamleader->meetings()->list([\'employee_id\' => \'employee-uuid\']);',
        ],
        'get_meeting_details' => [
            'description' => 'Get meeting details with tracked time',
            'code' => '$meeting = $teamleader->meetings()->withTrackedTime()->info(\'meeting-uuid\');',
        ],
        'create_meeting' => [
            'description' => 'Schedule a new meeting',
            'code' => '$meeting = $teamleader->meetings()->schedule([...]);',
        ],
        'complete_meeting' => [
            'description' => 'Mark a meeting as complete',
            'code' => '$teamleader->meetings()->complete(\'meeting-uuid\');',
        ],
        'create_report' => [
            'description' => 'Create a report for completed meeting',
            'code' => '$report = $teamleader->meetings()->createReport(\'meeting-uuid\', [...]);',
        ],
    ];

    protected function getBasePath(): string
    {
        return 'meetings';
    }

    /**
     * List meetings
     *
     * @param  array  $filters  ids, employee_id, start_date, end_date, milestone_id,
     *                          group_id, term, recurrence_id. group_id (nextgen project
     *                          group) cannot be combined with milestone_id (legacy).
     * @param  array  $options  page_size, page_number, sort (scheduled_at), sort_order, include(s)
     *
     * @throws InvalidArgumentException On an unknown filter key, option, sort field or include
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(
            array_keys($options),
            ['page_size', 'page_number', 'sort', 'sort_order', 'include', 'includes', 'filters']
        );

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'meetings.list does not support: '.implode(', ', $unknown)
                .'. Supported: page_size, page_number, sort, sort_order, include.'
            );
        }

        if (! empty($filters['group_id']) && ! empty($filters['milestone_id'])) {
            throw new InvalidArgumentException(
                'The "group_id" filter cannot be combined with "milestone_id". Use one or the other.'
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $this->buildFilterParams($filters, $options));
    }

    /**
     * Get one meeting
     *
     * @param  string  $id  Meeting UUID
     * @param  mixed  $includes  tracked_time and/or estimated_time
     *
     * @throws InvalidArgumentException On an include meetings.info does not accept
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];
        $includes = $this->collectIncludes($includes, 'meetings.info');

        return $this->api->request('POST', $this->getBasePath().'.info', $this->applyIncludes($params, $includes));
    }

    /**
     * Schedule a meeting
     *
     * Requires title, starts_at, ends_at and attendees, with at least one user
     * attendee. `customer` is optional; until v2.2.12 the SDK required it.
     *
     * project_id and milestone_id are mutually exclusive, and group_id
     * requires project_id.
     *
     * @throws InvalidArgumentException When a required field is missing, or a field or value is not accepted
     */
    public function schedule(array $data): array
    {
        return $this->api->request('POST', $this->getBasePath().'.schedule', $this->validateScheduleData($data));
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateScheduleData(array $data): array
    {
        foreach (self::REQUIRED_ON_SCHEDULE as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("{$field} is required to schedule a meeting");
            }
        }

        $this->rejectUnknownFields($data, self::SCHEDULE_FIELDS, 'meetings.schedule');
        $this->validateCommonFields($data, 'meetings.schedule');

        return $data;
    }

    /**
     * Update a meeting
     *
     * If attendees is given it must include at least one user. customer,
     * description, deal_id, project_id, group_id and milestone_id take null
     * to clear them.
     *
     * @throws InvalidArgumentException When a field or value is not accepted
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;

        return $this->api->request('POST', $this->getBasePath().'.update', $this->validateUpdateData($data));
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateUpdateData(array $data): array
    {
        if (empty($data['id'])) {
            throw new InvalidArgumentException('Meeting ID is required for updates');
        }

        $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], 'meetings.update');
        $this->validateCommonFields($data, 'meetings.update');

        return $data;
    }

    /**
     * Checks schedule and update share
     *
     * @throws InvalidArgumentException
     */
    private function validateCommonFields(array $data, string $endpoint): void
    {
        if (isset($data['attendees'])) {
            if (! is_array($data['attendees']) || ! array_is_list($data['attendees'])) {
                throw new InvalidArgumentException("attendees must be a list of ['type' => user|contact, 'id' => uuid]");
            }

            foreach ($data['attendees'] as $index => $attendee) {
                if (! is_array($attendee) || empty($attendee['id']) || ! isset($attendee['type'])) {
                    throw new InvalidArgumentException("attendees[{$index}] needs both a type and an id");
                }

                $this->assertEnum($attendee['type'], self::ATTENDEE_TYPES, "attendees[{$index}].type", $endpoint);
            }

            if (! in_array('user', array_column($data['attendees'], 'type'), true)) {
                throw new InvalidArgumentException('At least one user attendee must be present');
            }
        }

        if (isset($data['customer'])) {
            if (! is_array($data['customer']) || empty($data['customer']['id']) || ! isset($data['customer']['type'])) {
                throw new InvalidArgumentException("customer must be ['type' => contact|company, 'id' => uuid]");
            }

            $this->assertEnum($data['customer']['type'], self::CUSTOMER_TYPES, 'customer.type', $endpoint);
        }

        if (! empty($data['project_id']) && ! empty($data['milestone_id'])) {
            throw new InvalidArgumentException('project_id and milestone_id are mutually exclusive');
        }

        if (! empty($data['group_id']) && empty($data['project_id'])) {
            throw new InvalidArgumentException('group_id requires project_id; the group must belong to that project');
        }

        foreach (['starts_at', 'ends_at'] as $field) {
            if (isset($data[$field]) && (! is_string($data[$field]) || strtotime($data[$field]) === false)) {
                throw new InvalidArgumentException("{$field} must be an ISO 8601 datetime, e.g. 2026-01-15T09:00:00+01:00");
            }
        }

        if (isset($data['starts_at'], $data['ends_at']) && strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
            throw new InvalidArgumentException('ends_at must be after starts_at');
        }
    }

    /**
     * Delete a meeting
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Mark meeting as complete
     */
    public function complete($id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.complete', ['id' => $id]);
    }

    /**
     * Create a report for a meeting
     *
     * @param  array  $reportData  attach_to [type => contact|company|deal, id] (required), summary, custom_fields
     *
     * @throws InvalidArgumentException
     */
    public function createReport($meetingId, array $reportData): array
    {
        $data = array_merge(['id' => $meetingId], $reportData);

        return $this->api->request('POST', $this->getBasePath().'.createReport', $this->validateReportData($data));
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateReportData(array $data): array
    {
        if (empty($data['id'])) {
            throw new InvalidArgumentException('Meeting ID is required for reports');
        }

        $this->rejectUnknownFields($data, [...self::REPORT_FIELDS, 'id'], 'meetings.createReport');

        if (empty($data['attach_to']['type']) || empty($data['attach_to']['id'])) {
            throw new InvalidArgumentException('Report attachment must specify type and id');
        }

        $this->assertEnum($data['attach_to']['type'], self::REPORT_TARGET_TYPES, 'attach_to.type', 'meetings.createReport');

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
     * Build the request body for meetings.list
     *
     * Until v2.2.12 filters and sort were passed through unchecked, and the
     * `includes` option key was ignored.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilterParams(array $filters, array $options): array
    {
        $params = [];

        if ($filters !== []) {
            $this->rejectUnknownFilters($filters, 'meetings.list');

            if (isset($filters['ids']) && ! is_array($filters['ids'])) {
                $filters['ids'] = [$filters['ids']];
            }

            $filters = array_filter($filters, fn ($value) => $value !== null);

            if ($filters !== []) {
                $params['filter'] = $filters;
            }
        }

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        if (! empty($options['sort'])) {
            $params['sort'] = $this->normaliseSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        return $this->applyIncludes($params, $this->collectIncludes($this->resolveIncludesOption($options), 'meetings.list'));
    }

    /**
     * Includes from the argument plus any queued through the fluent interface, checked
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    private function collectIncludes(mixed $includes, string $endpoint): array
    {
        $pending = $this->getPendingIncludes();
        $this->pendingIncludes = [];

        return $this->assertIncludes([...(array) ($includes ?? []), ...$pending], self::INCLUDES, $endpoint);
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
            'end_date' => $endDate,
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
