<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

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
    protected bool $supportsSideloading = true;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'expiry' => 'Include expiry information (only if user has access to quotation expiry)',
    ];

    // Common filters based on API documentation
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
            'code' => '$quotations = $teamleader->quotations()->list();'
        ],
        'list_specific' => [
            'description' => 'Get specific quotations by ID',
            'code' => '$quotations = $teamleader->quotations()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);'
        ],
        'get_single' => [
            'description' => 'Get a single quotation with expiry',
            'code' => '$quotation = $teamleader->quotations()->include(\'expiry\')->info(\'quotation-uuid\');'
        ],
        'create' => [
            'description' => 'Create a new quotation',
            'code' => '$quotation = $teamleader->quotations()->create([\'deal_id\' => \'deal-uuid\', \'currency\' => [\'code\' => \'EUR\'], \'grouped_lines\' => [...]]);'
        ],
        'update' => [
            'description' => 'Update a quotation',
            'code' => '$teamleader->quotations()->update(\'quotation-uuid\', [\'grouped_lines\' => [...]]);'
        ],
        'accept' => [
            'description' => 'Accept a quotation',
            'code' => '$teamleader->quotations()->accept(\'quotation-uuid\');'
        ],
        'send' => [
            'description' => 'Send a quotation via email',
            'code' => '$teamleader->quotations()->send([\'quotations\' => [\'uuid1\'], \'from\' => [...], \'recipients\' => [...], \'subject\' => \'...\', \'content\' => \'...\', \'language\' => \'en\']);'
        ],
        'download' => [
            'description' => 'Download a quotation as PDF',
            'code' => '$download = $teamleader->quotations()->download(\'quotation-uuid\', \'pdf\');'
        ],
    ];

    /**
     * Get the base path for the quotations resource
     */
    protected function getBasePath(): string
    {
        return 'quotations';
    }

    /**
     * List quotations with enhanced filtering and pagination
     *
     * @param array $filters Filters to apply
     * @param array $options Additional options (pagination)
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page'])) {
            $params['page'] = $this->buildPagination($options['page']);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get quotation information
     *
     * @param string $id Quotation UUID
     * @param mixed $includes Includes to load (e.g., 'expiry')
     * @return array
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        // Apply includes
        if (!empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.info', $params);
    }

    /**
     * Create a new quotation
     *
     * @param array $data Quotation data
     * @return array
     */
    public function create(array $data): array
    {
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
    }

    /**
     * Update a quotation
     *
     * @param string $id Quotation UUID
     * @param array $data Updated quotation data
     * @return array
     */
    public function update(string $id, array $data): array
    {
        $data['id'] = $id;

        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Delete a quotation
     *
     * @param string $id Quotation UUID
     * @return array
     */
    public function delete(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Mark a quotation as accepted
     *
     * @param string $id Quotation UUID
     * @return array
     */
    public function accept(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.accept', ['id' => $id]);
    }

    /**
     * Send one or more quotations via email
     *
     * @param array $data Send parameters including quotations, sender, recipients, subject, content, and language
     * @return array
     */
    public function send(array $data): array
    {
        $this->validateSendData($data);

        return $this->api->request('POST', $this->getBasePath() . '.send', $data);
    }

    /**
     * Download a quotation in a specific format
     *
     * @param string $id Quotation UUID
     * @param string $format Download format (default: 'pdf')
     * @return array Returns location URL and expiration time
     */
    public function download(string $id, string $format = 'pdf'): array
    {
        if (!in_array($format, $this->supportedFormats)) {
            throw new \InvalidArgumentException(
                "Invalid format '{$format}'. Supported formats: " . implode(', ', $this->supportedFormats)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.download', [
            'id' => $id,
            'format' => $format
        ]);
    }

    /**
     * Get quotations by specific IDs
     *
     * @param array $ids Array of quotation UUIDs
     * @return array
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get quotations by status
     *
     * @param string|array $status Single status or array of statuses
     * @return array
     */
    public function byStatus($status): array
    {
        if (is_string($status)) {
            $status = [$status];
        }

        foreach ($status as $s) {
            if (!in_array($s, $this->validStatuses)) {
                throw new \InvalidArgumentException(
                    "Invalid status '{$s}'. Must be one of: " . implode(', ', $this->validStatuses)
                );
            }
        }

        return $this->list(['status' => $status]);
    }

    /**
     * Build filters array for the API request
     *
     * @param array $filters
     * @return array
     */
    private function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle IDs filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        return $apiFilters;
    }

    /**
     * Build pagination parameters
     *
     * @param array $pagination
     * @return array
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
     * Validate data for creating a quotation
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    private function validateCreateData(array $data): void
    {
        if (!isset($data['deal_id'])) {
            throw new \InvalidArgumentException('deal_id is required to create a quotation');
        }

        if (!isset($data['grouped_lines']) && !isset($data['text'])) {
            throw new \InvalidArgumentException('A quotation needs either grouped_lines or text to be valid');
        }
    }

    /**
     * Validate data for sending quotations
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    private function validateSendData(array $data): void
    {
        if (!isset($data['quotations']) || !is_array($data['quotations']) || empty($data['quotations'])) {
            throw new \InvalidArgumentException('quotations array is required and must not be empty');
        }

        if (!isset($data['from']) || !isset($data['from']['sender'])) {
            throw new \InvalidArgumentException('from.sender is required');
        }

        if (!isset($data['recipients']) || !isset($data['recipients']['to']) || empty($data['recipients']['to'])) {
            throw new \InvalidArgumentException('recipients.to is required and must not be empty');
        }

        if (!isset($data['subject'])) {
            throw new \InvalidArgumentException('subject is required');
        }

        if (!isset($data['content'])) {
            throw new \InvalidArgumentException('content is required');
        }

        if (!isset($data['language'])) {
            throw new \InvalidArgumentException('language is required');
        }
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
                    'data.type' => 'Resource type (always "quotation")'
                ]
            ],
            'info' => [
                'description' => 'Complete quotation information',
                'fields' => [
                    'data.id' => 'Quotation UUID',
                    'data.deal' => 'Deal reference object',
                    'data.grouped_lines' => 'Array of line item groups',
                    'data.currency' => 'Currency code (e.g., "EUR")',
                    'data.currency_exchange_rate' => 'Exchange rate information',
                    'data.total' => 'Total amounts (tax_exclusive, tax_inclusive, taxes)',
                    'data.purchase_price' => 'Purchase price (nullable)',
                    'data.created_at' => 'Creation timestamp',
                    'data.updated_at' => 'Last update timestamp',
                    'data.status' => 'Quotation status (open, accepted, expired, rejected, closed)',
                    'data.name' => 'Quotation name',
                    'data.document_template' => 'Document template reference (nullable)',
                    'data.expiry' => 'Expiry information (if includes=expiry is requested)',
                ]
            ],
            'list' => [
                'description' => 'Array of quotations',
                'fields' => [
                    'data' => 'Array of quotation objects with structure similar to info endpoint'
                ]
            ],
            'download' => [
                'description' => 'Temporary download URL for quotation',
                'fields' => [
                    'data.location' => 'Temporary URL where the file can be downloaded',
                    'data.expires' => 'Expiration time of the temporary download link',
                ]
            ],
            'send' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => []
            ],
            'accept' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => []
            ],
            'update' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => []
            ],
            'delete' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => []
            ]
        ];
    }
}
