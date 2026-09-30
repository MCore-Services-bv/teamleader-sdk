<?php

namespace McoreServices\TeamleaderSDK\Resources\TimeTracking;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class TimeTracking extends Resource
{
    use ValidatesWritePayload;

    /** Body fields timeTracking.add accepts */
    public const ADD_FIELDS = [
        'started_at', 'started_on', 'ended_at', 'duration', 'work_type_id',
        'description', 'subject', 'invoiceable', 'user_id',
    ];

    /**
     * Body fields timeTracking.update accepts, besides `id`. No `ended_at` —
     * an update gives the start and the duration — and no `user_id`.
     */
    public const UPDATE_FIELDS = [
        'started_at', 'started_on', 'duration', 'work_type_id', 'description', 'subject', 'invoiceable',
    ];

    /** Includes timeTracking.list and timeTracking.info accept */
    public const INCLUDES = ['materials', 'relates_to'];

    /** `filter.relates_to.type` on timeTracking.list */
    public const RELATES_TO_TYPES = ['milestone', 'project', 'nextgenProject', 'nextgenProjectGroup'];

    protected string $description = 'Manage time tracking entries in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = true;

    // Available includes for sideloading
    protected array $availableIncludes = self::INCLUDES;

    // Default includes
    protected array $defaultIncludes = [];

    /**
     * Subject types accepted when writing a time tracking entry
     * (timeTracking.add and timeTracking.update).
     *
     * Verified against @teamleader/focus-api-specification v1.197.0. Note this
     * is deliberately wider than $filterSubjectTypes: `nextgenTask` can carry
     * tracked time but is not accepted as a `filter.subject.type` on
     * timeTracking.list.
     *
     * The spec's update schema omits `nextgenTask` while the add schema includes
     * it. Rather than guess which is authoritative, the SDK accepts it on both
     * writes — the API is the backstop if update genuinely refuses it.
     */
    protected array $availableSubjectTypes = [
        'company',
        'contact',
        'event',
        'milestone',
        'nextgenTask',
        'ticket',
        'todo',
    ];

    /**
     * Subject types accepted by the timeTracking.list subject filter.
     *
     * `nextgenTask` is absent — the API does not offer it as a filter value even
     * though time can be tracked against one.
     */
    protected array $filterSubjectTypes = [
        'company',
        'contact',
        'event',
        'milestone',
        'ticket',
        'todo',
    ];

    /**
     * Sort fields accepted by timeTracking.list.
     *
     * The API declares exactly one. Anything else is silently ignored by the
     * API, so it is rejected here instead.
     */
    protected array $availableSortFields = [
        'starts_on' => 'Sort by the date the entry started',
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of time tracking entry UUIDs',
        'user_id' => 'Filter by user UUID',
        'started_after' => 'Start of period (ISO 8601 datetime)',
        'started_before' => 'End of period (ISO 8601 datetime)',
        'ended_after' => 'Start of period for ended entries (ISO 8601 datetime)',
        'ended_before' => 'End of period for ended entries (ISO 8601 datetime)',
        'subject' => 'Filter by subject (id and type)',
        'subject_types' => 'Filter by subject types array',
        'relates_to' => 'Filter by related entity (milestone, project, nextgenProject, nextgenProjectGroup)',
    ];

    // Valid relates_to type values for the relates_to filter
    protected array $validRelatesToTypes = self::RELATES_TO_TYPES;

