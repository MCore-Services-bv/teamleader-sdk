<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class CallOutcomes extends Resource
{
    protected string $description = 'Manage call outcomes in Teamleader Focus Calendar';

    // Resource capabilities - CallOutcomes are typically read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = false;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none for call outcomes)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    /**
     * callOutcomes.list takes no filter at all. Until v2.2.12 an `ids` filter
     * was advertised and sent, the API ignored it, and byIds() returned every
     * outcome. byIds() now filters the full list client-side.
     */
    protected array $commonFilters = [];

    // Usage examples specific to call outcomes
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all call outcomes',
            'code' => '$callOutcomes = $teamleader->callOutcomes()->list();',
        ],
        'filter_by_ids' => [
            'description' => 'Get specific call outcomes by IDs',
            'code' => '$callOutcomes = $teamleader->callOutcomes()->byIds(["uuid1", "uuid2"]);',
        ],
        'find_by_name' => [
            'description' => 'Find call outcome by name',
            'code' => '$outcome = $teamleader->callOutcomes()->findByName("Succesvol gesprek");',
        ],
    ];

    /**
     * Get the base path for the call outcomes resource
     */
    protected function getBasePath(): string
    {
        return 'callOutcomes';
    }

    /**
     * List call outcomes
     *
     * @param  array  $filters  None; callOutcomes.list takes no filter. Passing any throws.
     * @param  array  $options  page_size, page_number
     *
     * @throws InvalidArgumentException On a filter or an unsupported option
     */
    public function list(array $filters = [], array $options = []): array
    {
        if ($filters !== []) {
            throw new InvalidArgumentException(
                'callOutcomes.list takes no filters; the API would ignore them and return every outcome. '
                .'Use byIds() to pick outcomes by ID.'
            );
        }

        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'filters']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'callOutcomes.list does not support: '.implode(', ', $unknown).'. Supported: page_size, page_number.'
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.list', [
            'page' => [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ],
        ]);
    }

    /**
     * Get call outcomes by ID
     *
     * The API cannot filter outcomes, so this reads every page and keeps the
     * requested IDs. Outcomes are a short, account-level list, so that is one
     * request in practice.
     *
     * @param  array  $ids  Call outcome UUIDs
     * @return array{data: list<array>}
     */
    public function byIds(array $ids, array $options = []): array
    {
        $wanted = array_flip($ids);

        return ['data' => array_values(array_filter(
            $this->everyOutcome(),
            fn (array $outcome) => isset($outcome['id'], $wanted[$outcome['id']])
        ))];
    }

    /**
     * Every call outcome, across pages
     *
     * @return list<array>
     */
    private function everyOutcome(): array
    {
        $outcomes = [];

        for ($page = 1; $page <= 50; $page++) {
            $batch = $this->list([], ['page_size' => 100, 'page_number' => $page])['data'] ?? [];
            array_push($outcomes, ...$batch);

            if (count($batch) < 100) {
                break;
            }
        }

        return $outcomes;
    }

    /**
     * Find call outcome by name
     */
    public function findByName(string $name, array $options = []): ?array
    {
        // Reads every page: before v2.2.12 only the first 20 outcomes were searched.
        foreach ($this->everyOutcome() as $outcome) {
            if (isset($outcome['name']) && strcasecmp($outcome['name'], $name) === 0) {
                return $outcome;
            }
        }

        return null;
    }
}
