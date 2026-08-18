<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Quotations extends Resource
{
    protected string $description = 'Manage quotations in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    // Neither quotations.list nor quotations.info declares an includes
    // parameter. Until v2.2.2 this was true and $availableIncludes advertised an
    // `expiry` include that the API has never accepted.
    protected bool $supportsSideloading = false;

    // Available includes for sideloading — none exist for this resource
    protected array $availableIncludes = [];

    /**
     * Filters accepted by quotations.list.
     *
     * Verified against @teamleader/focus-api-specification: `ids` is the only
     * one. `status` was declared here until v2.2.2 and is not a filter — the API
     * ignored it and returned every quotation. See byStatus().
     */
    protected array $commonFilters = [
        'ids' => 'Array of quotation UUIDs to filter by',
    ];

    // Quotation status values
    protected array $validStatuses = [
        'open',
        'accepted',
        'expired',
        'rejected',
        'closed',
    ];

    // Supported download formats
    protected array $supportedFormats = [
        'pdf',
    ];

    // Usage examples specific to quotations
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all quotations',
            'code' => '$quotations = $teamleader->quotations()->list();',
        ],
        'list_specific' => [
            'description' => 'Get specific quotations by ID',
            'code' => '$quotations = $teamleader->quotations()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'get_single' => [
            'description' => 'Get a single quotation',
            'code' => '$quotation = $teamleader->quotations()->info(\'quotation-uuid\');',
        ],
        'create' => [
            'description' => 'Create a new quotation',
            'code' => '$quotation = $teamleader->quotations()->create([\'deal_id\' => \'deal-uuid\', \'currency\' => [\'code\' => \'EUR\'], \'grouped_lines\' => [...]]);',
        ],
        'update' => [
            'description' => 'Update a quotation',
            'code' => '$teamleader->quotations()->update(\'quotation-uuid\', [\'grouped_lines\' => [...]]);',
        ],
        'accept' => [
            'description' => 'Accept a quotation',
            'code' => '$teamleader->quotations()->accept(\'quotation-uuid\');',
        ],
        'send' => [
            'description' => 'Send a quotation via email',
            'code' => '$teamleader->quotations()->send([\'quotations\' => [\'uuid1\'], \'from\' => [...], \'recipients\' => [...], \'subject\' => \'...\', \'content\' => \'...\', \'language\' => \'en\']);',
        ],
        'download' => [
            'description' => 'Download a quotation as PDF',
            'code' => '$download = $teamleader->quotations()->download(\'quotation-uuid\', \'pdf\');',
        ],
    ];

    /**
     * Get quotation information
     *
     * `quotations.info` declares no includes parameter — everything the endpoint
     * returns comes back automatically.
     *
     * @param  string  $id  Quotation UUID
     * @param  mixed  $includes  Not supported by this endpoint
     *
     * @throws InvalidArgumentException When includes are requested
     */
    public function info($id, $includes = null): array
    {
        if (! empty($includes) || ! empty($this->getPendingIncludes())) {
            throw new InvalidArgumentException(
                'quotations.info accepts no includes parameter. The `expiry` include '
                .'advertised before v2.2.2 does not exist in the API.'
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Get the base path for the quotations resource
     */
    protected function getBasePath(): string
    {
        return 'quotations';
    }

    /**
     * Create a new quotation
     *
     * @param  array  $data  Quotation data
     */
    public function create(array $data): array
    {
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Validate data for creating a quotation
     *
     * @throws InvalidArgumentException
     */
    private function validateCreateData(array $data): void
    {
        if (! isset($data['deal_id'])) {
            throw new InvalidArgumentException('deal_id is required to create a quotation');
        }

        if (! isset($data['grouped_lines']) && ! isset($data['text'])) {
            throw new InvalidArgumentException('A quotation needs either grouped_lines or text to be valid');
        }
    }

    /**
     * Update a quotation
     *
     * @param  string  $id  Quotation UUID
     * @param  array  $data  Updated quotation data
     */
    public function update(string $id, array $data): array
    {
        $data['id'] = $id;

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a quotation
     *
     * @param  string  $id  Quotation UUID
     */
    public function delete(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Mark a quotation as accepted
     *
     * @param  string  $id  Quotation UUID
     */
    public function accept(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.accept', ['id' => $id]);
    }

    /**
     * Send one or more quotations via email
     *
     * @param  array  $data  Send parameters including quotations, sender, recipients, subject, content, and language
     */
    public function send(array $data): array
    {
        $this->validateSendData($data);

        return $this->api->request('POST', $this->getBasePath().'.send', $data);
    }

    /**
     * Validate data for sending quotations
     *
     * @throws InvalidArgumentException
     */
    private function validateSendData(array $data): void
    {
        if (! isset($data['quotations']) || ! is_array($data['quotations']) || empty($data['quotations'])) {
            throw new InvalidArgumentException('quotations array is required and must not be empty');
        }

        if (! isset($data['from']) || ! isset($data['from']['sender'])) {
            throw new InvalidArgumentException('from.sender is required');
        }

        if (! isset($data['recipients']) || ! isset($data['recipients']['to']) || empty($data['recipients']['to'])) {
            throw new InvalidArgumentException('recipients.to is required and must not be empty');
        }

        if (! isset($data['subject'])) {
            throw new InvalidArgumentException('subject is required');
        }

        if (! isset($data['content'])) {
            throw new InvalidArgumentException('content is required');
        }

        if (! isset($data['language'])) {
            throw new InvalidArgumentException('language is required');
        }
    }

    /**
     * Download a quotation in a specific format
     *
     * @param  string  $id  Quotation UUID
     * @param  string  $format  Download format (default: 'pdf')
     * @return array Returns location URL and expiration time
     */
    public function download(string $id, string $format = 'pdf'): array
    {
        if (! in_array($format, $this->supportedFormats)) {
            throw new InvalidArgumentException(
                "Invalid format '{$format}'. Supported formats: ".implode(', ', $this->supportedFormats)
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.download', [
            'id' => $id,
            'format' => $format,
        ]);
    }

    /**
     * Get quotations by specific IDs
     *
     * @param  array  $ids  Array of quotation UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * List quotations with enhanced filtering and pagination
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination via page.size / page.number)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination. The SDK-wide convention is page_size / page_number;
        // this resource only understood a nested ['page' => ['size' => ...]]
        // array until v2.2.2, so the standard form was silently ignored and every
        // call returned the API default of 20.
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        } elseif (isset($options['page'])) {
            $params['page'] = $this->buildPagination($options['page']);
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $supported = array_keys($this->commonFilters);

        $unknown = array_diff(array_keys($filters), $supported);

        if ($unknown !== []) {
            $message = 'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key')
                .' for quotations.list: '.implode(', ', $unknown)
                .'. Supported: '.implode(', ', $supported).'.';

            if (in_array('status', $unknown, true)) {
                $message .= ' quotations.list has no status filter — the API ignores it '
                    .'and returns every quotation. Filter client-side on data[].status.';
            }

            throw new InvalidArgumentException($message);
        }

        $apiFilters = [];

        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? $filters['ids'] : [$filters['ids']];
        }

        return $apiFilters;
    }

    /**
     * Build pagination parameters
     */
    private function buildPagination(array $pagination): array
    {
        $page = [];

        if (isset($pagination['size'])) {
            $page['size'] = (int) $pagination['size'];
        }

        if (isset($pagination['number'])) {
            $page['number'] = (int) $pagination['number'];
        }

        return $page;
    }

    /**
     * Get quotations by status
     *
     * @deprecated since v2.2.2 — quotations.list has no `status` filter. The API
     * ignored it and returned every quotation, so this method never filtered
     * anything. Fetch and filter client-side on `data[].status`. Removed in v3.0.
     *
     * @param  string|array  $status  Single status or array of statuses
     *
     * @throws InvalidArgumentException Always
     */
    public function byStatus($status): array
    {
        throw new InvalidArgumentException(
            'quotations.list has no `status` filter — this method silently returned every '
            .'quotation. Fetch with list() and filter client-side on data[].status, '
            .'e.g. array_filter($result[\'data\'], fn ($q) => $q[\'status\'] === \'open\').'
        );
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created quotation ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created quotation',
                    'data.type' => 'Resource type (always "quotation")',
                ],
            ],
            'info' => [
                'description' => 'Complete quotation information',
                'fields' => [
                    'data.id' => 'Quotation UUID',
                    'data.deal' => 'Deal reference object',
                    'data.deal.id' => 'Deal UUID',
                    'data.deal.type' => 'Deal type string',
                    'data.grouped_lines' => 'Array of line item groups',
                    'data.status' => 'Quotation status (open, accepted, expired, rejected, closed)',
                    'data.name' => 'Quotation name',
                ],
            ],
            'list' => [
                'description' => 'Array of quotations with the same structure as info',
                'fields' => [
                    'data' => 'Array of quotation objects',
                ],
            ],
            'download' => [
                'description' => 'Temporary download URL for quotation',
                'fields' => [
                    'data.location' => 'Temporary URL where the file can be downloaded',
                    'data.expires' => 'Expiration time of the temporary download link',
                ],
            ],
            'send' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => [],
            ],
            'accept' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => [],
            ],
            'update' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => [],
            ],
            'delete' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => [],
            ],
        ];
    }
}
