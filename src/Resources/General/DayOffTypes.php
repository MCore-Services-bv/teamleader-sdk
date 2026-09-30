<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use Exception;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class DayOffTypes extends Resource
{
    use ValidatesWritePayload;

    /** Body fields dayOffTypes.create accepts; update takes the same plus `id` */
    public const WRITE_FIELDS = ['name', 'color', 'date_validity'];

    protected string $description = 'Manage day off types in Teamleader Focus';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;  // API doesn't show pagination

    protected bool $supportsFiltering = false;  // API doesn't show filters

    protected bool $supportsSorting = false;    // API doesn't show sorting

    protected bool $supportsSideloading = false; // No includes shown

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Common filters (none based on API documentation)
    protected array $commonFilters = [];

    // Usage examples specific to day off types
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all day off types',
            'code' => '$dayOffTypes = $teamleader->dayOffTypes()->list();',
        ],
        'create_basic' => [
            'description' => 'Create a basic day off type',
            'code' => '$dayOffType = $teamleader->dayOffTypes()->create([\'name\' => \'Vacation\', \'color\' => \'#00B2B2\']);',
        ],
        'create_with_validity' => [
            'description' => 'Create day off type with date validity',
            'code' => '$dayOffType = $teamleader->dayOffTypes()->create([
    \'name\' => \'Summer Leave\',
    \'color\' => \'#FFB600\',
    \'date_validity\' => [
        \'from\' => \'2024-06-01\',
        \'until\' => \'2024-08-31\'
    ]
]);',
        ],
        'update_type' => [
            'description' => 'Update an existing day off type',
            'code' => '$result = $teamleader->dayOffTypes()->update(\'uuid-here\', [\'name\' => \'Updated Name\', \'color\' => \'#FF0000\']);',
        ],
        'delete_type' => [
            'description' => 'Delete a day off type',
            'code' => '$result = $teamleader->dayOffTypes()->delete(\'uuid-here\');',
        ],
    ];

    /**
     * List all day off types
     *
     * `dayOffTypes.list` takes no request body — no filter, page or sort
     * parameter exists for it. Arguments are rejected rather than discarded.
     *
     * @param  array  $filters  Must be empty — this endpoint accepts no filters
     * @param  array  $options  Must be empty — this endpoint accepts no sorting or pagination
     *
     * @throws InvalidArgumentException When any argument is passed
     */
    public function list(array $filters = [], array $options = []): array
    {
        $this->rejectUnsupportedListArguments($filters, $options);

        return $this->api->request('POST', $this->getBasePath().'.list');
    }

    /**
     * Get the base path for the day off types resource
     */
    protected function getBasePath(): string
    {
        return 'dayOffTypes';
    }

    /**
     * Delete a day off type
     *
     * @param  string  $id  Day off type UUID
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Create a day off type with date validity period
     *
     * @param  string  $name  Name of the day off type
     * @param  string  $color  Hex color code (optional)
     * @param  string  $fromDate  Start date (YYYY-MM-DD)
     * @param  string  $untilDate  End date (YYYY-MM-DD, optional)
     */
    public function createWithValidity(string $name, ?string $color = null, ?string $fromDate = null, ?string $untilDate = null): array
    {
        $data = ['name' => $name];

        if ($color) {
            $data['color'] = $color;
        }

        if ($fromDate) {
            $data['date_validity'] = ['from' => $fromDate];

            if ($untilDate) {
                $data['date_validity']['until'] = $untilDate;
            }
        }

        return $this->create($data);
    }

    /**
     * Create a new day off type
     *
     * @param  array  $data  Day off type data
     * @return array
     */
    public function create(array $data)
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Name is required for creating a day off type');
        }

        // Validate data before sending
        $data = $this->validateData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Validate day off type data
     *
     * Empty strings and empty arrays are stripped, but `null` is preserved:
     * `date_validity` is declared nullable on dayOffTypes.update, so passing
     * null is how a caller clears the validity period. Stripping it would drop
     * the clear silently and the field would keep its old value with no error.
     * This is the same defect fixed for Contacts and Companies in v1.2.6.
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Remove empty values, but keep null — it signals a field clear to the API
        $data = array_filter($data, function ($value, $key) {
            if ($key === 'id') {
                return true;
            }

            if ($value === null) {
                return true;
            }

            return $value !== '' && $value !== [];
        }, ARRAY_FILTER_USE_BOTH);

        $allowed = $operation === 'update' ? [...self::WRITE_FIELDS, 'id'] : self::WRITE_FIELDS;
        $this->rejectUnknownFields($data, $allowed, 'dayOffTypes.'.$operation);

        // Validate color format if provided
        if (isset($data['color']) && ! empty($data['color'])) {
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
                throw new InvalidArgumentException('Color must be a valid hex color code (e.g., #00B2B2)');
            }
        }

        // Validate date validity format if provided
        if (isset($data['date_validity']) && is_array($data['date_validity'])) {
            $this->rejectUnknownFields($data['date_validity'], ['from', 'until'], 'dayOffTypes.'.$operation.' date_validity');

            if (! isset($data['date_validity']['from'])) {
                throw new InvalidArgumentException('Date validity needs a "from" date');
            }
            if (isset($data['date_validity']['from'])) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_validity']['from'])) {
                    throw new InvalidArgumentException('Date validity "from" must be in YYYY-MM-DD format');
                }
            }

            if (isset($data['date_validity']['until'])) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_validity']['until'])) {
                    throw new InvalidArgumentException('Date validity "until" must be in YYYY-MM-DD format');
                }

                // Validate that until date is after from date
                if (isset($data['date_validity']['from'])) {
                    // A single-day validity (until = from) was rejected before v2.2.14.
                    if (strtotime($data['date_validity']['until']) < strtotime($data['date_validity']['from'])) {
                        throw new InvalidArgumentException('Date validity "until" must be on or after the "from" date');
                    }
                }
            }
        }

        // Validate name length
        if (isset($data['name']) && strlen($data['name']) > 255) {
            throw new InvalidArgumentException('Name cannot be longer than 255 characters');
        }

        return $data;
    }

    /**
     * Update day off type color
     *
     * @param  string  $id  Day off type UUID
     * @param  string  $color  Hex color code
     */
    public function updateColor(string $id, string $color): array
    {
        return $this->update($id, ['color' => $color]);
    }

    /**
     * Update an existing day off type
     *
     * @param  string  $id  Day off type UUID
     * @param  array  $data  Updated data
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;

        // Validate data before sending
        $data = $this->validateData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Update day off type validity dates
     *
     * @param  string  $id  Day off type UUID
     * @param  string  $fromDate  Start date (YYYY-MM-DD)
     * @param  string  $untilDate  End date (YYYY-MM-DD, optional)
     */
    public function updateValidity(string $id, string $fromDate, ?string $untilDate = null): array
    {
        $data = [
            'date_validity' => ['from' => $fromDate],
        ];

        if ($untilDate) {
            $data['date_validity']['until'] = $untilDate;
        }

        return $this->update($id, $data);
    }

    /**
     * Get available color options for day off types
     *
     * @return array Common color options
     */
    public function getCommonColors(): array
    {
        return [
            '#00B2B2' => 'Teal',
            '#FF6B6B' => 'Red',
            '#4ECDC4' => 'Turquoise',
            '#45B7D1' => 'Blue',
            '#96CEB4' => 'Green',
            '#FFEAA7' => 'Yellow',
            '#DDA0DD' => 'Plum',
            '#98D8C8' => 'Mint',
            '#F7DC6F' => 'Light Yellow',
            '#BB8FCE' => 'Light Purple',
        ];
    }

    /**
     * Bulk create day off types
     *
     * @param  array  $dayOffTypes  Array of day off type data
     * @return array Results of all create operations
     */
    public function bulkCreate(array $dayOffTypes): array
    {
        $results = [];

        foreach ($dayOffTypes as $index => $dayOffTypeData) {
            try {
                $results[] = [
                    'index' => $index,
                    'success' => true,
                    'data' => $this->create($dayOffTypeData),
                ];
            } catch (Exception $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => $dayOffTypeData,
                ];
            }
        }

        return $results;
    }

    /**
     * Get validation rules for day off type data
     *
     * @return array Laravel validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'date_validity' => 'nullable|array',
            'date_validity.from' => 'required_with:date_validity|date_format:Y-m-d',
            'date_validity.until' => 'nullable|date_format:Y-m-d|after:date_validity.from',
        ];
    }

    /**
     * Override getSuggestedIncludes as day off types don't have includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Day off types don't have sideloadable relationships
    }
}
