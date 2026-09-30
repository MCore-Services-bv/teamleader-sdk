<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Subscriptions extends Resource
{
    use ValidatesWritePayload;

    /** Body fields subscriptions.update accepts, besides `id`; subscriptions.create accepts the same set */
    public const WRITE_FIELDS = [
        'invoicee', 'department_id', 'deal_id', 'project_id', 'purchase_order_number', 'title', 'note',
        'starts_on', 'ends_on', 'billing_cycle', 'payment_term', 'grouped_lines', 'invoice_generation',
        'custom_fields', 'document_template_id', 'invoice_content', 'delivery_information',
    ];

    /** Fields subscriptions.create requires */
    public const REQUIRED_ON_CREATE = [
        'invoicee', 'department_id', 'starts_on', 'billing_cycle', 'title', 'grouped_lines',
        'payment_term', 'invoice_generation',
    ];

    /**
     * `billing_cycle.periodicity.period` per unit. The specification declares
     * one alternative per unit, each with its own allowed periods.
     */
    public const PERIODS = [
        'week' => [1, 2],
        'month' => [1, 2, 3, 4, 6],
        'year' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    ];

    /** `billing_cycle.days_in_advance` */
    public const DAYS_IN_ADVANCE = [0, 7, 14, 21, 28];

    /** `invoice_content` — required for French departments sending via Peppol from France */
    public const INVOICE_CONTENT = ['goods', 'services', 'goods_and_services'];

    /** `invoice_generation.payment_method` */
    public const INVOICE_PAYMENT_METHODS = ['direct_debit'];

    /** `delivery_information.type` */
    public const DELIVERY_TYPES = ['set_days_after_invoice_date'];

    protected string $description = 'Manage subscriptions in Teamleader Focus';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = false;  // Uses deactivate instead

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of subscription UUIDs',
        'invoice_id' => 'Find subscriptions that generated the given invoice',
        'deal_id' => 'Filter on subscriptions created from a deal',
        'department_id' => 'Filter on subscriptions of a specific department',
        'customer' => 'Customer object: ["type" => "contact"|"company", "id" => "..."]',
        'status' => 'Array of statuses (active, deactivated)',
    ];

    // Sort fields accepted by subscriptions.list — a keyed map, so
    // normaliseSort() validates against it
    protected array $availableSortFields = [
        'title' => 'Subscription title',
        'created_at' => 'Creation date',
        'status' => 'Status',
    ];

    // Valid billing cycle units
    protected array $billingCycleUnits = [
        'week',
        'month',
        'year',
    ];

    // Valid customer types
    protected array $customerTypes = [
        'contact',
        'company',
    ];

    // Valid status values
    protected array $statusValues = [
        'active',
        'deactivated',
    ];

    // Valid payment term types
    protected array $paymentTermTypes = [
        'cash',
        'end_of_month',
        'after_invoice_date',
    ];

    // Valid invoice generation actions
    protected array $invoiceGenerationActions = [
        'draft',
        'book',
        'book_and_send',
    ];

    // Valid sending methods for invoice_generation when action is 'book_and_send'
    protected array $validSendingMethods = [
        'email',
        'peppol',
        'postal_service',
    ];

    // Usage examples specific to subscriptions
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all subscriptions',
            'code' => '$subscriptions = $teamleader->subscriptions()->list();',
        ],
        'filter_by_status' => [
            'description' => 'Get active subscriptions',
            'code' => '$subscriptions = $teamleader->subscriptions()->active();',
        ],
        'filter_by_customer' => [
            'description' => 'Get subscriptions for a specific customer',
            'code' => '$subscriptions = $teamleader->subscriptions()->forCustomer(\'company\', \'company-uuid\');',
        ],
        'filter_by_department' => [
            'description' => 'Get subscriptions for a specific department',
            'code' => '$subscriptions = $teamleader->subscriptions()->forDepartment(\'department-uuid\');',
        ],
        'create_subscription' => [
            'description' => 'Create a new subscription',
            'code' => '$subscription = $teamleader->subscriptions()->create([...]);',
        ],
        'create_with_peppol' => [
            'description' => 'Create a subscription that sends invoices via Peppol',
            'code' => <<<'PHP'
$subscription = $teamleader->subscriptions()->create([
    'invoicee' => [
        'customer' => ['type' => 'company', 'id' => 'company-uuid'],
    ],
    'department_id' => 'dept-uuid',
    'starts_on' => '2024-01-01',
    'billing_cycle' => [
        'periodicity' => ['unit' => 'month', 'period' => 1],
        'days_in_advance' => 7,
    ],
    'title' => 'Monthly support',
    'grouped_lines' => [[
        'section' => ['title' => 'Support'],
        'line_items' => [[
            'quantity' => 1,
            'description' => 'Monthly support fee',
            'unit_price' => ['amount' => 500.00, 'tax' => 'excluding'],
            'tax_rate_id' => 'tax-rate-uuid',
        ]],
    ]],
    'payment_term' => ['type' => 'cash'],
    'invoice_generation' => [
        'action' => 'book_and_send',
        'sending_methods' => [
            ['method' => 'peppol'],
        ],
    ],
]);
PHP,
        ],
        'update_subscription' => [
            'description' => 'Update an existing subscription',
            'code' => '$subscription = $teamleader->subscriptions()->update(\'subscription-uuid\', [...]);',
        ],
        'deactivate_subscription' => [
            'description' => 'Deactivate a subscription',
            'code' => '$result = $teamleader->subscriptions()->deactivate(\'subscription-uuid\');',
        ],
        'get_info' => [
            'description' => 'Get detailed information about a subscription',
            'code' => '$subscription = $teamleader->subscriptions()->info(\'subscription-uuid\');',
        ],
    ];

    /**
     * Get detailed information about a subscription
     *
     * Response includes:
     * - id, title, note (nullable), status, department, invoicee, project (nullable)
     * - starts_on, ends_on (nullable), next_renewal_date (nullable)
     * - billing_cycle (periodicity, days_in_advance, payment_term)
     * - total (tax_exclusive, tax_inclusive, taxes)
     * - grouped_lines, invoice_generation (action, sending_methods, payment_method)
     * - custom_fields, document_template, currency
     * - web_url
     * - created_at (string|null): ISO 8601 creation timestamp
     * - purchase_order_number (string|null): PO number on the subscription
     * - delivery_information (object|null): Delivery details (name, address, etc.)
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Get the base path for the subscriptions resource
     */
    protected function getBasePath(): string
    {
        return 'subscriptions';
    }

    /**
     * Create a new subscription
     *
     * Required fields:
     * - invoicee (object): customer {type, id}, optional for_attention_of
     * - department_id (string): department UUID
     * - starts_on (string): YYYY-MM-DD
     * - billing_cycle (object): periodicity {unit, period}, days_in_advance
     * - title (string)
     * - grouped_lines (array): sections with line_items
     * - payment_term (object): type (cash|end_of_month|after_invoice_date), days
     * - invoice_generation (object): action (draft|book|book_and_send)
     *   - sending_methods: required when action is 'book_and_send'
     *     - method: email|peppol|postal_service
     *
     * Optional fields:
     * - ends_on (string|null): YYYY-MM-DD
     * - deal_id (string|null)
     * - project_id (string|null)
     * - note (string|null)
     * - payment_method: direct_debit
     * - custom_fields (array)
     * - document_template_id (string)
     * - purchase_order_number (string|null): PO number to include on generated invoices
     * - delivery_information (object|null): Delivery details passed to generated invoices
     *
     * Returns HTTP 201 with data.{id, type}
     */
    public function create(array $data): array
    {
        $this->validateSubscriptionData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Validate subscription data against subscriptions.create / .update
     *
     * Before v2.2.7: `department_id` was not in the required list although the
     * API requires it; the billing cycle's period and days_in_advance were not
     * checked; `sending_methods` could omit `email` for book_and_send, which
     * the API rejects; and unknown fields were sent and dropped.
     *
     * @throws InvalidArgumentException
     */
    protected function validateSubscriptionData(array $data, string $operation): void
    {
        $endpoint = "subscriptions.{$operation}";

        $this->rejectUnknownFields(
            $data,
            $operation === 'create' ? self::WRITE_FIELDS : [...self::WRITE_FIELDS, 'id'],
            $endpoint
        );

        if ($operation === 'create') {
            foreach (self::REQUIRED_ON_CREATE as $field) {
                if (! isset($data[$field])) {
                    throw new InvalidArgumentException("Field '{$field}' is required for subscription creation");
                }
            }

            if (! isset($data['invoicee']['customer']['type']) || ! isset($data['invoicee']['customer']['id'])) {
                throw new InvalidArgumentException('Invoicee must include customer type and id');
            }
        }

        if ($operation === 'update' && ! isset($data['id'])) {
            throw new InvalidArgumentException('Subscription ID is required for update');
        }

        if (isset($data['invoicee']['customer']['type'])) {
            $this->validateCustomerType($data['invoicee']['customer']['type']);
        }

        if (isset($data['billing_cycle'])) {
            $this->validateBillingCycle($data['billing_cycle'], $endpoint);
        }

        if (isset($data['payment_term']['type'])) {
            $this->validatePaymentTermType($data['payment_term']['type']);
        }

        if (isset($data['invoice_generation'])) {
            $this->validateInvoiceGeneration($data['invoice_generation'], $endpoint);
        }

        if (isset($data['grouped_lines'])) {
            $this->validateGroupedLines($data['grouped_lines']);
        }

        $this->assertEnum($data['invoice_content'] ?? null, self::INVOICE_CONTENT, 'invoice_content', $endpoint);

        if (isset($data['delivery_information'])) {
            $info = $data['delivery_information'];

            if (! is_array($info) || ! isset($info['type'], $info['number_of_days_after_invoice_date'])) {
                throw new InvalidArgumentException(
                    'delivery_information needs type and number_of_days_after_invoice_date, or null to clear it'
                );
            }

            $this->assertEnum($info['type'], self::DELIVERY_TYPES, 'delivery_information.type', $endpoint);
        }
    }

    /**
     * billing_cycle: periodicity {unit, period} and days_in_advance, both
     * required. The allowed periods depend on the unit.
     *
     * @throws InvalidArgumentException
     */
    protected function validateBillingCycle(mixed $cycle, string $endpoint): void
    {
        if (! is_array($cycle) || ! isset($cycle['periodicity']) || ! array_key_exists('days_in_advance', $cycle)) {
            throw new InvalidArgumentException('billing_cycle needs periodicity and days_in_advance');
        }

        $unit = $cycle['periodicity']['unit'] ?? null;
        $period = $cycle['periodicity']['period'] ?? null;

        if (! is_string($unit)) {
            throw new InvalidArgumentException('billing_cycle.periodicity needs a unit: week, month or year');
        }

        $this->validateBillingCycleUnit($unit);

        if (! in_array($period, self::PERIODS[$unit], true)) {
            throw new InvalidArgumentException(
                "Invalid billing_cycle.periodicity.period for {$endpoint}: a {$unit}ly cycle takes "
                .implode(', ', self::PERIODS[$unit]).'.'
            );
        }

        $this->assertEnum($cycle['days_in_advance'], self::DAYS_IN_ADVANCE, 'billing_cycle.days_in_advance', $endpoint);
    }

    /**
     * invoice_generation: an action, an optional payment method, and — for
     * book_and_send only — sending methods that always include `email`.
     *
     * @throws InvalidArgumentException
     */
    protected function validateInvoiceGeneration(mixed $generation, string $endpoint): void
    {
        if (! is_array($generation) || ! isset($generation['action'])) {
            throw new InvalidArgumentException('invoice_generation needs an action: draft, book or book_and_send');
        }

        $this->validateInvoiceGenerationAction($generation['action']);
        $this->assertEnum($generation['payment_method'] ?? null, self::INVOICE_PAYMENT_METHODS, 'invoice_generation.payment_method', $endpoint);

        if ($generation['action'] === 'book_and_send') {
            if (! isset($generation['sending_methods'])) {
                throw new InvalidArgumentException('invoice_generation.sending_methods is required when action is book_and_send');
            }

            $this->validateSendingMethods($generation['sending_methods']);
        } elseif (isset($generation['sending_methods'])) {
            throw new InvalidArgumentException(
                "invoice_generation.sending_methods only applies to book_and_send, not {$generation['action']}"
            );
        }
    }

    /**
     * Validate customer type
     */
    protected function validateCustomerType(string $type): void
    {
        if (! in_array($type, $this->customerTypes)) {
            throw new InvalidArgumentException(
                'Invalid customer type. Must be one of: '.implode(', ', $this->customerTypes)
            );
        }
    }

    /**
     * Validate billing cycle unit
     */
    protected function validateBillingCycleUnit(string $unit): void
    {
        if (! in_array($unit, $this->billingCycleUnits)) {
            throw new InvalidArgumentException(
                'Invalid billing cycle unit. Must be one of: '.implode(', ', $this->billingCycleUnits)
            );
        }
    }

    /**
     * Validate payment term type
     */
    protected function validatePaymentTermType(string $type): void
    {
        if (! in_array($type, $this->paymentTermTypes)) {
            throw new InvalidArgumentException(
                'Invalid payment term type. Must be one of: '.implode(', ', $this->paymentTermTypes)
            );
        }
    }

    /**
     * Validate invoice generation action
     */
    protected function validateInvoiceGenerationAction(string $action): void
    {
        if (! in_array($action, $this->invoiceGenerationActions)) {
            throw new InvalidArgumentException(
                'Invalid invoice generation action. Must be one of: '.implode(', ', $this->invoiceGenerationActions)
            );
        }
    }

    /**
     * Validate sending methods array
     *
     * @throws InvalidArgumentException
     */
    protected function validateSendingMethods(array $methods): void
    {
        if (empty($methods)) {
            throw new InvalidArgumentException('Sending methods cannot be empty when provided');
        }

        foreach ($methods as $item) {
            if (! isset($item['method'])) {
                throw new InvalidArgumentException('Each sending method entry must have a "method" key');
            }

            if (! in_array($item['method'], $this->validSendingMethods)) {
                throw new InvalidArgumentException(
                    "Invalid sending method '{$item['method']}'. Must be one of: ".
                    implode(', ', $this->validSendingMethods)
                );
            }
        }

        // Specification 1.221.0: "Method email is always required; when peppol
        // is used, email acts as the fallback for when Peppol sending fails."
        if (! in_array('email', array_column($methods, 'method'), true)) {
            throw new InvalidArgumentException(
                'invoice_generation.sending_methods must always include email — it is the fallback when '
                .'Peppol or postal sending fails. E.g. [["method" => "peppol"], ["method" => "email"]].'
            );
        }
    }

    /**
     * Validate grouped lines structure
     */
    protected function validateGroupedLines(array $groupedLines): void
    {
        if (! is_array($groupedLines) || empty($groupedLines)) {
            throw new InvalidArgumentException('Grouped lines must be a non-empty array');
        }

        foreach ($groupedLines as $group) {
            if (! isset($group['line_items']) || ! is_array($group['line_items']) || empty($group['line_items'])) {
                throw new InvalidArgumentException('Each grouped line must have a non-empty line_items array');
            }

            foreach ($group['line_items'] as $item) {
                // unit_price is optional in the specification; before v2.2.7
                // the SDK required it.
                foreach (['quantity', 'description', 'tax_rate_id'] as $field) {
                    if (! isset($item[$field])) {
                        throw new InvalidArgumentException("Line item missing required field: {$field}");
                    }
                }

                if (array_key_exists('unit_price', $item)
                    && (! isset($item['unit_price']['amount']) || ($item['unit_price']['tax'] ?? null) !== 'excluding')) {
                    throw new InvalidArgumentException('Unit price must include amount, and tax "excluding"');
                }
            }
        }
    }

    /**
     * Update an existing subscription
     *
     * All fields except id are optional. Note:
     * - starts_on and billing_cycle can only be updated if no invoices have been generated yet
     *
     * Updatable fields:
     * - starts_on (string): YYYY-MM-DD (only if no invoices created yet)
     * - billing_cycle (object): only if no invoices created yet
     * - ends_on (string|null): YYYY-MM-DD
     * - title (string)
     * - invoicee (object)
     * - department_id (string)
     * - payment_term (object|null)
     * - project_id (string|null)
     * - deal_id (string|null)
     * - note (string|null)
     * - grouped_lines (array)
     * - invoice_generation (object): action (draft|book|book_and_send)
     *   - sending_methods: required when action is 'book_and_send'
     *     - method: email|peppol|postal_service
     * - payment_method: direct_debit
     * - custom_fields (array)
     * - document_template_id (string)
     * - purchase_order_number (string|null): PO number to include on generated invoices
     * - delivery_information (object|null): Delivery details passed to generated invoices
     *
     * Returns HTTP 204 (no body)
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateSubscriptionData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Deactivate a subscription
     */
    public function deactivate($id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.deactivate', [
            'id' => $id,
        ]);
    }

    /**
     * Get active subscriptions
     */
    public function active(array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['status' => ['active']], $additionalFilters),
            $options
        );
    }

    /**
     * List subscriptions with filtering, sorting, and pagination
     *
     * Response includes per item:
     * - id, title, note, status, department, invoicee, project
     * - starts_on, ends_on (nullable), next_renewal_date (nullable)
     * - billing_cycle, total, taxes, web_url
     * - created_at (string|null): ISO 8601 creation timestamp
     * - purchase_order_number (string|null): PO number on the subscription
     * - delivery_information (object|null): Delivery details (name, address, etc.)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        // Apply sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build filters array for the API request
     *
     * Before v2.2.7 any key was forwarded unchecked, and a `status` string was
     * sent as a string where the API expects an array.
     *
     * @throws InvalidArgumentException When a filter key, status or customer type is not supported
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'subscriptions.list');

        $built = [];

        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }

            if ($key === 'status' || $key === 'ids') {
                $value = is_array($value) ? array_values($value) : [$value];
            }

            if ($key === 'status') {
                foreach ($value as $status) {
                    if (! in_array($status, $this->statusValues, true)) {
                        throw new InvalidArgumentException(
                            'Invalid status value. Must be one of: '.implode(', ', $this->statusValues)
                        );
                    }
                }
            }

            if ($key === 'customer') {
                if (! is_array($value) || ! isset($value['type'], $value['id'])) {
                    throw new InvalidArgumentException('The customer filter takes ["type" => "contact"|"company", "id" => "..."].');
                }

                $this->validateCustomerType($value['type']);
            }

            $built[$key] = $value;
        }

        return $built;
    }

    /**
     * Build the sort array
     *
     * Before v2.2.7 a field name — `['sort' => 'title']` — reached array_map()
     * as a string and raised a TypeError: the Projects::buildSort() fatal of
     * v2.2.2, on another resource. Fields are now validated too.
     *
     * @param  array|string  $sort  A field name, a list of names, or sort objects
     *
     * @throws InvalidArgumentException When a sort field or order is not supported
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        return $this->normaliseSort($sort, $order);
    }

    /**
     * Get deactivated subscriptions
     */
    public function deactivated(array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['status' => ['deactivated']], $additionalFilters),
            $options
        );
    }

    /**
     * Get subscriptions for a specific customer
     */
    public function forCustomer(string $type, string $id, array $options = []): array
    {
        $this->validateCustomerType($type);

        return $this->list([
            'customer' => [
                'type' => $type,
                'id' => $id,
            ],
        ], $options);
    }

    /**
     * Get subscriptions for a specific department
     */
    public function forDepartment(string $departmentId, array $options = []): array
    {
        return $this->list(['department_id' => $departmentId], $options);
    }

    /**
     * Get subscriptions for a specific deal
     */
    public function forDeal(string $dealId, array $options = []): array
    {
        return $this->list(['deal_id' => $dealId], $options);
    }

    /**
     * Get subscriptions that generated a specific invoice
     */
    public function forInvoice(string $invoiceId, array $options = []): array
    {
        return $this->list(['invoice_id' => $invoiceId], $options);
    }

    /**
     * Get subscriptions by specific IDs
     */
    public function byIds(array $ids, array $options = []): array
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('At least one subscription ID is required');
        }

        return $this->list(['ids' => $ids], $options);
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created subscription data',
                'fields' => [
                    'data.id' => 'UUID of the created subscription',
                    'data.type' => 'Resource type',
                ],
            ],
            'info' => [
                'description' => 'Complete subscription information',
                'fields' => [
                    'data.id' => 'Subscription UUID',
                    'data.title' => 'Subscription title',
                    'data.note' => 'Subscription note (nullable, Markdown)',
                    'data.status' => 'Status (active, deactivated)',
                    'data.department' => 'Department reference',
                    'data.invoicee' => 'Invoicee information with customer and for_attention_of',
                    'data.project' => 'Project reference (nullable)',
                    'data.starts_on' => 'Start date',
                    'data.ends_on' => 'End date (nullable)',
                    'data.next_renewal_date' => 'Next renewal date (nullable)',
                    'data.billing_cycle' => 'Billing cycle with periodicity and days_in_advance',
                    'data.total' => 'Total amounts (tax_exclusive, tax_inclusive, taxes)',
                    'data.payment_term' => 'Payment term information',
                    'data.grouped_lines' => 'Array of grouped line items',
                    'data.invoice_generation' => 'Invoice generation settings (action, sending_methods, payment_method)',
                    'data.custom_fields' => 'Custom fields',
                    'data.document_template' => 'Document template reference',
                    'data.currency' => 'Currency code',
                    'data.web_url' => 'Web URL to the subscription',
                    'data.created_at' => 'Creation timestamp ISO 8601 (nullable)',
                    'data.purchase_order_number' => 'PO number on the subscription (nullable)',
                    'data.delivery_information' => 'Delivery details (nullable)',
                ],
            ],
            'list' => [
                'description' => 'Array of subscriptions with pagination',
                'fields' => [
                    'data' => 'Array of subscription objects',
                    'data[].id' => 'Subscription UUID',
                    'data[].title' => 'Subscription title',
                    'data[].note' => 'Note (nullable)',
                    'data[].status' => 'Status (active, deactivated)',
                    'data[].department' => 'Department reference',
                    'data[].invoicee' => 'Invoicee with customer and for_attention_of',
                    'data[].project' => 'Project reference (nullable)',
                    'data[].starts_on' => 'Start date',
                    'data[].ends_on' => 'End date (nullable)',
                    'data[].next_renewal_date' => 'Next renewal date (nullable)',
                    'data[].billing_cycle' => 'Billing cycle details',
                    'data[].total' => 'Total amounts',
                    'data[].web_url' => 'Web URL to the subscription',
                    'data[].created_at' => 'Creation timestamp ISO 8601 (nullable)',
                    'data[].purchase_order_number' => 'PO number on the subscription (nullable)',
                    'data[].delivery_information' => 'Delivery details (nullable)',
                ],
            ],
            'deactivate' => [
                'description' => 'Empty response on success (204 No Content)',
                'fields' => [],
            ],
        ];
    }
}
