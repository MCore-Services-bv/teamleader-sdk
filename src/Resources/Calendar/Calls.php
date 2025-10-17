<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Calls extends Resource
{
    protected string $description = 'Manage calls in Teamleader Focus Calendar';

    // Resource capabilities - Calls support core operations
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = false; // No delete endpoint in API

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = false; // Not explicitly mentioned in docs

    protected bool $supportsSideloading = false; // No includes mentioned

    // Common filters based on API documentation
    protected array $commonFilters = [
        'scheduled_after' => 'Filter on calls occurring on or after a given date (YYYY-MM-DD)',
        'scheduled_before' => 'Filter on calls occurring on or before a given date (YYYY-MM-DD)',
        'relates_to' => 'Filter calls by related object (company)',
        'call_outcome_id' => 'Filter on completed calls by outcome',
    ];

    /**
     * List calls with filtering and pagination
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Build filter structure
        if (! empty($filters)) {
            $filterData = [];

            if (isset($filters['scheduled_after'])) {
                $filterData['scheduled_after'] = $filters['scheduled_after'];
            }

            if (isset($filters['scheduled_before'])) {
                $filterData['scheduled_before'] = $filters['scheduled_before'];
            }

            if (isset($filters['relates_to'])) {
                $filterData['relates_to'] = $filters['relates_to'];
            }

            if (isset($filters['call_outcome_id'])) {
                $filterData['call_outcome_id'] = $filters['call_outcome_id'];
            }

            if (! empty($filterData)) {
                $params['filter'] = $filterData;
            }
        }

        // Add pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get call information
     */
    public function info(string $id): array
    {
        $this->validateId($id);

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Create a new call
     */
    public function create(array $data): array
    {
        // Validate required fields
        $requiredFields = ['participant', 'due_at', 'assignee'];
        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required for creating a call");
            }
        }

        // Validate participant structure
        if (! isset($data['participant']['customer'])) {
            throw new InvalidArgumentException('Participant must have a customer object');
        }

        if (! isset($data['participant']['customer']['type']) || ! isset($data['participant']['customer']['id'])) {
            throw new InvalidArgumentException('Participant customer must have type and id');
        }

        // Validate assignee structure
        if (! isset($data['assignee']['type']) || ! isset($data['assignee']['id'])) {
            throw new InvalidArgumentException('Assignee must have type and id');
        }

        return $this->api->request('POST', $this->getBasePath().'.add', $data);
    }

    /**
     * Update an existing call
     */
    public function update(string $id, array $data): array
    {
        $this->validateId($id);

        // Add ID to the data
        $data['id'] = $id;

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Mark a call as complete
     */
    public function complete(string $id, ?string $outcomeId = null, ?string $outcomeSummary = null): array
    {
        $this->validateId($id);

        $data = ['id' => $id];

        if ($outcomeId !== null) {
            $data['call_outcome_id'] = $outcomeId;
        }

        if ($outcomeSummary !== null) {
            $data['outcome_summary'] = $outcomeSummary;
        }

        return $this->api->request('POST', $this->getBasePath().'.complete', $data);
    }

    /**
     * Get upcoming calls (scheduled after today)
     */
    public function upcoming(array $options = []): array
    {
        $today = date('Y-m-d');

        return $this->list(['scheduled_after' => $today], $options);
    }

    /**
     * Get overdue calls (scheduled before today, not completed)
     */
    public function overdue(array $options = []): array
    {
        $today = date('Y-m-d');

        return $this->list(['scheduled_before' => $today], $options);
    }

    /**
     * Get calls for a specific company
     */
    public function forCompany(string $companyId, array $options = []): array
    {
        $this->validateId($companyId, 'Company');

        return $this->list([
            'relates_to' => [
                'type' => 'company',
                'id' => $companyId,
            ],
        ], $options);
    }

    /**
     * Get calls within a date range
     */
    public function betweenDates(string $startDate, string $endDate, array $options = []): array
    {
        return $this->list([
            'scheduled_after' => $startDate,
            'scheduled_before' => $endDate,
        ], $options);
    }

    /**
     * Get today's calls
     */
    public function today(array $options = []): array
    {
        $today = date('Y-m-d');

        return $this->betweenDates($today, $today, $options);
    }

    /**
     * Get this week's calls
     */
    public function thisWeek(array $options = []): array
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));

        return $this->betweenDates($startOfWeek, $endOfWeek, $options);
    }

    /**
     * Schedule a call (alias for create with more intuitive naming)
     */
    public function schedule(array $data): array
    {
        return $this->create($data);
    }

    /**
     * Reschedule a call (update only the due_at field)
     */
    public function reschedule(string $id, string $newDateTime): array
    {
        return $this->update($id, ['due_at' => $newDateTime]);
    }

    /**
     * Get completed calls with a specific outcome
     */
    public function withOutcome(string $outcomeId, array $options = []): array
    {
        $this->validateId($outcomeId, 'Outcome');

        return $this->list(['call_outcome_id' => $outcomeId], $options);
    }

    /**
     * Get the base path for the calls resource
     */
    protected function getBasePath(): string
    {
        return 'calls';
    }
}
