<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use McoreServices\TeamleaderSDK\Resources\Resource;

class Orders extends Resource
{
    protected string $description = 'Retrieve and view orders in Teamleader Focus';

    // Resource capabilities - Orders are read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = true;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'custom_fields' => 'Include custom field values for the order',
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of order UUIDs to filter by',
    ];

    // Payment term types
    protected array $paymentTermTypes = [
        'cash',
        'end_of_month',
        'after_invoice_date',
    ];

    // Supplier types
    protected array $supplierTypes = [
        'company',
        'contact',
    ];

    // Usage examples specific to orders
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all orders',
            'code' => '$orders = $teamleader->orders()->list();',
        ],
        'list_specific' => [
            'description' => 'Get specific orders by ID',
            'code' => '$orders = $teamleader->orders()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'get_single' => [
            'description' => 'Get a single order with custom fields',
            'code' => '$order = $teamleader->orders()->include(\'custom_fields\')->info(\'order-uuid\');',
        ],
        'by_ids' => [
            'description' => 'Get orders by IDs using convenience method',
            'code' => '$orders = $teamleader->orders()->byIds([\'uuid1\', \'uuid2\']);',
        ],
    ];

    /**
     * Get the base path for the orders resource
     */
    protected function getBasePath(): string
    {
        return 'orders';
    }

    /**
     * List orders with optional filtering
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (includes)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply includes from options
        if (isset($options['includes'])) {
            $params['includes'] = is_array($options['includes'])
                ? implode(',', $options['includes'])
                : $options['includes'];
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get order information
     *
     * @param  string  $id  Order UUID
     * @param  mixed  $includes  Includes to load (e.g., 'custom_fields')
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        // Apply includes
        if (! empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Get orders by specific IDs
     *
     * @param  array  $ids  Array of order UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Build filters array for the API request
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
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'info' => [
                'description' => 'Complete order information',
                'fields' => [
                    'data.id' => 'Order UUID',
                    'data.name' => 'Order name',
                    'data.order_date' => 'Order date (YYYY-MM-DD) (nullable)',
                    'data.delivery_date' => 'Delivery date (YYYY-MM-DD) (nullable)',
                    'data.payment_term' => 'Payment term information (nullable)',
                    'data.payment_term.type' => 'Payment type (cash, end_of_month, after_invoice_date)',
                    'data.payment_term.days' => 'Days modifier (not required for cash)',
                    'data.grouped_lines' => 'Array of line item groups',
                    'data.grouped_lines[].section' => 'Section information',
                    'data.grouped_lines[].section.title' => 'Section title',
                    'data.grouped_lines[].line_items' => 'Array of line items in this section',
                    'data.grouped_lines[].line_items[].product' => 'Product reference (nullable)',
                    'data.grouped_lines[].line_items[].quantity' => 'Item quantity',
                    'data.grouped_lines[].line_items[].description' => 'Item description',
                    'data.grouped_lines[].line_items[].extended_description' => 'Extended description with Markdown (nullable)',
                    'data.grouped_lines[].line_items[].unit' => 'Unit of measure (nullable)',
                    'data.grouped_lines[].line_items[].unit_price' => 'Unit price information',
                    'data.grouped_lines[].line_items[].unit_price.amount' => 'Price amount',
                    'data.grouped_lines[].line_items[].unit_price.tax' => 'Tax type (excluding)',
                    'data.grouped_lines[].line_items[].tax' => 'Tax rate reference',
                    'data.grouped_lines[].line_items[].discount' => 'Discount information (nullable)',
                    'data.grouped_lines[].line_items[].total' => 'Line item totals',
                    'data.grouped_lines[].line_items[].product_category' => 'Product category reference (nullable)',
                    'data.total' => 'Order total amounts',
                    'data.total.tax_exclusive' => 'Total excluding tax',
                    'data.total.tax_inclusive' => 'Total including tax',
                    'data.total.purchase_price_tax_exclusive' => 'Purchase price excluding tax (nullable)',
                    'data.total.purchase_price_tax_inclusive' => 'Purchase price including tax (nullable)',
                    'data.total.taxes' => 'Tax breakdown array',
                    'data.total.taxes[].rate' => 'Tax rate (e.g., 0.21 for 21%)',
                    'data.total.taxes[].taxable' => 'Taxable amount',
                    'data.total.taxes[].tax' => 'Tax amount',
                    'data.web_url' => 'URL to view order in Teamleader Focus',
                    'data.supplier' => 'Supplier information (nullable)',
                    'data.supplier.type' => 'Supplier type (company, contact)',
                    'data.supplier.id' => 'Supplier UUID',
                    'data.department' => 'Department reference (nullable)',
                    'data.deal' => 'Deal reference (nullable)',
                    'data.project' => 'Project reference (nullable)',
                    'data.assignee' => 'Assignee reference (nullable)',
                    'data.custom_fields' => 'Custom fields (only with includes=custom_fields)',
                ],
            ],
            'list' => [
                'description' => 'Array of orders',
                'fields' => [
                    'data' => 'Array of order objects with simplified structure',
                    'data[].id' => 'Order UUID',
                    'data[].name' => 'Order name',
                    'data[].order_date' => 'Order date (nullable)',
                    'data[].delivery_date' => 'Delivery date (nullable)',
                    'data[].payment_term' => 'Payment term information (nullable)',
                    'data[].total' => 'Order total amounts',
                    'data[].web_url' => 'URL to view order in Teamleader Focus',
                    'data[].supplier' => 'Supplier information (nullable)',
                    'data[].department' => 'Department reference (nullable)',
                    'data[].deal' => 'Deal reference (nullable)',
                    'data[].project' => 'Project reference (nullable)',
                    'data[].assignee' => 'Assignee reference (nullable)',
                    'data[].custom_fields' => 'Custom fields (only with includes=custom_fields)',
                ],
            ],
        ];
    }

    /**
     * Get payment term types
     */
    public function getPaymentTermTypes(): array
    {
        return $this->paymentTermTypes;
    }

    /**
     * Get supplier types
     */
    public function getSupplierTypes(): array
    {
        return $this->supplierTypes;
    }
}
