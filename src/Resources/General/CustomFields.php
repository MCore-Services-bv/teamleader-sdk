<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use McoreServices\TeamleaderSDK\Resources\Resource;

class CustomFields extends Resource
{
    protected string $description = 'Manage custom field definitions in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = false;  // Based on API docs, no create endpoint

    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint

    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false; // Custom fields don't support pagination

    // Available includes for sideloading
    protected array $availableIncludes = [
        // No specific includes mentioned in API docs for custom fields
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of custom field UUIDs to filter by',
        'context' => 'Filter by context (contact, company, deal, project, invoice, product, milestone, ticket)',
        'type' => 'Filter by field type (single_line, multi_line, single_select, multi_select, checkbox, date, number, auto_number)',
    ];

    // Usage examples specific to custom fields
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all custom fields',
            'code' => '$customFields = $teamleader->customFields()->list();',
        ],
        'list_by_context' => [
            'description' => 'Get custom fields for specific context',
            'code' => '$contactFields = $teamleader->customFields()->list([\'context\' => \'contact\']);',
        ],
        'list_by_type' => [
            'description' => 'Get custom fields by type',
            'code' => '$selectFields = $teamleader->customFields()->list([\'type\' => \'single_select\']);',
        ],
        'list_specific' => [
            'description' => 'Get specific custom fields by ID',
            'code' => '$fields = $teamleader->customFields()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'get_single' => [
            'description' => 'Get a single custom field',
            'code' => '$field = $teamleader->customFields()->info(\'field-uuid-here\');',
        ],
    ];

    /**
     * Get the base path for the custom fields resource
     */
    protected function getBasePath(): string
    {
        return 'customFieldDefinitions';
    }

    /**
     * List custom fields with enhanced filtering
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (not used for custom fields)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get custom field information
     *
     * @param  string  $id  Custom field UUID
     * @param  mixed  $includes  Includes (not used for custom fields)
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        // Custom fields don't support includes, but we maintain compatibility
        if (! empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Get custom fields for a specific context
     *
     * @param  string  $context  The context to filter by
     */
    public function forContext(string $context): array
    {
        return $this->list(['context' => $context]);
    }

    /**
     * Get contact custom fields
     */
    public function forContacts(): array
    {
        return $this->forContext('contact');
    }

    /**
     * Get company custom fields
     */
    public function forCompanies(): array
    {
        return $this->forContext('company');
    }

    /**
     * Get deal/sale custom fields (API uses 'sale' context, not 'deal')
     */
    public function forDeals(): array
    {
        return $this->forContext('sale');
    }

    /**
     * Get sale custom fields (direct API context name)
     */
    public function forSales(): array
    {
        return $this->forContext('sale');
    }

    /**
     * Get subscription custom fields
     */
    public function forSubscriptions(): array
    {
        return $this->forContext('subscription');
    }

    /**
     * Get external cost/order custom fields
     */
    public function forExternalCosts(): array
    {
        return $this->forContext('pro_external_cost');
    }

    /**
     * Get quotation custom fields
     */
    public function forQuotations(): array
    {
        return $this->forContext('quotation');
    }

    /**
     * Get credit note custom fields
     */
    public function forCreditnotes(): array
    {
        return $this->forContext('creditnote');
    }

    /**
     * Get project custom fields
     */
    public function forProjects(): array
    {
        return $this->forContext('project');
    }

    /**
     * Get invoice custom fields
     */
    public function forInvoices(): array
    {
        return $this->forContext('invoice');
    }

    /**
     * Get product custom fields
     */
    public function forProducts(): array
    {
        return $this->forContext('product');
    }

    /**
     * Get milestone custom fields
     */
    public function forMilestones(): array
    {
        return $this->forContext('milestone');
    }

    /**
     * Get ticket custom fields
     */
    public function forTickets(): array
    {
        return $this->forContext('ticket');
    }

    /**
     * Get custom fields by type
     *
     * @param  string  $type  The field type
     */
    public function byType(string $type): array
    {
        return $this->list(['type' => $type]);
    }

    /**
     * Get custom fields by specific IDs
     *
     * @param  array  $ids  Array of custom field UUIDs
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

        // Handle context filter
        if (isset($filters['context'])) {
            if (is_string($filters['context'])) {
                $apiFilters['context'] = $filters['context'];
            }
        }

        // Handle type filter
        if (isset($filters['type'])) {
            if (is_string($filters['type'])) {
                $apiFilters['type'] = $filters['type'];
            }
        }

        return $apiFilters;
    }

    /**
     * Get available contexts for custom fields
     */
    public function getAvailableContexts(): array
    {
        return [
            'contact' => 'Contact custom fields',
            'company' => 'Company custom fields',
            'deal' => 'Deal custom fields',
            'project' => 'Project custom fields',
            'invoice' => 'Invoice custom fields',
            'product' => 'Product custom fields',
            'milestone' => 'Milestone custom fields',
            'ticket' => 'Ticket custom fields',
        ];
    }

    /**
     * Get available field types
     */
    public function getAvailableTypes(): array
    {
        return [
            'single_line' => 'Single line text field',
            'multi_line' => 'Multi-line text field',
            'single_select' => 'Single selection dropdown',
            'multi_select' => 'Multiple selection field',
            'checkbox' => 'Checkbox field',
            'date' => 'Date field',
            'number' => 'Number field',
            'auto_number' => 'Auto-incrementing number field',
        ];
    }

    /**
     * Check if a field type supports options
     *
     * @param  string  $type  Field type
     */
    public function typeHasOptions(string $type): bool
    {
        return in_array($type, ['single_select', 'multi_select']);
    }

    /**
     * Check if a field type is a reference type
     *
     * @param  string  $type  Field type
     */
    public function typeIsReference(string $type): bool
    {
        return in_array($type, ['company', 'contact', 'product', 'user']);
    }

    /**
     * Get the actual context name used by the API (handles deal/sale discrepancy)
     *
     * @param  string  $context  Requested context
     * @return string Actual API context
     */
    public function getApiContext(string $context): string
    {
        // Handle the deal/sale discrepancy
        if ($context === 'deal') {
            // In reality, API might use 'sale' - this could be environment dependent
            return 'sale';
        }

        return $context;
    }

    /**
     * Get all supported contexts (both documented and actual)
     */
    public function getAllSupportedContexts(): array
    {
        return array_keys($this->getAvailableContexts());
    }

    /**
     * Get all supported field types
     */
    public function getAllSupportedTypes(): array
    {
        return array_keys($this->getAvailableTypes());
    }

    /**
     * Override the default validation since custom fields have limited operations
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Custom fields are read-only in this API, so no validation needed for create/update
        return $data;
    }

    /**
     * Override getSuggestedIncludes as custom fields don't have common includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Custom fields don't have sideloadable relationships in the API
    }
}
