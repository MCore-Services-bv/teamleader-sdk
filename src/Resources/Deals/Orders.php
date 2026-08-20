<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Orders extends Resource
{
    protected string $description = 'Retrieve and view orders in Teamleader Focus';

    // Resource capabilities - Orders are read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    // Pagination is not declared for orders.list in
    // @teamleader/focus-api-specification — v1.198.0 declares `filter` and
    // `includes` only, where 40 of the 58 `.list` endpoints declare `page`.
    // The endpoint honours it regardless. Verified live on 2026-08-20 against
    // an account holding 30 orders: no page parameter returned 20, size 100
    // returned all 30, size 100 number 2 returned 0, and size 5 returned five
    // records on page 1 and five different records on page 2. An ignored
    // parameter cannot produce that — the API's usual response to a key it does
    // not recognise is to answer 200 and carry on as though it were absent — so
    // this is an undocumented capability rather than an absent one.
    protected bool $supportsPagination = true;

    // orders.list declares no `meta` in its response and takes no `pagination`
    // include, so there is no total count and no page count. The only
    // end-of-list signal is a page shorter than the one requested, which is
    // what all() relies on.
    protected bool $requestsPaginationMeta = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = true;

    // Available includes for sideloading.
    //
    // A flat list, matching every other resource. Until v2.2.2 this was a keyed
    // map, so getCapabilities()['available_includes'] returned a different shape
    // here than anywhere else and generic iteration over capabilities broke.
    protected array $availableIncludes = [
        'custom_fields',
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
            'description' => 'Get the first page of orders (20 records — the API default)',
            'code' => '$orders = $teamleader->orders()->list();',
        ],
        'paginated_list' => [
            'description' => 'Get orders with an explicit page size and number',
            'code' => '$orders = $teamleader->orders()->list([], [\'page_size\' => 100, \'page_number\' => 1]);',
        ],
        'every_order' => [
            'description' => 'Get every order in the account, walking all pages',
            'code' => '$orders = $teamleader->orders()->all();',
        ],
        'list_specific' => [
            'description' => 'Get specific orders by ID',
            'code' => '$orders = $teamleader->orders()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'get_single' => [
            'description' => 'Get a single order with custom fields',
            'code' => '$order = $teamleader->orders()->with(\'custom_fields\')->info(\'order-uuid\');',
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
     * List orders with optional filtering and pagination
     *
     * Pagination is undocumented on this endpoint but functional — see the note
     * on $supportsPagination. Passing neither page option sends no `page` key
     * and returns the API default of 20 records, which is the pre-v2.2.3
     * behaviour unchanged.
     *
     * Filtering is `ids` only. The API accepts any other filter key, ignores it,
     * and answers 200 with the full unfiltered first page — `department_id`,
     * `updated_since`, `order_date_after`, `term` and `status` were each
     * confirmed to have no effect. Unknown keys therefore throw rather than
     * being sent; see buildFilters().
     *
     * Sorting is not supported. A sort passed here is rejected, because the API
     * accepts one and discards it — sorting by `order_date` returns the same
     * first record as no sort at all.
     *
     * @param  array  $filters  Filters to apply — `ids` only
     * @param  array  $options  page_size, page_number, include
     *
     * @throws InvalidArgumentException When a sort or an unknown filter key is passed
     */
    public function list(array $filters = [], array $options = []): array
    {
        $this->rejectUnsupportedListArguments($filters, $options);

        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        // Apply includes — accepts both the `include` and `includes` option keys
        $params = $this->applyIncludes($params, $this->resolveIncludesOption($options));

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
     * Retrieve every order, walking the endpoint's pages
     *
     * orders.list returns no total count, so the end of the list is inferred
     * from a page shorter than the one requested — which means a complete final
     * page costs one extra empty request, and the number of requests a full
     * pass needs cannot be known in advance.
     *
     * $maxPages is a runaway guard, not a limit: reaching it with a full page
     * still coming throws, rather than returning a partial set that looks
     * complete. Silently returning 10,000 of 11,000 orders is the failure this
     * method exists to prevent, so it is not repeated here.
     *
     * The signature differs from PaymentMethods::all() and TaxRates::all(),
     * which take (array $filters, int $maxPages). $options sits in the middle
     * because custom_fields is worth sideloading during a full pass, and
     * because applyPendingIncludes() consumes the fluent state after the first
     * request — so with('custom_fields')->all() would otherwise sideload page 1
     * and nothing after it. The sideload is resolved once here and replayed on
     * every page.
     *
     * @param  array  $filters  Filters to apply — `ids` only
     * @param  array  $options  include (applied to every page)
     * @param  int  $maxPages  Safety cap — 100 pages of 100 is 10,000 orders
     * @return array{data: array} Every matching order in one data array
     *
     * @throws TeamleaderException When $maxPages is reached with records still pending
     */
    public function all(array $filters = [], array $options = [], int $maxPages = 100): array
    {
        $pageSize = 100;

        $includes = $this->resolveIncludesOption($options) ?: $this->getPendingIncludes();
        $this->pendingIncludes = [];

        $orders = [];
        $page = 1;
        $hasMore = false;

        do {
            $pageOptions = [
                'page_size' => $pageSize,
                'page_number' => $page,
            ];

            if (! empty($includes)) {
                $pageOptions['include'] = $includes;
            }

            $result = $this->list($filters, $pageOptions);

            $batch = $result['data'] ?? [];
            $orders = array_merge($orders, $batch);

            $hasMore = count($batch) === $pageSize;
            $page++;
        } while ($hasMore && $page <= $maxPages);

        if ($hasMore) {
            throw new TeamleaderException(
                'orders.list still had records after '.$maxPages.' pages of '.$pageSize
                .' ('.count($orders).' retrieved). Raise $maxPages if the account is '
                .'genuinely this large — returning a partial set here would look '
                .'complete to the caller.'
            );
        }

        return ['data' => $orders];
    }

    /**
     * Build filters array for the API request
     *
     * orders.list accepts `ids` and nothing else. Everything else is whitelisted
     * out here rather than sent, because the API answers 200 to a filter key it
     * does not recognise and returns the full unfiltered set — so a typo, or a
     * filter borrowed from another resource, would look like it worked.
     *
     * @throws InvalidArgumentException When an unsupported filter key is passed
     */
    protected function buildFilters(array $filters): array
    {
        $supported = array_keys($this->commonFilters);
        $unknown = array_diff(array_keys($filters), $supported);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key')
                .' for orders.list: '.implode(', ', $unknown)
                .'. Supported: '.implode(', ', $supported)
                .'. The API ignores every other filter key and returns the full set.'
            );
        }

        $apiFilters = [];

        // Handle IDs filter — a lone string is wrapped
        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids'])
                ? array_values($filters['ids'])
                : [$filters['ids']];
        }

        return $apiFilters;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of orders with summary information',
                'fields' => [
                    'data' => 'Array of order objects',
                    'data[].id' => 'Order UUID',
                    'data[].name' => 'Order name',
                    'data[].order_date' => 'Order date (YYYY-MM-DD) (nullable)',
                    'data[].order_number' => 'Sequential order number, integer (nullable)',
                    'data[].status' => 'Order status, e.g. delivered. Present on live records '
                        .'but not declared in the API specification, so the value set is '
                        .'unconfirmed',
                    'data[].delivery_date' => 'Delivery date (YYYY-MM-DD) (nullable)',
                    'data[].payment_term' => 'Payment term information (nullable)',
                    'data[].payment_term.type' => 'Payment type (cash, end_of_month, after_invoice_date)',
                    'data[].payment_term.days' => 'Days modifier. Not required when type is cash',
                    'data[].total' => 'Order total amounts',
                    'data[].total.tax_exclusive' => 'Total excluding tax',
                    'data[].total.tax_exclusive.amount' => 'Amount excluding tax',
                    'data[].total.tax_exclusive.currency' => 'Currency code',
                    'data[].total.tax_inclusive' => 'Total including tax',
                    'data[].total.tax_inclusive.amount' => 'Amount including tax',
                    'data[].total.tax_inclusive.currency' => 'Currency code',
                    'data[].total.purchase_price_tax_exclusive' => 'Purchase price excluding tax (nullable)',
                    'data[].total.purchase_price_tax_exclusive.amount' => 'Amount',
                    'data[].total.purchase_price_tax_exclusive.currency' => 'Currency code',
                    'data[].total.purchase_price_tax_inclusive' => 'Purchase price including tax (nullable)',
                    'data[].total.purchase_price_tax_inclusive.amount' => 'Amount',
                    'data[].total.purchase_price_tax_inclusive.currency' => 'Currency code',
                    'data[].total.taxes' => 'Tax breakdown array',
                    'data[].total.taxes[].rate' => 'Tax rate (e.g. 0.21 for 21%)',
                    'data[].total.taxes[].taxable' => 'Taxable amount object',
                    'data[].total.taxes[].taxable.amount' => 'Taxable amount',
                    'data[].total.taxes[].taxable.currency' => 'Currency code',
                    'data[].total.taxes[].tax' => 'Tax amount object',
                    'data[].total.taxes[].tax.amount' => 'Tax amount',
                    'data[].total.taxes[].tax.currency' => 'Currency code',
                    'data[].web_url' => 'URL to view order in Teamleader Focus',
                    'data[].supplier' => 'Supplier information (nullable)',
                    'data[].supplier.type' => 'Supplier type (company, contact)',
                    'data[].supplier.id' => 'Supplier UUID',
                    'data[].department' => 'Department reference (nullable)',
                    'data[].department.id' => 'Department UUID',
                    'data[].department.type' => 'Reference type (department)',
                    'data[].deal' => 'Deal reference (nullable)',
                    'data[].deal.id' => 'Deal UUID',
                    'data[].deal.type' => 'Reference type (deal)',
                    'data[].project' => 'Project reference — old projects module only (nullable)',
                    'data[].project.id' => 'Project UUID',
                    'data[].project.type' => 'Reference type (project)',
                    'data[].assignee' => 'Assignee reference (nullable)',
                    'data[].assignee.id' => 'User UUID',
                    'data[].assignee.type' => 'Reference type (user)',
                    'data[].custom_fields' => 'Custom fields (only with includes=custom_fields)',
                    'data[].custom_fields[].definition' => 'Custom field definition reference',
                    'data[].custom_fields[].definition.id' => 'Definition UUID',
                    'data[].custom_fields[].definition.type' => 'Reference type (customFieldDefinition)',
                    'data[].custom_fields[].value' => 'Field value — type depends on the definition',
                ],
            ],
            'info' => [
                'description' => 'Complete order information including grouped line items',
                'fields' => [
                    'data.id' => 'Order UUID',
                    'data.name' => 'Order name',
                    'data.order_date' => 'Order date (YYYY-MM-DD) (nullable)',
                    'data.order_number' => 'Sequential order number, integer (nullable)',
                    'data.status' => 'Order status, e.g. delivered. Present on live records '
                        .'but not declared in the API specification, so the value set is '
                        .'unconfirmed',
                    'data.delivery_date' => 'Delivery date (YYYY-MM-DD) (nullable)',
                    'data.payment_term' => 'Payment term information (nullable)',
                    'data.payment_term.type' => 'Payment type (cash, end_of_month, after_invoice_date)',
                    'data.payment_term.days' => 'Days modifier. Not required when type is cash',
                    'data.grouped_lines' => 'Array of line item groups',
                    'data.grouped_lines[].section' => 'Section information',
                    'data.grouped_lines[].section.title' => 'Section title',
                    'data.grouped_lines[].line_items' => 'Array of line items in this section',
                    'data.grouped_lines[].line_items[].product' => 'Product reference (nullable)',
                    'data.grouped_lines[].line_items[].product.id' => 'Product UUID',
                    'data.grouped_lines[].line_items[].product.type' => 'Product type string',
                    'data.grouped_lines[].line_items[].quantity' => 'Item quantity',
                    'data.grouped_lines[].line_items[].description' => 'Item description',
                    'data.grouped_lines[].line_items[].extended_description' => 'Extended description with Markdown (nullable)',
                    'data.grouped_lines[].line_items[].unit' => 'Unit of measure (nullable)',
                    'data.grouped_lines[].line_items[].unit.id' => 'Unit UUID',
                    'data.grouped_lines[].line_items[].unit.type' => 'Unit type string',
                    'data.grouped_lines[].line_items[].unit_price' => 'Unit price information',
                    'data.grouped_lines[].line_items[].unit_price.amount' => 'Price amount',
                    'data.grouped_lines[].line_items[].unit_price.tax' => 'Tax type (excluding)',
                    'data.grouped_lines[].line_items[].tax' => 'Tax rate reference',
                    'data.grouped_lines[].line_items[].tax.id' => 'Tax UUID',
                    'data.grouped_lines[].line_items[].tax.type' => 'Tax type string',
                    'data.grouped_lines[].line_items[].discount' => 'Discount information (nullable)',
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
                    'data.grouped_lines[].line_items[].product_category' => 'Product category reference (nullable)',
                    'data.grouped_lines[].line_items[].product_category.id' => 'Product category UUID',
                    'data.grouped_lines[].line_items[].product_category.type' => 'Reference type (productCategory)',
                    'data.grouped_lines[].line_items[].project' => 'Project reference for this line item (nullable)',
                    'data.grouped_lines[].line_items[].project.id' => 'Project UUID',
                    'data.grouped_lines[].line_items[].project.type' => 'Project type (e.g. nextgenProject)',
                    'data.grouped_lines[].line_items[].group' => 'Project group reference for this line item (nullable)',
                    'data.grouped_lines[].line_items[].group.id' => 'Group UUID',
                    'data.grouped_lines[].line_items[].group.type' => 'Group type (e.g. nextgenProjectGroup)',
                    'data.grouped_lines[].line_items[].purchase_price' => 'Purchase price for this line item (nullable)',
                    'data.grouped_lines[].line_items[].purchase_price.amount' => 'Purchase price amount',
                    'data.grouped_lines[].line_items[].purchase_price.currency' => 'Currency code',
                    'data.total' => 'Order total amounts',
                    'data.total.tax_exclusive' => 'Total excluding tax',
                    'data.total.tax_exclusive.amount' => 'Amount excluding tax',
                    'data.total.tax_exclusive.currency' => 'Currency code',
                    'data.total.tax_inclusive' => 'Total including tax',
                    'data.total.tax_inclusive.amount' => 'Amount including tax',
                    'data.total.tax_inclusive.currency' => 'Currency code',
                    'data.total.purchase_price_tax_exclusive' => 'Total purchase price excluding tax (nullable)',
                    'data.total.purchase_price_tax_exclusive.amount' => 'Amount',
                    'data.total.purchase_price_tax_exclusive.currency' => 'Currency code',
                    'data.total.purchase_price_tax_inclusive' => 'Total purchase price including tax (nullable)',
                    'data.total.purchase_price_tax_inclusive.amount' => 'Amount',
                    'data.total.purchase_price_tax_inclusive.currency' => 'Currency code',
                    'data.total.taxes' => 'Tax breakdown array',
                    'data.total.taxes[].rate' => 'Tax rate (e.g. 0.21 for 21%)',
                    'data.total.taxes[].taxable' => 'Taxable amount object',
                    'data.total.taxes[].taxable.amount' => 'Taxable amount',
                    'data.total.taxes[].taxable.currency' => 'Currency code',
                    'data.total.taxes[].tax' => 'Tax amount object',
                    'data.total.taxes[].tax.amount' => 'Tax amount',
                    'data.total.taxes[].tax.currency' => 'Currency code',
                    'data.web_url' => 'URL to view order in Teamleader Focus',
                    'data.supplier' => 'Supplier information (nullable)',
                    'data.supplier.type' => 'Supplier type (company, contact)',
                    'data.supplier.id' => 'Supplier UUID',
                    'data.department' => 'Department reference (nullable)',
                    'data.department.id' => 'Department UUID',
                    'data.department.type' => 'Reference type (department)',
                    'data.deal' => 'Deal reference (nullable)',
                    'data.deal.id' => 'Deal UUID',
                    'data.deal.type' => 'Reference type (deal)',
                    'data.project' => 'Project reference — old projects module only (nullable)',
                    'data.project.id' => 'Project UUID',
                    'data.project.type' => 'Reference type (project)',
                    'data.assignee' => 'Assignee reference (nullable)',
                    'data.assignee.id' => 'User UUID',
                    'data.assignee.type' => 'Reference type (user)',
                    'data.custom_fields' => 'Custom fields (only with includes=custom_fields)',
                    'data.custom_fields[].definition' => 'Custom field definition reference',
                    'data.custom_fields[].definition.id' => 'Definition UUID',
                    'data.custom_fields[].definition.type' => 'Reference type (customFieldDefinition)',
                    'data.custom_fields[].value' => 'Field value — type depends on the definition',
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
