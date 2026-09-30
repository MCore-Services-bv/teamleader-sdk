<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Calls extends Resource
{
    use ValidatesWritePayload;

    /** Body fields calls.add accepts */
    public const ADD_FIELDS = ['description', 'participant', 'due_at', 'assignee', 'deal_id', 'custom_fields'];

    /** Body fields calls.update accepts, besides `id` — the same set */
    public const UPDATE_FIELDS = self::ADD_FIELDS;

    public const REQUIRED_ON_ADD = ['participant', 'due_at', 'assignee'];

    /** `participant.customer.type` */
    public const CUSTOMER_TYPES = ['contact', 'company'];

    /** `assignee.type` — calls are assigned to users only */
    public const ASSIGNEE_TYPES = ['user'];

    protected string $description = 'Manage calls in Teamleader Focus Calendar';

    // Resource capabilities - Calls support core operations
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = false; // Not explicitly mentioned in docs

    protected bool $supportsSideloading = false; // No includes mentioned

    /**
     * `includes=pagination` adds a meta block with the total match count —
     * calls.list documents it, so it is requested on every call.
     */
    protected bool $requestsPaginationMeta = true;

    // Common filters based on API documentation
    protected array $commonFilters = [
        'scheduled_after' => 'Filter on calls occurring on or after a given date (YYYY-MM-DD)',
        'scheduled_before' => 'Filter on calls occurring on or before a given date (YYYY-MM-DD)',
        'relates_to' => 'Filter calls by related object (company)',
        'call_outcome_id' => 'Filter on completed calls by outcome',
    ];

    /**
     * Get call information
     */
    public function info(string $id, $includes = null): array
    {
        $this->assertIncludes($includes, [], 'calls.info');

        $this->validateId($id);

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Get the base path for the calls resource
     */
    protected function getBasePath(): string
    {
        return 'calls';
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
     * List calls
     *
     * `includes=pagination` is always sent, so the response carries the total
     * match count in `meta`.
     *
     * @param  array  $filters  scheduled_after, scheduled_before (YYYY-MM-DD),
     *                          relates_to [type, id], call_outcome_id
     * @param  array  $options  page_size, page_number
     *
     * @throws InvalidArgumentException On an unknown filter key or option
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'filters']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'calls.list does not support: '.implode(', ', $unknown)
                .'. Supported: page_size, page_number. It takes no sort or includes.'
            );
        }

        // Unknown keys were dropped without a word until v2.2.12.
        $this->rejectUnknownFilters($filters, 'calls.list');

        if (isset($filters['relates_to']) && (! is_array($filters['relates_to']) || empty($filters['relates_to']['id']) || empty($filters['relates_to']['type']))) {
            throw new InvalidArgumentException("The relates_to filter must be ['type' => ..., 'id' => uuid].");
        }

        $params = [];
        $filter = array_filter($filters, fn ($value) => $value !== null);

        if ($filter !== []) {
            $params['filter'] = $filter;
        }

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        $params['includes'] = 'pagination';

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
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
     * Get today's calls
     */
    public function today(array $options = []): array
    {
        $today = date('Y-m-d');

        return $this->betweenDates($today, $today, $options);
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
     * Create a call
     *
     * Requires participant [customer => [type, id]], due_at and assignee
     * [type => user, id].
     *
     * @throws InvalidArgumentException When a required field is missing, or a field or value is not accepted
     */
    public function create(array $data): array
    {
        foreach (self::REQUIRED_ON_ADD as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required for creating a call");
            }
        }

        $this->rejectUnknownFields($data, self::ADD_FIELDS, 'calls.add');
        $this->validateCallData($data, 'calls.add');

        return $this->api->request('POST', $this->getBasePath().'.add', $data);
    }

    /**
     * Checks add and update share
     *
     * @throws InvalidArgumentException
     */
    protected function validateCallData(array $data, string $endpoint): void
    {
        if (isset($data['participant'])) {
            if (! isset($data['participant']['customer'])) {
                throw new InvalidArgumentException('Participant must have a customer object');
            }

            $customer = $data['participant']['customer'];

            if (! is_array($customer) || ! isset($customer['type']) || empty($customer['id'])) {
                throw new InvalidArgumentException('Participant customer must have type and id');
            }

            $this->assertEnum($customer['type'], self::CUSTOMER_TYPES, 'participant.customer.type', $endpoint);
        }

        if (isset($data['assignee'])) {
            if (! is_array($data['assignee']) || ! isset($data['assignee']['type']) || empty($data['assignee']['id'])) {
                throw new InvalidArgumentException('Assignee must have type and id');
            }

            $this->assertEnum($data['assignee']['type'], self::ASSIGNEE_TYPES, 'assignee.type', $endpoint);
        }

        if (isset($data['due_at']) && (! is_string($data['due_at']) || ! preg_match('/^\d{4}-\d{2}-\d{2}T/', $data['due_at']) || strtotime($data['due_at']) === false)) {
            throw new InvalidArgumentException('due_at must be an ISO 8601 datetime, e.g. 2026-02-04T16:00:00+01:00');
        }
    }

    /**
     * Reschedule a call (update only the due_at field)
     */
    public function reschedule(string $id, string $newDateTime): array
    {
        return $this->update($id, ['due_at' => $newDateTime]);
    }

    /**
     * Update an existing call
     */
    public function update(string $id, array $data): array
    {
        $this->validateId($id);

        // Add ID to the data
        $data['id'] = $id;

        $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], 'calls.update');
        $this->validateCallData($data, 'calls.update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
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
     * Delete a call.
     *
     * @param  string  $id  Call UUID
     * @param  mixed  ...$additionalParams  Unused
     *
     * @throws InvalidArgumentException When the ID is not a valid UUID
     */
    public function delete($id, ...$additionalParams): array
    {
        $this->validateId($id);

        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
        ]);
    }
}
