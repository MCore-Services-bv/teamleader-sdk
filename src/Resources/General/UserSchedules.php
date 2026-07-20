<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use BadMethodCallException;
use DateTime;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

/**
 * UserSchedules resource.
 *
 * Wraps the `userSchedules.list` endpoint (added by Teamleader in the
 * 2026-06-19 changelog). Returns the working schedules of one or more users,
 * expanded per day over a date range of at most 7 days.
 *
 * This is the successor to `users.getWeekSchedule` (still available on the
 * Users resource, but deprecated in favour of this endpoint).
 *
 * Only available on accounts with the *Weekly working schedule* feature.
 */
class UserSchedules extends Resource
{
    protected string $description = 'Retrieve per-day working schedules for one or more users in Teamleader Focus';

    // Resource capabilities — read-only, list-only.
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none).
    protected array $availableIncludes = [];

    // Default includes.
    protected array $defaultIncludes = [];

    // Common filters based on API documentation.
    protected array $commonFilters = [
        'user_ids' => 'Required. Array of user UUIDs to return schedules for',
        'from' => 'Required. Start of the date range (inclusive), YYYY-MM-DD',
        'until' => 'Required. End of the date range (inclusive), YYYY-MM-DD. On or after "from"; range may span at most 7 days',
    ];

    // Maximum number of days the (inclusive) date range may span.
    protected int $maxRangeDays = 7;

    // Usage examples specific to user schedules.
    protected array $usageExamples = [
        'list_schedules' => [
            'description' => 'Get per-day schedules for several users over a week',
            'code' => '$schedules = $teamleader->userSchedules()->list([
    \'user_ids\' => [\'user-uuid-1\', \'user-uuid-2\'],
    \'from\'     => \'2026-06-01\',
    \'until\'    => \'2026-06-07\',
]);',
        ],
        'list_for_one_user' => [
            'description' => 'Get the schedule for a single user',
            'code' => '$schedule = $teamleader->userSchedules()->forUser(\'user-uuid\', \'2026-06-01\', \'2026-06-07\');',
        ],
    ];

    /**
     * Convenience: schedule for a single user over a date range.
     *
     * @param  string  $userId  User UUID
     * @param  string  $from  Start date (inclusive), YYYY-MM-DD
     * @param  string  $until  End date (inclusive), YYYY-MM-DD
     * @param  array  $options  Pagination options
     */
    public function forUser(string $userId, string $from, string $until, array $options = []): array
    {
        return $this->list([
            'user_ids' => [$userId],
            'from' => $from,
            'until' => $until,
        ], $options);
    }

    /**
     * List the per-day working schedules of one or more users.
     *
     * Posts to `userSchedules.list`. Non-working days are omitted from each
     * user's schedule. Only available with the *Weekly working schedule* feature.
     *
     * @param  array  $filters  Required filter keys:
     *                          - user_ids (array<string>, required): user UUIDs
     *                          - from (string, required): start date, YYYY-MM-DD (inclusive)
     *                          - until (string, required): end date, YYYY-MM-DD (inclusive, <= 7 days after "from")
     * @param  array  $options  Pagination options: page_size (default 20), page_number (default 1)
     *
     * @throws InvalidArgumentException When required filters are missing or the date range is invalid
     */
    public function list(array $filters = [], array $options = []): array
    {
        $this->validateListFilters($filters);

        $params = [
            'filter' => [
                'user_ids' => array_values($filters['user_ids']),
                'from' => $filters['from'],
                'until' => $filters['until'],
            ],
        ];

        // Apply pagination when provided.
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Validate the filters passed to list().
     *
     * @throws InvalidArgumentException
     */
    protected function validateListFilters(array $filters): void
    {
        // user_ids
        if (empty($filters['user_ids']) || ! is_array($filters['user_ids'])) {
            throw new InvalidArgumentException('userSchedules.list requires a non-empty "user_ids" array.');
        }
        foreach ($filters['user_ids'] as $userId) {
            if (! is_string($userId) || $userId === '') {
                throw new InvalidArgumentException('Each entry in "user_ids" must be a non-empty user UUID string.');
            }
        }

        // from / until presence
        if (empty($filters['from'])) {
            throw new InvalidArgumentException('userSchedules.list requires a "from" date (YYYY-MM-DD).');
        }
        if (empty($filters['until'])) {
            throw new InvalidArgumentException('userSchedules.list requires an "until" date (YYYY-MM-DD).');
        }

        // date format
        $from = $this->parseDate($filters['from']);
        $until = $this->parseDate($filters['until']);
        if ($from === null) {
            throw new InvalidArgumentException('Invalid "from" date format. Use YYYY-MM-DD.');
        }
        if ($until === null) {
            throw new InvalidArgumentException('Invalid "until" date format. Use YYYY-MM-DD.');
        }

        // ordering + span (inclusive range of at most 7 days => diff 0..6 days)
        if ($until < $from) {
            throw new InvalidArgumentException('"until" must be on or after "from".');
        }
        $spanDays = (int) $from->diff($until)->format('%a');
        if ($spanDays > ($this->maxRangeDays - 1)) {
            throw new InvalidArgumentException(
                sprintf('The date range may span at most %d days.', $this->maxRangeDays)
            );
        }
    }

    /**
     * Parse a strict YYYY-MM-DD date, returning null when invalid.
     */
    protected function parseDate(string $date): ?DateTime
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);

        return ($d && $d->format('Y-m-d') === $date) ? $d : null;
    }

    /**
     * Get the base path for the user schedules resource.
     */
    protected function getBasePath(): string
    {
        return 'userSchedules';
    }

    /**
     * Convenience: schedules for several users over a date range.
     *
     * @param  array  $userIds  Array of user UUIDs
     * @param  string  $from  Start date (inclusive), YYYY-MM-DD
     * @param  string  $until  End date (inclusive), YYYY-MM-DD
     * @param  array  $options  Pagination options
     */
    public function forUsers(array $userIds, string $from, string $until, array $options = []): array
    {
        return $this->list([
            'user_ids' => $userIds,
            'from' => $from,
            'until' => $until,
        ], $options);
    }

    /**
     * info() is not supported — this endpoint only exposes .list.
     */
    public function info($id, $includes = null): array
    {
        throw new BadMethodCallException('UserSchedules does not support info(). Use list() with user_ids, from and until.');
    }

    /**
     * create() is not supported.
     */
    public function create(array $data): array
    {
        throw new BadMethodCallException('UserSchedules is read-only; create() is not supported.');
    }

    /**
     * update() is not supported.
     */
    public function update($id, array $data): array
    {
        throw new BadMethodCallException('UserSchedules is read-only; update() is not supported.');
    }

    /**
     * delete() is not supported.
     */
    public function delete($id, ...$additionalParams): array
    {
        throw new BadMethodCallException('UserSchedules is read-only; delete() is not supported.');
    }
}
