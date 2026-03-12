<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class CustomFields extends Resource
{
    protected string $description = 'Manage custom field definitions in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint

    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true; // FIX: API does paginate (default page size = 20)

    // Available includes for sideloading
    protected array $availableIncludes = [
        // No specific includes mentioned in API docs for custom fields
    ];

    // Valid types based on API documentation
    protected array $validTypes = [
        'single_line',
        'multi_line',
        'single_select',
        'multi_select',
        'date',
        'money',
        'auto_increment',
        'integer',
        'number',
        'boolean',
        'email',
        'telephone',
        'url',
        'company',
        'contact',
        'product',
        'user',
    ];

    // Valid contexts based on API documentation
    protected array $validContexts = [
        'contact',
        'company',
        'deal',
        'project',
        'milestone',
        'product',
        'invoice',
        'subscription',
        'ticket',
    ];

    // Types that support the 'options' configuration key
    protected array $typesWithOptions = [
        'single_select',
        'multi_select',
    ];

    // Types that support the 'searchable' configuration key
    protected array $typesWithSearchable = [
        'single_line',
        'company',
        'integer',
        'number',
        'auto_increment',
        'email',
        'telephone',
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of custom field UUIDs to filter by',
        'context' => 'Filter by context (contact, company, deal, project, milestone, product, invoice, subscription, ticket)',
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
        'list_specific' => [
            'description' => 'Get specific custom fields by ID',
            'code' => '$fields = $teamleader->customFields()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'get_single' => [
            'description' => 'Get a single custom field',
            'code' => '$field = $teamleader->customFields()->info(\'field-uuid-here\');',
        ],
        'create_text' => [
            'description' => 'Create a single-line text custom field for contacts',
            'code' => '$field = $teamleader->customFields()->create([\'label\' => \'VAT Number\', \'type\' => \'single_line\', \'context\' => \'contact\']);',
        ],
        'create_select' => [
            'description' => 'Create a single-select dropdown for deals with options',
            'code' => '$field = $teamleader->customFields()->create([\'label\' => \'Lead Source\', \'type\' => \'single_select\', \'context\' => \'deal\', \'configuration\' => [\'options\' => [\'Referral\', \'Website\', \'Cold Call\']]]);',
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
     * List custom fields with optional filtering and pagination.
     *
     * The Teamleader API defaults to a page size of 20. Always pass
     * page_size and page_number via $options to retrieve all records.
     *
     * @param  array  $filters  Filters to apply (ids, context)
     * @param  array  $options  Pagination options: page_size, page_number
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination — required to retrieve more than the default 20 records
        $params['page'] = [
            'size' => $options['page_size'] ?? 20,
            'number' => $options['page_number'] ?? 1,
        ];

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

        if (! empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Create a new custom field definition.
     * Requires the 'settings' OAuth scope.
     *
     * @param  array  $data  Custom field data
     * @return array API response containing data.id and data.type of the created field
     */
    public function create(array $data): array
    {
        $data = $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Validate data for the create endpoint
     *
     * @param  array  $data  Input data
     * @return array Validated and cleaned data
     *
     * @throws InvalidArgumentException When required fields are missing or values are invalid
     */
    protected function validateCreateData(array $data): array
    {
        // Validate required: label
        if (empty($data['label']) || ! is_string($data['label'])) {
            throw new InvalidArgumentException('Custom field label is required and must be a non-empty string.');
        }

        // Validate required: type
        if (empty($data['type'])) {
            throw new InvalidArgumentException('Custom field type is required.');
        }

        if (! in_array($data['type'], $this->validTypes, true)) {
            throw new InvalidArgumentException(
                "Invalid custom field type '{$data['type']}'. Valid types: ".implode(', ', $this->validTypes)
            );
        }

        // Validate required: context
        if (empty($data['context'])) {
            throw new InvalidArgumentException('Custom field context is required.');
        }

        if (! in_array($data['context'], $this->validContexts, true)) {
            throw new InvalidArgumentException(
                "Invalid custom field context '{$data['context']}'. Valid contexts: ".implode(', ', $this->validContexts)
            );
        }

        // Validate optional: configuration
        if (isset($data['configuration']) && is_array($data['configuration'])) {
            $data['configuration'] = $this->validateConfiguration($data['configuration'], $data['type']);
        }

        return $data;
    }

    /**
     * Validate the configuration object based on the field type
     *
     * @param  array  $configuration  The configuration array
     * @param  string  $type  The field type
     * @return array Validated configuration
     *
     * @throws InvalidArgumentException When configuration keys are invalid for the given type
     */
    protected function validateConfiguration(array $configuration, string $type): array
    {
        // Validate 'options' — only for single_select and multi_select
        if (isset($configuration['options'])) {
            if (! in_array($type, $this->typesWithOptions, true)) {
                throw new InvalidArgumentException(
                    "Configuration key 'options' is only valid for types: ".implode(', ', $this->typesWithOptions).". Got '{$type}'."
                );
            }

            if (! is_array($configuration['options'])) {
                throw new InvalidArgumentException("Configuration 'options' must be an array of strings.");
            }
        }

        // Validate 'default_value' — only for auto_increment
        if (isset($configuration['default_value']) && $type !== 'auto_increment') {
            throw new InvalidArgumentException(
                "Configuration key 'default_value' is only valid for type 'auto_increment'. Got '{$type}'."
            );
        }

        // Validate 'searchable' — only for specific types
        if (isset($configuration['searchable'])) {
            if (! in_array($type, $this->typesWithSearchable, true)) {
                throw new InvalidArgumentException(
                    "Configuration key 'searchable' is only valid for types: ".implode(', ', $this->typesWithSearchable).". Got '{$type}'."
                );
            }

            if (! is_bool($configuration['searchable'])) {
                throw new InvalidArgumentException("Configuration 'searchable' must be a boolean.");
            }
        }

        return $configuration;
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
     * Get deal custom fields
     */
    public function forDeals(): array
    {
        return $this->forContext('deal');
    }

    /**
     * Get sale custom fields (alias for forDeals — same API context)
     */
    public function forSales(): array
    {
        return $this->forContext('deal');
    }

    /**
     * Get subscription custom fields
     */
    public function forSubscriptions(): array
    {
        return $this->forContext('subscription');
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
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        if (isset($filters['context']) && is_string($filters['context'])) {
            $apiFilters['context'] = $filters['context'];
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
            'milestone' => 'Milestone custom fields',
            'product' => 'Product custom fields',
            'invoice' => 'Invoice custom fields',
            'subscription' => 'Subscription custom fields',
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
            'date' => 'Date field',
            'money' => 'Money / currency field',
            'auto_increment' => 'Auto-incrementing number field',
            'integer' => 'Integer number field',
            'number' => 'Decimal number field',
            'boolean' => 'Boolean (yes/no) field',
            'email' => 'Email address field',
            'telephone' => 'Telephone number field',
            'url' => 'URL field',
            'company' => 'Company reference field',
            'contact' => 'Contact reference field',
            'product' => 'Product reference field',
            'user' => 'User reference field',
        ];
    }

    /**
     * Check if a field type supports the 'options' configuration key
     *
     * @param  string  $type  Field type
     */
    public function typeHasOptions(string $type): bool
    {
        return in_array($type, $this->typesWithOptions, true);
    }

    /**
     * Check if a field type supports the 'searchable' configuration key
     *
     * @param  string  $type  Field type
     */
    public function typeIsSearchable(string $type): bool
    {
        return in_array($type, $this->typesWithSearchable, true);
    }

    /**
     * Check if a field type is a reference type (links to another entity)
     *
     * @param  string  $type  Field type
     */
    public function typeIsReference(string $type): bool
    {
        return in_array($type, ['company', 'contact', 'product', 'user'], true);
    }

    /**
     * Get all supported contexts
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
     * Override the default validation — used only for create
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        if ($operation === 'create') {
            return $this->validateCreateData($data);
        }

        return $data;
    }

    /**
     * Override getSuggestedIncludes as custom fields don't have sideloadable relationships
     */
    protected function getSuggestedIncludes(): array
    {
        return [];
    }
}