    // Usage examples
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all time tracking entries',
            'code' => '$entries = $teamleader->timeTracking()->list();',
        ],
        'filter_by_user' => [
            'description' => 'Get entries for specific user',
            'code' => '$entries = $teamleader->timeTracking()->forUser("user-uuid");',
        ],
        'filter_by_date_range' => [
            'description' => 'Get entries within date range',
            'code' => '$entries = $teamleader->timeTracking()->betweenDates("2024-01-01", "2024-01-31");',
        ],
        'with_materials' => [
            'description' => 'Get entries with materials',
            'code' => '$entries = $teamleader->timeTracking()->withMaterials()->list();',
        ],
        'create_entry' => [
            'description' => 'Create a time tracking entry',
            'code' => '$entry = $teamleader->timeTracking()->create([
    "started_at" => "2024-01-15T10:00:00+00:00",
    "duration" => 3600,
    "subject" => [
        "id" => "company-uuid",
        "type" => "company"
    ]
]);',
        ],
    ];

    /**
     * Get the base path for the time tracking resource
     */
    protected function getBasePath(): string
    {
        return 'timeTracking';
    }

    /**
     * List time tracking entries with enhanced filtering and sorting
     */
    public function list(array $filters = [], array $options = []): array
    {
        $allowed = ['page_size', 'page_number', 'sort', 'sort_order', 'include', 'includes', 'filters'];
        $unknown = array_diff(array_keys($options), $allowed);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'timeTracking.list does not support: '.implode(', ', $unknown)
                .'. Supported: page_size, page_number, sort, sort_order, include.'
            );
        }

        // `includes` was ignored before v2.2.11; both keys are read now, and
        // every include — option or fluent — is checked.
        $pending = $this->getPendingIncludes();
        $this->pendingIncludes = [];
        $includes = $this->assertIncludes(
            [...(array) ($this->resolveIncludesOption($options) ?? []), ...$pending],
            self::INCLUDES,
            'timeTracking.list'
        );

        $params = $this->buildQueryParams(
            [],
            $filters,
            $options['sort'] ?? null,
            $options['sort_order'] ?? 'asc',
            $options['page_size'] ?? 20,
            $options['page_number'] ?? 1,
            $includes === [] ? null : $includes
        );

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get time tracking entry information
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        $pending = $this->getPendingIncludes();
        $this->pendingIncludes = [];
        $includes = $this->assertIncludes(
            [...(array) ($includes ?? []), ...$pending],
            self::INCLUDES,
            'timeTracking.info'
        );

        $params = $this->applyIncludes($params, $includes);

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Create a new time tracking entry
     * Supports three variants:
     * 1. started_at + duration
     * 2. started_at + ended_at
     * 3. started_on + duration (duration tracking mode)
     */
    public function create(array $data): array
    {
        $validatedData = $this->validateTimeTrackingData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.add', $validatedData);
    }

    /**
     * Update a time tracking entry
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $validatedData = $this->validateTimeTrackingData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $validatedData);
    }

    /**
     * Delete a time tracking entry
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Resume a timer based on previously tracked time
     */
    public function resume(string $id, ?string $startedAt = null): array
    {
        $params = ['id' => $id];

        if ($startedAt !== null) {
            $this->assertDateTime($startedAt, 'started_at');

            $params['started_at'] = $startedAt;
        }

        return $this->api->request('POST', $this->getBasePath().'.resume', $params);
    }

    /**
     * Filter entries by user
     */
    public function forUser(string $userId, array $options = []): array
    {
        return $this->list(
            array_merge(['user_id' => $userId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter entries by subject
     *
     * Note that `nextgenTask` is not accepted here even though time can be
     * tracked against one — the API offers no such filter value. Use
     * relatedTo() for project-side filtering.
     *
     * @throws InvalidArgumentException When the subject type is not a valid filter value
     */
    public function forSubject(string $subjectId, string $subjectType, array $options = []): array
    {
        $this->validateFilterSubjectType($subjectType);

        return $this->list(
            array_merge(
                ['subject' => ['id' => $subjectId, 'type' => $subjectType]],
                $options['filters'] ?? []
            ),
            $options
        );
    }

    /**
     * Filter entries by subject types
     *
     * @throws InvalidArgumentException When a subject type is not a valid filter value
     */
    public function forSubjectTypes(array $subjectTypes, array $options = []): array
    {
        foreach ($subjectTypes as $type) {
            // null matches tracked time without a subject
            if ($type !== null) {
                $this->validateFilterSubjectType($type);
            }
        }

        return $this->list(
            array_merge(['subject_types' => $subjectTypes], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter entries by date range (started)
     */
    public function betweenDates(string $startDate, string $endDate, array $options = []): array
    {
        return $this->list(
            array_merge([
                'started_after' => $startDate,
                'started_before' => $endDate,
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter entries by ended date range
     */
    public function endedBetween(string $startDate, string $endDate, array $options = []): array
    {
        return $this->list(
            array_merge([
                'ended_after' => $startDate,
                'ended_before' => $endDate,
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter entries related to a milestone, project, or new projects entity
     *
     * Valid type values: milestone, project, nextgenProject, nextgenProjectGroup
     */
    public function relatedTo(string $entityId, string $entityType, array $options = []): array
    {
        if (! in_array($entityType, $this->validRelatesToTypes)) {
            throw new InvalidArgumentException(
                'Invalid relates_to type. Must be one of: '.implode(', ', $this->validRelatesToTypes)
            );
        }

        return $this->list(
            array_merge(
                ['relates_to' => ['id' => $entityId, 'type' => $entityType]],
                $options['filters'] ?? []
            ),
            $options
        );
    }

    /**
     * Fluent interface: Include materials
     */
    public function withMaterials(): self
    {
        return $this->with('materials');
    }

    /**
     * Fluent interface: Include relates_to
     */
    public function withRelations(): self
    {
        return $this->with('relates_to');
    }

    /**
     * Validate a create or update body against the specification
     *
     * @throws InvalidArgumentException
     */
    protected function validateTimeTrackingData(array $data, string $operation): array
    {
        if ($operation === 'update') {
            if (! isset($data['id'])) {
                throw new InvalidArgumentException('ID is required for update operation');
            }

            $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], 'timeTracking.update');
            $this->validateUpdateTiming($data);
        } else {
            $this->rejectUnknownFields($data, self::ADD_FIELDS, 'timeTracking.add');
            $this->validateTimeTrackingVariant($data);
        }

        // subject is nullable on update: null unlinks it
        if (isset($data['subject'])) {
            if (! is_array($data['subject'])) {
                throw new InvalidArgumentException('Subject must contain both id and type');
            }

            $this->validateSubject($data['subject']);
        }

        if (isset($data['work_type_id']) && ! $this->isValidUuid($data['work_type_id'])) {
            throw new InvalidArgumentException('Invalid work_type_id format. Must be a valid UUID');
        }

        if (isset($data['user_id']) && ! $this->isValidUuid($data['user_id'])) {
            throw new InvalidArgumentException('Invalid user_id format. Must be a valid UUID');
        }

        if (isset($data['invoiceable']) && ! is_bool($data['invoiceable'])) {
            throw new InvalidArgumentException('Invoiceable must be a boolean value');
        }

        if (isset($data['duration']) && (! is_int($data['duration']) || $data['duration'] <= 0)) {
            throw new InvalidArgumentException('Duration must be a positive integer (seconds)');
        }

        foreach (['started_at', 'ended_at'] as $field) {
            if (isset($data[$field])) {
                $this->assertDateTime($data[$field], $field);
            }
        }

        if (isset($data['started_on']) && ! $this->isDate($data['started_on'])) {
            throw new InvalidArgumentException('started_on must be a date in YYYY-MM-DD format');
        }

        if (isset($data['started_at'], $data['ended_at']) && strtotime($data['ended_at']) <= strtotime($data['started_at'])) {
            throw new InvalidArgumentException('ended_at must be after started_at');
        }

        return $data;
    }

    /**
     * timeTracking.update requires `duration` and exactly one of `started_at`
     * or `started_on` on every call: an update restates the timing. Before
     * v2.2.11 an update without them was sent, and the API refused it.
     *
     * @throws InvalidArgumentException
     */
    protected function validateUpdateTiming(array $data): void
    {
        if (! isset($data['duration'])) {
            throw new InvalidArgumentException(
                'timeTracking.update requires duration (in seconds) on every call, '
                .'together with started_at or started_on.'
            );
        }

        if (isset($data['started_at']) === isset($data['started_on'])) {
            throw new InvalidArgumentException(
                'timeTracking.update requires exactly one of started_at or started_on.'
            );
        }
    }

    /**
     * Validate the timing of a new entry
     *
     * timeTracking.add takes one of three shapes:
     * 1) started_at + duration
     * 2) started_at + ended_at
     * 3) started_on + duration (only with duration time tracking enabled)
     *
     * @throws InvalidArgumentException
     */
    protected function validateTimeTrackingVariant(array $data): void
    {
        $hasStartedAt = isset($data['started_at']);
        $hasEndedAt = isset($data['ended_at']);
        $hasStartedOn = isset($data['started_on']);
        $hasDuration = isset($data['duration']);

        $valid = ($hasStartedAt && $hasEndedAt && ! $hasDuration && ! $hasStartedOn)
            || ($hasStartedAt && $hasDuration && ! $hasEndedAt && ! $hasStartedOn)
            || ($hasStartedOn && $hasDuration && ! $hasStartedAt && ! $hasEndedAt);

        if (! $valid) {
            throw new InvalidArgumentException(
                'Invalid time tracking variant. Must provide either: '.
                '1) started_at + duration, '.
                '2) started_at + ended_at, or '.
                '3) started_on + duration'
            );
        }
    }

    /**
     * An ISO 8601 datetime with a timezone (offset or Z)
     *
     * @throws InvalidArgumentException
     */
    protected function assertDateTime(mixed $value, string $field): void
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:\d{2})$/';

        if (! is_string($value) || ! preg_match($pattern, $value) || strtotime($value) === false) {
            throw new InvalidArgumentException(
                "{$field} must be an ISO 8601 datetime with a timezone, e.g. 2026-01-15T10:00:00+01:00"
            );
        }
    }

    protected function isDate(mixed $value): bool
    {
        $parsed = is_string($value) ? \DateTime::createFromFormat('!Y-m-d', $value) : false;

        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }

    /**
     * Validate subject structure for create and update
     *
     * Writes accept a wider set of subject types than the list filter does —
     * see $availableSubjectTypes and $filterSubjectTypes.
     */
    protected function validateSubject(array $subject): void
    {
        if (! isset($subject['id']) || ! isset($subject['type'])) {
            throw new InvalidArgumentException('Subject must contain both id and type');
        }

        if (! $this->isValidUuid($subject['id'])) {
            throw new InvalidArgumentException('Subject id must be a valid UUID');
        }

        if (! in_array($subject['type'], $this->availableSubjectTypes)) {
            throw new InvalidArgumentException(
                'Invalid subject type. Must be one of: '.implode(', ', $this->availableSubjectTypes)
            );
        }
    }

    /**
     * Build the filter object for the API request
     *
     * Unknown filter keys throw rather than being forwarded. The API ignores
     * filter keys it does not recognise and answers 200 with the full unfiltered
     * result set, which is indistinguishable from a correct response — a
     * `updated_since` filter that was never applied returns every entry in the
     * account rather than the requested window.
     *
     * `updated_since`, `invoiced` and `invoiceable` are the confirmed cases.
     * None of them exist on timeTracking.list.
     *
     * Note that null, empty-string and empty-array values are skipped before
     * validation, so `['user_id' => null]` is treated as "not set" rather than
     * as an error.
     *
     * @throws InvalidArgumentException When a filter key or value is not supported
     */
    protected function applyFilters(array $params = [], array $filters = [])
    {
        if (empty($filters)) {
            return $params;
        }

        $apiFilters = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }

            switch ($key) {
                case 'ids':
                    $apiFilters['ids'] = is_array($value) ? $value : [$value];
                    break;

                case 'user_id':
                case 'started_after':
                case 'started_before':
                case 'ended_after':
                case 'ended_before':
                    $apiFilters[$key] = $value;
                    break;

                case 'subject':
                    if (! is_array($value) || ! isset($value['id']) || ! isset($value['type'])) {
                        throw new InvalidArgumentException(
                            'The subject filter must contain both id and type.'
                        );
                    }

                    $this->validateFilterSubjectType($value['type']);

                    $apiFilters['subject'] = $value;
                    break;

                case 'subject_types':
                    if (! is_array($value)) {
                        throw new InvalidArgumentException('The subject_types filter must be an array.');
                    }

                    // "For tracked time without a subject type, provide null"
                    foreach ($value as $type) {
                        if ($type !== null) {
                            $this->validateFilterSubjectType($type);
                        }
                    }

                    $apiFilters['subject_types'] = array_values($value);
                    break;

                case 'relates_to':
                    if (! is_array($value) || ! isset($value['id']) || ! isset($value['type'])) {
                        throw new InvalidArgumentException(
                            'The relates_to filter must contain both id and type.'
                        );
                    }

                    if (! in_array($value['type'], $this->validRelatesToTypes, true)) {
                        throw new InvalidArgumentException(
                            "Invalid relates_to type: {$value['type']}. Must be one of: "
                            .implode(', ', $this->validRelatesToTypes).'.'
                        );
                    }

                    $apiFilters['relates_to'] = $value;
                    break;

                default:
                    throw new InvalidArgumentException(
                        "Invalid filter key '{$key}' for timeTracking.list. Supported filters: "
                        .implode(', ', array_keys($this->commonFilters)).'.'
                    );
            }
        }

        if (! empty($apiFilters)) {
            $params['filter'] = $apiFilters;
        }

        return $params;
    }

    /**
     * Validate a subject type used as a list filter value
     *
     * @throws InvalidArgumentException
     */
    protected function validateFilterSubjectType(mixed $type): void
    {
        if (! is_string($type) || ! in_array($type, $this->filterSubjectTypes, true)) {
            $message = 'Invalid subject type for the timeTracking.list filter: '
                .(is_string($type) ? $type : gettype($type))
                .'. Must be one of: '.implode(', ', $this->filterSubjectTypes).'.';

            if ($type === 'nextgenTask') {
                $message .= ' Time can be tracked against a nextgenTask, but the API '
                    .'does not accept it as a filter value. Use relates_to instead.';
            }

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Apply sorting to the API request
     *
     * The API accepts a single sort field, `starts_on`. Anything else is
     * silently ignored by the API, so it is rejected here.
     *
     * @throws InvalidArgumentException When the sort field or order is not supported
     */
    protected function applySorting(array $params = [], $sort = null, $order = 'asc'): array
    {
        // Handle null sort - return params as-is
        if (! $sort) {
            return $params;
        }

        // If sort is already a list of sort objects
        if (is_array($sort) && isset($sort[0]['field'])) {
            $params['sort'] = array_map(fn (array $entry) => [
                'field' => $this->validateSortField($entry['field']),
                'order' => $this->normaliseSortOrder($entry['order'] ?? $order),
            ], $sort);

            return $params;
        }

        // If sort is an array with 'field' and 'order' keys
        if (is_array($sort) && isset($sort['field'])) {
            $params['sort'] = [[
                'field' => $this->validateSortField($sort['field']),
                'order' => $this->normaliseSortOrder($sort['order'] ?? $order),
            ]];

            return $params;
        }

        // If sort is a string (field name), convert to proper structure
        if (is_string($sort)) {
            $params['sort'] = [[
                'field' => $this->validateSortField($sort),
                'order' => $this->normaliseSortOrder($order),
            ]];

            return $params;
        }

        throw new InvalidArgumentException(
            'Unrecognised sort format. Pass a field name, '
            ."['field' => ..., 'order' => ...], or a list of those."
        );
    }

    /**
     * Ensure a sort field is one the API accepts
     *
     * @throws InvalidArgumentException
     */
    protected function validateSortField(mixed $field): string
    {
        if (! is_string($field) || ! array_key_exists($field, $this->availableSortFields)) {
            throw new InvalidArgumentException(
                'Invalid sort field: '.(is_string($field) ? $field : gettype($field))
                .'. timeTracking.list accepts: '
                .implode(', ', array_keys($this->availableSortFields)).'.'
            );
        }

        return $field;
    }

    /**
     * Ensure a sort order is asc or desc
     *
     * @throws InvalidArgumentException
     */
    protected function normaliseSortOrder(mixed $order): string
    {
        if (! is_string($order)) {
            throw new InvalidArgumentException('Sort order must be a string: asc or desc.');
        }

        $normalised = strtolower($order);

        if (! in_array($normalised, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Invalid sort order: {$order}. Must be asc or desc.");
        }

        return $normalised;
    }

    /**
     * Helper: Check if string is valid UUID
     */
    protected function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
