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

    protected bool $supportsSideloading = true;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'expiry' => 'Include expiry information (only if user has access to quotation expiry)',
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of quotation UUIDs to filter by',
        'status' => 'Filter by status(es): open, accepted, expired, rejected, closed',
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
            'description' => 'Get a single quotation with expiry',
            'code' => '$quotation = $teamleader->quotations()->include(\'expiry\')->info(\'quotation-uuid\');',
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
     * @param string $id Quotation UUID
     * @param mixed $includes Includes to load (e.g., 'expiry')
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
     * Get the base path for the quotations resource
     */
    protected function getBasePath(): string
    {
        return 'quotations';
    }

    /**
     * Create a new quotation
     *
     * @param array $data Quotation data
     */
    public function create(array $data): array
    {
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
    }

    /**
     * Validate data for creating a quotation
     *
     * @throws InvalidArgumentException
     */
    private function validateCreateData(array $data): void
    {
        if (!isset($data['deal_id'])) {
            throw new InvalidArgumentException('deal_id is required to create a quotation');
        }

        if (!isset($data['grouped_lines']) && !isset($data['text'])) {
            throw new InvalidArgumentException('A quotation needs either grouped_lines or text to be valid');
        }
    }

    /**
     * Update a quotation
     *
     * @param string $id Quotation UUID
     * @param array $data Updated quotation data
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
     */
    public function delete(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Mark a quotation as accepted
     *
     * @param string $id Quotation UUID
     */
    public function accept(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.accept', ['id' => $id]);
    }

    /**
     * Send one or more quotations via email
     *
     * @param array $data Send parameters including quotations, sender, recipients, subject, content, and language
     */
    public function send(array $data): array
    {
        $this->validateSendData($data);

        return $this->api->request('POST', $this->getBasePath() . '.send', $data);
    }

    /**
     * Validate data for sending quotations
     *
     * @throws InvalidArgumentException
     */
    private function validateSendData(array $data): void
    {
        if (!isset($data['quotations']) || !is_array($data['quotations']) || empty($data['quotations'])) {
            throw new InvalidArgumentException('quotations array is required and must not be empty');
        }

        if (!isset($data['from']) || !isset($data['from']['sender'])) {
            throw new InvalidArgumentException('from.sender is required');
        }

        if (!isset($data['recipients']) || !isset($data['recipients']['to']) || empty($data['recipients']['to'])) {
            throw new InvalidArgumentException('recipients.to is required and must not be empty');
        }

        if (!isset($data['subject'])) {
            throw new InvalidArgumentException('subject is required');
        }

        if (!isset($data['content'])) {
            throw new InvalidArgumentException('content is required');
        }

        if (!isset($data['language'])) {
            throw new InvalidArgumentException('language is required');
        }
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
            throw new InvalidArgumentException(
                "Invalid format '{$format}'. Supported formats: " . implode(', ', $this->supportedFormats)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.download', [
            'id' => $id,
            'format' => $format,
        ]);
    }

    /**
     * Get quotations by specific IDs
     *
     * @param array $ids Array of quotation UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * List quotations with enhanced filtering and pagination
     *
     * @param array $filters Filters to apply
     * @param array $options Additional options (pagination via page.size / page.number)
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
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle IDs filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        // Handle status filter
        if (isset($filters['status'])) {
            $apiFilters['status'] = is_string($filters['status'])
                ? [$filters['status']]
                : $filters['status'];
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
            $page['size'] = (int)$pagination['size'];
        }

        if (isset($pagination['number'])) {
            $page['number'] = (int)$pagination['number'];
        }

        return $page;
    }

    /**
     * Get quotations by status
     *
     * @param string|array $status Single status or array of statuses
     */
    public function byStatus($status): array
    {
        if (is_string($status)) {
            $status = [$status];
        }

        foreach ($status as $s) {
            if (!in_array($s, $this->validStatuses)) {
                throw new InvalidArgumentException(
                    "Invalid status '{$s}'. Must be one of: " . implode(', ', $this->validStatuses)
                );
            }
        }

        return $this->list(['status' => $status]);
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
                    'data.grouped_lines[].section' => 'Section object',
                    'data.grouped_lines[].section.title' => 'Section title',
                    'data.grouped_lines[].line_items' => 'Array of line items',
                    'data.grouped_lines[].line_items[].product' => 'Product reference (nullable)',
                    'data.grouped_lines[].line_items[].product.id' => 'Product UUID',
                    'data.grouped_lines[].line_items[].product.type' => 'Product type string',
                    'data.grouped_lines[].line_items[].quantity' => 'Item quantity',
                    'data.grouped_lines[].line_items[].description' => 'Item description',
                    'data.grouped_lines[].line_items[].extended_description' => 'Extended description with Markdown (nullable)',
                    'data.grouped_lines[].line_items[].unit' => 'Unit of measure (nullable)',
                    'data.grouped_lines[].line_items[].unit.id' => 'Unit UUID',
                    'data.grouped_lines[].line_items[].unit.type' => 'Unit type string',
                    'data.grouped_lines[].line_items[].unit_price' => 'Unit price object',
                    'data.grouped_lines[].line_items[].unit_price.amount' => 'Price amount',
                    'data.grouped_lines[].line_items[].unit_price.currency' => 'Currency code',
                    'data.grouped_lines[].line_items[].unit_price.tax' => 'Tax inclusion (excluding)',
                    'data.grouped_lines[].line_items[].tax' => 'Tax rate reference',
                    'data.grouped_lines[].line_items[].tax.id' => 'Tax rate UUID',
                    'data.grouped_lines[].line_items[].tax.type' => 'Tax type string',
                    'data.grouped_lines[].line_items[].discount' => 'Discount (nullable)',
                    'data.grouped_lines[].line_items[].discount.value' => 'Discount value (0–100)',
                    'data.grouped_lines[].line_items[].discount.type' => 'Discount type (percentage)',
                    'data.grouped_lines[].line_items[].total' => 'Line item totals',
                    'data.grouped_lines[].line_items[].total.tax_exclusive' => 'Total excluding tax',
                    'data.grouped_lines[].line_items[].total.tax_exclusive.amount' => 'Amount',
                    'data.grouped_lines[].line_items[].total.tax_exclusive.currency' => 'Currency code',
                    'data.grouped_lines[].line_items[].total.tax_exclusive_before_discount' => 'Total excluding tax before discount',
                    'data.grouped_lines[].line_items[].total.tax_exclusive_before_discount.amount' => 'Amount',
                    'data.grouped_lines[].line_items[].total.tax_exclusive_before_discount.currency' => 'Currency code',
                    'data.grouped_lines[].line_items[].total.tax_inclusive' => 'Total including tax',
                    'data.grouped_lines[].line_items[].total.tax_inclusive.amount' => 'Amount',
                    'data.grouped_lines[].line_items[].total.tax_inclusive.currency' => 'Currency code',
                    'data.grouped_lines[].line_items[].total.tax_inclusive_before_discount' => 'Total including tax before discount',
                    'data.grouped_lines[].line_items[].total.tax_inclusive_before_discount.amount' => 'Amount',
                    'data.grouped_lines[].line_items[].total.tax_inclusive_before_discount.currency' => 'Currency code',
                    'data.grouped_lines[].line_items[].purchase_price' => 'Purchase price for this line item (nullable)',
                    'data.grouped_lines[].line_items[].purchase_price.amount' => 'Purchase price amount',
                    'data.grouped_lines[].line_items[].purchase_price.currency' => 'Currency code',
                    'data.grouped_lines[].line_items[].periodicity' => 'Recurring period for this line item (nullable)',
                    'data.grouped_lines[].line_items[].periodicity.unit' => 'Period unit (week, month, year)',
                    'data.grouped_lines[].line_items[].periodicity.period' => 'Period multiplier',
                    'data.currency' => 'Quotation currency code (e.g. EUR)',
                    'data.currency_exchange_rate' => 'Exchange rate object (when currency differs from account currency)',
                    'data.currency_exchange_rate.from' => 'Source currency code',
                    'data.currency_exchange_rate.to' => 'Target currency code',
                    'data.currency_exchange_rate.rate' => 'Exchange rate (e.g. 1.1234)',
                    'data.text' => 'Rich text content of the quotation in Markdown',
                    'data.total' => 'Quotation totals',
                    'data.total.tax_exclusive' => 'Total excluding tax',
                    'data.total.tax_exclusive.amount' => 'Amount',
                    'data.total.tax_exclusive.currency' => 'Currency code',
                    'data.total.tax_inclusive' => 'Total including tax',
                    'data.total.tax_inclusive.amount' => 'Amount',
                    'data.total.tax_inclusive.currency' => 'Currency code',
                    'data.total.taxes' => 'Tax breakdown array',
                    'data.total.taxes[].rate' => 'Tax rate (e.g. 0.21 for 21%)',
                    'data.total.taxes[].taxable' => 'Taxable amount object',
                    'data.total.taxes[].tax' => 'Tax amount object',
                    'data.total.purchase_price' => 'Total purchase price (nullable)',
                    'data.total.purchase_price.amount' => 'Amount',
                    'data.total.purchase_price.currency' => 'Currency code',
                    'data.discounts' => 'Document-level discounts array',
                    'data.discounts[].type' => 'Discount type (percentage)',
                    'data.discounts[].value' => 'Discount value (0–100)',
                    'data.discounts[].description' => 'Discount description (e.g. winter promotion)',
                    'data.created_at' => 'Creation timestamp (nullable)',
                    'data.updated_at' => 'Last update timestamp (nullable)',
                    'data.status' => 'Quotation status (open, accepted, expired, rejected, closed)',
                    'data.name' => 'Quotation name',
                    'data.document_template' => 'Document template reference',
                    'data.document_template.id' => 'Template UUID',
                    'data.document_template.type' => 'Template type string',
                    'data.expiry' => 'Expiry info — only with includes=expiry and if user has access',
                    'data.expiry.expires_after' => 'Expiry date (YYYY-MM-DD)',
                    'data.expiry.action_after_expiry' => 'Action on expiry (lock, none)',
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
