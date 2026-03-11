<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Subscriptions extends Resource
{
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
        'customer.type' => 'Customer type (contact, company)',
        'customer.id' => 'Customer UUID',
        'status' => 'Array of statuses (active, deactivated)',
    ];

    // Available sort fields
    protected array $availableSortFields = [
        'title',
        'created_at',
        'status',
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
     * Get the base path for the subscriptions resource
     */
    protected function getBasePath(): string
    {
        return 'subscriptions';
    }

    /**
     * List subscriptions with filtering, sorting, and pagination
     *
     * Response includes per item:
     * - id, title, note, status, department, invoicee, project
     * - starts_on, ends_on (nullable), next_renewal_date (nullable)
     * - billing_cycle, total, taxes, web_url
     * - created_at (string|null): ISO 8601 creation timestamp
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
            $params['sort'] = $this->buildSort($options['sort']);
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

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
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
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
     *
     * Returns HTTP 201 with data.{id, type}
     */
    public function create(array $data): array
    {
        $this->validateSubscriptionData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
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
     * Validate subscription data for create/update operations
     */
    protected function validateSubscriptionData(array $data, string $operation): void
    {
        // Required fields for creation
        if ($operation === 'create') {
            $requiredFields = [
                'invoicee',
                'starts_on',
                'billing_cycle',
                'title',
                'grouped_lines',
                'payment_term',
                'invoice_generation',
            ];

            foreach ($requiredFields as $field) {
                if (! isset($data[$field])) {
                    throw new InvalidArgumentException("Field '{$field}' is required for subscription creation");
                }
            }

            // Validate invoicee structure
            if (! isset($data['invoicee']['customer']['type']) || ! isset($data['invoicee']['customer']['id'])) {
                throw new InvalidArgumentException('Invoicee must include customer type and id');
            }

            $this->validateCustomerType($data['invoicee']['customer']['type']);
        }

        // Required field for update
        if ($operation === 'update' && ! isset($data['id'])) {
            throw new InvalidArgumentException('Subscription ID is required for update');
        }

        // Validate customer type if provided
        if (isset($data['invoicee']['customer']['type'])) {
            $this->validateCustomerType($data['invoicee']['customer']['type']);
        }

        // Validate billing cycle if provided
        if (isset($data['billing_cycle']['periodicity']['unit'])) {
            $this->validateBillingCycleUnit($data['billing_cycle']['periodicity']['unit']);
        }

        // Validate payment term type if provided
        if (isset($data['payment_term']['type'])) {
            $this->validatePaymentTermType($data['payment_term']['type']);
        }

        // Validate invoice generation action if provided
        if (isset($data['invoice_generation']['action'])) {
            $this->validateInvoiceGenerationAction($data['invoice_generation']['action']);
        }

        // Validate sending methods if provided (only used when action is 'book_and_send')
        if (isset($data['invoice_generation']['sending_methods'])) {
            $this->validateSendingMethods($data['invoice_generation']['sending_methods']);
        }

        // Validate grouped_lines structure if provided
        if (isset($data['grouped_lines'])) {
            $this->validateGroupedLines($data['grouped_lines']);
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
                $requiredItemFields = ['quantity', 'description', 'unit_price', 'tax_rate_id'];
                foreach ($requiredItemFields as $field) {
                    if (! isset($item[$field])) {
                        throw new InvalidArgumentException("Line item missing required field: {$field}");
                    }
                }

                // Validate unit_price structure
                if (! isset($item['unit_price']['amount']) || ! isset($item['unit_price']['tax'])) {
                    throw new InvalidArgumentException('Unit price must include amount and tax fields');
                }
            }
        }
    }

    /**
     * Build filters for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $built = [];

        foreach ($filters as $key => $value) {
            // Validate status values
            if ($key === 'status' && is_array($value)) {
                foreach ($value as $status) {
                    if (! in_array($status, $this->statusValues)) {
                        throw new InvalidArgumentException(
                            'Invalid status value. Must be one of: '.implode(', ', $this->statusValues)
                        );
                    }
                }
            }

            // Validate customer type if provided
            if ($key === 'customer' && isset($value['type'])) {
                $this->validateCustomerType($value['type']);
            }

            $built[$key] = $value;
        }

        return $built;
    }

    /**
     * Build sort array for API request
     *
     * @param  array  $sort
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        if (isset($sort['field'])) {
            // Single sort field
            return [[
                'field' => $sort['field'],
                'order' => $sort['order'] ?? 'asc',
            ]];
        }

        // Multiple sort fields
        return array_map(function ($item) {
            return [
                'field' => $item['field'],
                'order' => $item['order'] ?? 'asc',
            ];
        }, $sort);
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
                ],
            ],
            'deactivate' => [
                'description' => 'Empty response on success (204 No Content)',
                'fields' => [],
            ],
        ];
    }
}
