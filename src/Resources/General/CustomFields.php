<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class CustomFields extends Resource
{
    /** Body fields customFieldDefinitions.create accepts */
    public const CREATE_FIELDS = ['label', 'type', 'context', 'required', 'configuration'];

    /**
     * Safety limit on the number of pages all() will fetch.
     */
    protected const MAX_PAGES = 50;

    protected string $description = 'Manage custom field definitions in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint

    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true; // API paginates (default page size = 20)

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = true;    // customFieldDefinitions.list sorts on label and context

    protected bool $supportsSideloading = false; // The endpoint declares no includes

    // Available includes for sideloading (none — the API declares none)
    protected array $availableIncludes = [];

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

    /**
     * Valid context values.
     *
     * Verified against @teamleader/focus-api-specification v1.197.0 — this is
     * the complete Context enum. Note that `quotation` and `creditnote` are not
     * in it; helpers for both were removed in v2.1.2 because the API rejects them.
     */
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

    /**
     * Context values the API returns that differ from the value it accepts.
     *
     * Teamleader accepts `context: deal` as a filter but returns `context: sale`
     * on the definitions it sends back. Teamleader has confirmed this is a defect
     * on their side. Until it is fixed, responses are normalised here so that what
     * you filter by and what you read back are the same string — otherwise any
     * comparison against 'deal' silently matches nothing.
     *
     * Normalisation is one-directional on purpose: `sale` is not accepted as an
     * inbound filter value, because the API rejects it and quietly translating an
     * invalid input into a valid one would hide the discrepancy in both directions.
     *
     * Remove an entry here once the API stops returning the left-hand value.
     */
    protected array $contextResponseAliases = [
        'sale' => 'deal',
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

    /**
     * Filters accepted by customFieldDefinitions.list.
     *
     * The keys of this array are the filter whitelist used by buildFilters().
     * There is no `type` filter — see byType(), which filters client-side.
     */
    protected array $commonFilters = [
        'ids' => 'Array of custom field UUIDs to filter by',
        'context' => 'Filter by context (contact, company, deal, project, milestone, product, invoice, subscription, ticket)',
    ];

    /**
     * Sort fields accepted by customFieldDefinitions.list.
     */
    protected array $availableSortFields = [
        'label' => 'Sort by field label',
        'context' => 'Sort by context',
    ];

    // Usage examples specific to custom fields
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get the first page of custom fields',
            'code' => '$customFields = $teamleader->customFields()->list();',
        ],
        'list_every_page' => [
            'description' => 'Get every custom field definition, paging automatically',
            'code' => '$customFields = $teamleader->customFields()->all();',
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
     * List custom fields with optional filtering, sorting and pagination.
     *
     * The Teamleader API defaults to a page size of 20. Pass page_size and
     * page_number via $options, or use all() to page automatically.
     *
     * Deal definitions come back from the API with `context: sale`; this method
     * normalises that to `deal` — see $contextResponseAliases.
     *
     * @param  array  $filters  Filters to apply (ids, context)
     * @param  array  $options  page_size, page_number, sort, sort_order
     *
     * @throws InvalidArgumentException When a filter key or sort field is not supported
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $apiFilters = $this->buildFilters($filters);

            if (! empty($apiFilters)) {
                $params['filter'] = $apiFilters;
            }
        }

        // Apply pagination — required to retrieve more than the default 20 records
        $params['page'] = [
            'size' => $options['page_size'] ?? 20,
            'number' => $options['page_number'] ?? 1,
        ];

        // Apply sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        $response = $this->api->request('POST', $this->getBasePath().'.list', $params);

        return $this->normaliseContextValues($response);
    }

    /**
     * Get every custom field definition, paging until the list is exhausted.
     *
     * The API returns no total count, so the end of the list is inferred from a
     * page shorter than the requested page size. A full final page therefore
     * costs one extra empty request.
     *
     * Makes multiple API calls. The returned array carries `data` and
     * `total_count` but no `headers`, since there is no single response to take
     * them from.
     *
     * @param  array  $filters  Filters to apply to every page
     * @param  int  $pageSize  Records per request
     */
    public function all(array $filters = [], int $pageSize = 100): array
    {
        $definitions = [];
        $pageNumber = 1;

        do {
            $response = $this->list($filters, [
                'page_size' => $pageSize,
                'page_number' => $pageNumber,
            ]);

            $batch = $response['data'] ?? [];

            if (! is_array($batch) || $batch === []) {
                break;
            }

            $definitions = array_merge($definitions, $batch);
            $pageNumber++;
        } while (count($batch) === $pageSize && $pageNumber <= self::MAX_PAGES);

        return [
            'data' => $definitions,
            'total_count' => count($definitions),
        ];
    }

    /**
     * Get custom field information
     *
     * Deal definitions come back from the API with `context: sale`; this method
     * normalises that to `deal` — see $contextResponseAliases.
     *
     * @param  string  $id  Custom field UUID
     * @param  mixed  $includes  Not supported by this endpoint
     *
     * @throws InvalidArgumentException When includes are requested
     */
    public function info($id, $includes = null): array
    {
        if (! empty($includes)) {
            throw new InvalidArgumentException(
                'customFieldDefinitions does not support sideloading. '
                .'Call info() with the id only.'
            );
        }

        $response = $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);

        return $this->normaliseContextValues($response);
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
     * Rewrite context values the API returns inconsistently.
     *
     * Handles both response shapes: list() returns a numerically indexed array of
     * definitions, info() returns a single definition. An empty data array passes
     * through untouched.
     */
    protected function normaliseContextValues(array $response): array
    {
        if (! isset($response['data']) || ! is_array($response['data'])) {
            return $response;
        }

        if (array_is_list($response['data'])) {
            foreach ($response['data'] as $index => $definition) {
                if (! is_array($definition)) {
                    continue;
                }

                $context = $definition['context'] ?? null;

                if (is_string($context) && isset($this->contextResponseAliases[$context])) {
                    $response['data'][$index]['context'] = $this->contextResponseAliases[$context];
                }
            }

            return $response;
        }

        $context = $response['data']['context'] ?? null;

        if (is_string($context) && isset($this->contextResponseAliases[$context])) {
            $response['data']['context'] = $this->contextResponseAliases[$context];
        }

        return $response;
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
        // customFieldDefinitions.create takes these five; anything else would be ignored.
        $unknown = array_diff(array_keys($data), self::CREATE_FIELDS);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'customFieldDefinitions.create does not accept: '.implode(', ', $unknown)
                .'. Accepted fields: '.implode(', ', self::CREATE_FIELDS).'.'
            );
        }

        if (isset($data['required']) && ! is_bool($data['required'])) {
            throw new InvalidArgumentException('required must be true or false.');
        }
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

        $this->validateContext($data['context']);

        // Validate optional: configuration
        if (isset($data['configuration']) && is_array($data['configuration'])) {
            $data['configuration'] = $this->validateConfiguration($data['configuration'], $data['type']);
        }

        return $data;
    }

    /**
     * Validate a context value against the API's Context enum
     *
     * @throws InvalidArgumentException
     */
    protected function validateContext(string $context): void
    {
        if (in_array($context, $this->validContexts, true)) {
            return;
        }

        $message = "Invalid custom field context '{$context}'. Valid contexts: "
            .implode(', ', $this->validContexts).'.';

        if (isset($this->contextResponseAliases[$context])) {
            $message .= " The API returns '{$context}' in responses but does not accept it as a filter; "
                ."use '{$this->contextResponseAliases[$context]}' instead. The SDK normalises this "
                .'automatically on the way back.';
        }

        throw new InvalidArgumentException($message);
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
     * @param  array  $options  Pagination and sorting options
     *
     * @throws InvalidArgumentException When the context is not one the API accepts
     */
    public function forContext(string $context, array $options = []): array
    {
        $this->validateContext($context);

        return $this->list(['context' => $context], $options);
    }

    /**
     * Get contact custom fields
     */
    public function forContacts(array $options = []): array
    {
        return $this->forContext('contact', $options);
    }

    /**
     * Get company custom fields
     */
    public function forCompanies(array $options = []): array
    {
        return $this->forContext('company', $options);
    }

    /**
     * Get deal custom fields
     *
     * The API returns these with `context: sale`; the SDK normalises that to
     * `deal` so the value you filter by and the value you read back match.
     */
    public function forDeals(array $options = []): array
    {
        return $this->forContext('deal', $options);
    }

    /**
     * Get sale custom fields — alias for forDeals()
     *
     * Kept for callers who think in the API's response vocabulary. `sale` is what
     * the API returns; `deal` is what it accepts, and what the SDK returns after
     * normalisation.
     */
    public function forSales(array $options = []): array
    {
        return $this->forContext('deal', $options);
    }

    /**
     * Get subscription custom fields
     */
    public function forSubscriptions(array $options = []): array
    {
        return $this->forContext('subscription', $options);
    }

    /**
     * Get project custom fields
     */
    public function forProjects(array $options = []): array
    {
        return $this->forContext('project', $options);
    }

    /**
     * Get invoice custom fields
     */
    public function forInvoices(array $options = []): array
    {
        return $this->forContext('invoice', $options);
    }

    /**
     * Get product custom fields
     */
    public function forProducts(array $options = []): array
    {
        return $this->forContext('product', $options);
    }

    /**
     * Get milestone custom fields
     */
    public function forMilestones(array $options = []): array
    {
        return $this->forContext('milestone', $options);
    }

    /**
     * Get ticket custom fields
     */
    public function forTickets(array $options = []): array
    {
        return $this->forContext('ticket', $options);
    }

    /**
     * Get custom fields of a specific type
     *
     * The API has no `type` filter, so this pages through every definition and
     * filters client-side. It therefore makes multiple API calls and returns
     * `data` and `total_count` without `headers`.
     *
     * @param  string  $type  The field type
     *
     * @throws InvalidArgumentException When the type is not one the API defines
     */
    public function byType(string $type): array
    {
        if (! in_array($type, $this->validTypes, true)) {
            throw new InvalidArgumentException(
                "Invalid custom field type '{$type}'. Valid types: ".implode(', ', $this->validTypes)
            );
        }

        $all = $this->all();

        $matching = array_values(array_filter(
            $all['data'],
            fn ($definition) => is_array($definition) && ($definition['type'] ?? null) === $type
        ));

        return [
            'data' => $matching,
            'total_count' => count($matching),
        ];
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
     * Build the filter object for the API request
     *
     * Rejects keys customFieldDefinitions.list does not accept. The API ignores
     * unrecognised filter keys and answers 200 with the full unfiltered set, so
     * forwarding them produces silently wrong results rather than an error.
     *
     * @throws InvalidArgumentException When a filter key is not supported
     */
    protected function buildFilters(array $filters): array
    {
        $supported = array_keys($this->commonFilters);
        $unknown = array_diff(array_keys($filters), $supported);

        if ($unknown !== []) {
            $message = 'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key')
                .' for customFieldDefinitions.list: '.implode(', ', $unknown)
                .'. Supported: '.implode(', ', $supported).'.';

            if (in_array('type', $unknown, true)) {
                $message .= ' The API has no type filter; use byType(), which filters client-side.';
            }

            throw new InvalidArgumentException($message);
        }

        $apiFilters = [];

        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? $filters['ids'] : [$filters['ids']];
        }

        if (isset($filters['context'])) {
            if (! is_string($filters['context'])) {
                throw new InvalidArgumentException('The context filter must be a string.');
            }

            $this->validateContext($filters['context']);

            $apiFilters['context'] = $filters['context'];
        }

        return $apiFilters;
    }

    /**
     * Build the sort object for the API request
     *
     * The API expects an array of objects — [['field' => ..., 'order' => ...]].
     *
     * @param  array|string  $sort  A field name, an array of field names, or an
     *                              array of ['field' => ..., 'order' => ...] entries
     * @param  string  $order  Default order applied to entries that do not carry one
     *
     * @throws InvalidArgumentException When a sort field or order is not supported
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        $order = $this->normaliseSortOrder($order);

        // Already a list of sort objects
        if (is_array($sort) && isset($sort[0]) && is_array($sort[0])) {
            return array_map(function (array $entry) use ($order) {
                return [
                    'field' => $this->validateSortField($entry['field'] ?? null),
                    'order' => $this->normaliseSortOrder($entry['order'] ?? $order),
                ];
            }, $sort);
        }

        // A single ['field' => ..., 'order' => ...] entry
        if (is_array($sort) && isset($sort['field'])) {
            return [[
                'field' => $this->validateSortField($sort['field']),
                'order' => $this->normaliseSortOrder($sort['order'] ?? $order),
            ]];
        }

        // A list of field names
        if (is_array($sort)) {
            return array_map(fn ($field) => [
                'field' => $this->validateSortField($field),
                'order' => $order,
            ], array_values($sort));
        }

        // A single field name
        return [[
            'field' => $this->validateSortField($sort),
            'order' => $order,
        ]];
    }

    /**
     * Ensure a sort field is one the API accepts
     *
     * @throws InvalidArgumentException
     */
    protected function validateSortField(mixed $field): string
    {
        if (! is_string($field) || ! array_key_exists($field, $this->availableSortFields)) {
            throw new InvalidArgumentException(
                'Invalid sort field: '.(is_string($field) ? $field : gettype($field))
                .'. customFieldDefinitions.list accepts: '
                .implode(', ', array_keys($this->availableSortFields)).'.'
            );
        }

        return $field;
    }

    /**
     * Ensure a sort order is asc or desc
     *
     * @throws InvalidArgumentException
     */
    protected function normaliseSortOrder(mixed $order): string
    {
        if (! is_string($order)) {
            throw new InvalidArgumentException('Sort order must be a string: asc or desc.');
        }

        $normalised = strtolower($order);

        if (! in_array($normalised, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Invalid sort order: {$order}. Must be asc or desc.");
        }

        return $normalised;
    }

    /**
     * Get available contexts for custom fields
     */
    public function getAvailableContexts(): array
    {
        return [
            'contact' => 'Contact custom fields',
            'company' => 'Company custom fields',
            'deal' => 'Deal custom fields (returned by the API as "sale", normalised to "deal")',
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
