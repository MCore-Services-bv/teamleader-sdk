<?php

namespace McoreServices\TeamleaderSDK\Resources\Products;

use Illuminate\Support\Collection;
use McoreServices\TeamleaderSDK\Resources\Resource;

class UnitOfMeasure extends Resource
{
    protected string $description = 'Manage units of measure in Teamleader Focus — read-only, and unpaginated: list() returns every unit in one response and accepts no arguments';

    // Resource capabilities - Units of Measure are read-only based on API docs
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false; // No pagination mentioned in API docs

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = false; // No filters mentioned in API docs

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters - none available based on API documentation
    protected array $commonFilters = [];

    // Usage examples specific to units of measure
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all units of measure',
            'code' => '$unitsOfMeasure = $teamleader->unitsOfMeasure()->list();',
        ],
        'get_by_name' => [
            'description' => 'Find unit of measure by name',
            'code' => '$unit = $teamleader->unitsOfMeasure()->findByName("piece");',
        ],
        'get_all_as_options' => [
            'description' => 'Get units as key-value pairs for dropdowns',
            'code' => '$options = $teamleader->unitsOfMeasure()->asOptions();',
        ],
    ];

    /**
     * Get the base path for the units of measure resource
     */
    protected function getBasePath(): string
    {
        return 'unitsOfMeasure';
    }

    /**
     * List all units of measure
     *
     * `unitsOfMeasure.list` takes no request body at all — the API specification
     * declares no filter, page or sort parameter for it. The inherited signature
     * still offers them, so arguments are rejected rather than discarded: before
     * v2.1.2 they were dropped silently, and every call returned the complete
     * list regardless of what was asked for.
     *
     * @param  array  $filters  Must be empty — this endpoint accepts no filters
     * @param  array  $options  Must be empty — this endpoint accepts no sorting or pagination
     *
     * @throws \InvalidArgumentException When any argument is passed
     */
    public function list(array $filters = [], array $options = []): array
    {
        $this->rejectUnsupportedListArguments($filters, $options);

        return $this->api->request('POST', $this->getBasePath().'.list', []);
    }

    /**
     * Units of measure have no info endpoint
     *
     * `unitsOfMeasure.list` is the only endpoint Teamleader exposes for this
     * resource. Use findById(), which resolves against the full list.
     *
     * @param  string  $id
     * @param  mixed  $includes
     *
     * @throws \InvalidArgumentException Always
     */
    public function info($id, $includes = null): array
    {
        throw new \InvalidArgumentException(
            'unitsOfMeasure has no info endpoint — list() is the only one the API exposes. '
            .'Use findById() to resolve a single unit from the full list.'
        );
    }

    /**
     * Find a unit of measure by name (case-insensitive search)
     *
     * @param  string  $name  The name to search for
     * @return array|null The unit of measure or null if not found
     */
    public function findByName(string $name): ?array
    {
        $response = $this->list();

        if (empty($response['data'])) {
            return null;
        }

        $searchName = strtolower(trim($name));

        foreach ($response['data'] as $unit) {
            if (isset($unit['name']) && strtolower(trim($unit['name'])) === $searchName) {
                return $unit;
            }
        }

        return null;
    }

    /**
     * Find a unit of measure by ID
     *
     * @param  string  $id  The ID to search for
     * @return array|null The unit of measure or null if not found
     */
    public function findById(string $id): ?array
    {
        $response = $this->list();

        if (empty($response['data'])) {
            return null;
        }

        foreach ($response['data'] as $unit) {
            if (isset($unit['id']) && $unit['id'] === $id) {
                return $unit;
            }
        }

        return null;
    }

    /**
     * Get units of measure as key-value pairs (id => name) for use in dropdowns
     */
    public function asOptions(): array
    {
        $response = $this->list();

        if (empty($response['data'])) {
            return [];
        }

        $options = [];
        foreach ($response['data'] as $unit) {
            if (isset($unit['id']) && isset($unit['name'])) {
                $options[$unit['id']] = $unit['name'];
            }
        }

        return $options;
    }

    /**
     * Get units of measure as collection for easier manipulation
     */
    public function asCollection(): Collection
    {
        $response = $this->list();

        return collect($response['data'] ?? []);
    }

    /**
     * Check if a unit exists by name
     */
    public function exists(string $name): bool
    {
        return $this->findByName($name) !== null;
    }

    /**
     * Get the total count of units of measure
     */
    public function count(): int
    {
        $response = $this->list();

        return count($response['data'] ?? []);
    }
}
