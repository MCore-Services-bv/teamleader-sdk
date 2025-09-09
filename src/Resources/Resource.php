<?php

namespace McoreServices\TeamleaderSDK\Resources;

use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Traits\FilterTrait;

abstract class Resource
{
    use FilterTrait;

    /**
     * @var TeamleaderSDK
     */
    protected $api;

    // Resource capabilities and configuration
    protected array $defaultIncludes = [];
    protected bool $supportsPagination = true;
    protected bool $supportsFiltering = true;
    protected bool $supportsSorting = true;
    protected bool $supportsSideloading = true;
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;

    // Documentation properties
    protected array $commonFilters = [];
    protected array $availableIncludes = [];
    protected array $usageExamples = [];
    protected string $description = '';

    /**
     * Resource constructor.
     *
     * @param TeamleaderSDK $api
     */
    public function __construct(TeamleaderSDK $api)
    {
        $this->api = $api;
    }

    /**
     * Get the base path for the resource.
     *
     * @return string
     */
    abstract protected function getBasePath();

    /**
     * Get comprehensive documentation for this resource
     */
    public function getDocumentation(): array
    {
        return [
            'resource' => class_basename($this),
            'endpoint' => $this->getBasePath(),
            'description' => $this->getResourceDescription(),
            'capabilities' => $this->getCapabilities(),
            'methods' => $this->getAvailableMethods(),
            'common_filters' => $this->getCommonFilters(),
            'available_includes' => $this->getAvailableIncludes(),
            'usage_examples' => $this->getUsageExamples(),
            'rate_limit_cost' => $this->getRateLimitCost(),
            'response_format' => $this->getResponseFormat()
        ];
    }

    /**
     * Get resource description
     */
    protected function getResourceDescription(): string
    {
        if (!empty($this->description)) {
            return $this->description;
        }

        // Auto-generate description based on class name
        $resourceName = class_basename($this);
        return "Manage {$resourceName} in Teamleader Focus";
    }

    /**
     * Get available methods based on capabilities
     */
    private function getAvailableMethods(): array
    {
        $methods = [];

        // Always available
        $methods['list'] = [
            'description' => 'Get a paginated list of resources',
            'parameters' => [
                'filters' => 'Array of filters to apply',
                'options' => 'Pagination, sorting, and include options'
            ],
            'example' => "\$this->api->{$this->getResourceMethodName()}()->list(['status' => 'active'])"
        ];

        $methods['info'] = [
            'description' => 'Get detailed information about a specific resource',
            'parameters' => [
                'id' => 'Resource UUID',
                'includes' => 'Array or string of relations to include'
            ],
            'example' => "\$this->api->{$this->getResourceMethodName()}()->info('uuid-here')"
        ];

        // Conditional methods
        if ($this->supportsCreation) {
            $methods['create'] = [
                'description' => 'Create a new resource',
                'parameters' => [
                    'data' => 'Array of resource data'
                ],
                'example' => "\$this->api->{$this->getResourceMethodName()}()->create(['name' => 'Example'])"
            ];
        }

        if ($this->supportsUpdate) {
            $methods['update'] = [
                'description' => 'Update an existing resource',
                'parameters' => [
                    'id' => 'Resource UUID',
                    'data' => 'Array of data to update'
                ],
                'example' => "\$this->api->{$this->getResourceMethodName()}()->update('uuid-here', ['name' => 'Updated'])"
            ];
        }

        if ($this->supportsDeletion) {
            $methods['delete'] = [
                'description' => 'Delete a resource',
                'parameters' => [
                    'id' => 'Resource UUID'
                ],
                'example' => "\$this->api->{$this->getResourceMethodName()}()->delete('uuid-here')"
            ];
        }

        if ($this->supportsBatch) {
            $methods['batch'] = [
                'description' => 'Perform batch operations',
                'parameters' => [
                    'operation' => 'Batch operation type',
                    'items' => 'Array of items to process'
                ],
                'example' => "\$this->api->{$this->getResourceMethodName()}()->batch('create', [...])"
            ];
        }

        return $methods;
    }

    /**
     * Get resource method name for examples
     */
    private function getResourceMethodName(): string
    {
        $className = class_basename($this);
        return lcfirst($className);
    }

    /**
     * Get common filters for this resource
     */
    protected function getCommonFilters(): array
    {
        if (!empty($this->commonFilters)) {
            return $this->commonFilters;
        }

        // Default common filters that most resources support
        return [
            'updated_since' => 'ISO 8601 datetime - Get resources updated after this time',
            'created_after' => 'ISO 8601 datetime - Get resources created after this time',
            'created_before' => 'ISO 8601 datetime - Get resources created before this time'
        ];
    }

    /**
     * Get available includes for sideloading
     */
    protected function getAvailableIncludes(): array
    {
        return array_merge($this->defaultIncludes, $this->availableIncludes);
    }

    /**
     * Get usage examples
     */
    protected function getUsageExamples(): array
    {
        if (!empty($this->usageExamples)) {
            return $this->usageExamples;
        }

        $methodName = $this->getResourceMethodName();

        return [
            'basic_list' => [
                'description' => 'Get all resources with pagination',
                'code' => "\$resources = \$teamleader->{$methodName}()->list();"
            ],
            'filtered_list' => [
                'description' => 'Get resources with filters',
                'code' => "\$resources = \$teamleader->{$methodName}()->list(['status' => 'active']);"
            ],
            'with_pagination' => [
                'description' => 'Get resources with custom pagination',
                'code' => "\$resources = \$teamleader->{$methodName}()->list([], ['page_size' => 50, 'page_number' => 2]);"
            ],
            'with_includes' => [
                'description' => 'Get resources with related data',
                'code' => "\$resources = \$teamleader->{$methodName}()->with(['responsible_user'])->list();"
            ],
            'single_resource' => [
                'description' => 'Get a single resource',
                'code' => "\$resource = \$teamleader->{$methodName}()->info('uuid-here');"
            ]
        ];
    }

    /**
     * Get rate limit cost information
     */
    protected function getRateLimitCost(): array
    {
        return [
            'list' => 1,
            'info' => 1,
            'create' => 1,
            'update' => 1,
            'delete' => 1,
            'batch' => 'varies based on items'
        ];
    }

    /**
     * Get response format information
     */
    protected function getResponseFormat(): array
    {
        return [
            'list' => [
                'data' => 'Array of resources',
                'pagination' => 'Pagination metadata',
                'included' => 'Sideloaded related resources',
                'meta' => 'Additional metadata'
            ],
            'info' => [
                'data' => 'Single resource object',
                'included' => 'Sideloaded related resources'
            ],
            'create' => [
                'data' => 'Created resource object'
            ],
            'update' => [
                'data' => 'Updated resource object'
            ],
            'delete' => [
                'success' => 'Boolean indicating success',
                'message' => 'Confirmation message'
            ]
        ];
    }

    /**
     * Generate interactive documentation
     */
    public function generateMarkdownDocs(): string
    {
        $docs = $this->getDocumentation();
        $resourceName = $docs['resource'];

        $markdown = "# {$resourceName}\n\n";
        $markdown .= "{$docs['description']}\n\n";

        // Endpoint information
        $markdown .= "## Endpoint\n\n";
        $markdown .= "`{$docs['endpoint']}`\n\n";

        // Capabilities
        $markdown .= "## Capabilities\n\n";
        foreach ($docs['capabilities'] as $capability => $supported) {
            $status = $supported ? '✅ Supported' : '❌ Not Supported';
            $markdown .= "- **" . ucwords(str_replace('_', ' ', $capability)) . "**: {$status}\n";
        }
        $markdown .= "\n";

        // Methods
        $markdown .= "## Available Methods\n\n";
        foreach ($docs['methods'] as $method => $info) {
            $markdown .= "### `{$method}()`\n\n";
            $markdown .= "{$info['description']}\n\n";

            if (!empty($info['parameters'])) {
                $markdown .= "**Parameters:**\n";
                foreach ($info['parameters'] as $param => $description) {
                    $markdown .= "- `{$param}`: {$description}\n";
                }
                $markdown .= "\n";
            }

            $markdown .= "**Example:**\n";
            $markdown .= "```php\n{$info['example']}\n```\n\n";
        }

        // Common filters
        if (!empty($docs['common_filters'])) {
            $markdown .= "## Common Filters\n\n";
            foreach ($docs['common_filters'] as $filter => $description) {
                $markdown .= "- `{$filter}`: {$description}\n";
            }
            $markdown .= "\n";
        }

        // Available includes
        if (!empty($docs['available_includes'])) {
            $markdown .= "## Available Includes (Sideloading)\n\n";
            foreach ($docs['available_includes'] as $include) {
                $markdown .= "- `{$include}`\n";
            }
            $markdown .= "\n";
        }

        // Usage examples
        $markdown .= "## Usage Examples\n\n";
        foreach ($docs['usage_examples'] as $example) {
            $markdown .= "### {$example['description']}\n\n";
            $markdown .= "```php\n{$example['code']}\n```\n\n";
        }

        return $markdown;
    }

    // ... (rest of your existing methods remain the same)

    /**
     * List all resources with enhanced parameter handling.
     */
    public function list(array $filters = [], array $options = [])
    {
        // Validate capabilities
        if (!$this->supportsFiltering && !empty($filters)) {
            throw new \InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support filtering"
            );
        }

        if (!$this->supportsPagination && (isset($options['page_size']) || isset($options['page_number']))) {
            throw new \InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support pagination"
            );
        }

        if (!$this->supportsSorting && (isset($options['sort']) || isset($options['sort_order']))) {
            throw new \InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support sorting"
            );
        }

        // Build parameters using existing method but with new structure
        $params = $this->createParams(
            $filters,
            $options['sort'] ?? null,
            $options['sort_order'] ?? 'asc',
            $options['page_size'] ?? 20,
            $options['page_number'] ?? 1,
            $options['include'] ?? null
        );

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get info about a resource with enhanced include handling.
     */
    public function info($id, $includes = null)
    {
        $params = ['id' => $id];

        if (!empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.info', $params);
    }

    /**
     * Create a new resource with validation.
     */
    public function create(array $data)
    {
        if (!$this->supportsCreation) {
            throw new \InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support creation"
            );
        }

        // Validate data before sending
        $data = $this->validateData($data, 'create');

        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
    }

    /**
     * Update a resource with validation.
     */
    public function update($id, array $data)
    {
        if (!$this->supportsUpdate) {
            throw new \InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support updates"
            );
        }

        $data['id'] = $id;

        // Validate data before sending
        $data = $this->validateData($data, 'update');

        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Delete a resource.
     */
    public function delete($id)
    {
        if (!$this->supportsDeletion) {
            throw new \InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support deletion"
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Batch operations support
     */
    public function batch(string $operation, array $items): array
    {
        if (!$this->supportsBatch) {
            throw new \InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support batch operations"
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.batch', [
            'operation' => $operation,
            'items' => $items
        ]);
    }

    /**
     * Get the resource capabilities for introspection
     */
    public function getCapabilities(): array
    {
        return [
            'supports_pagination' => $this->supportsPagination,
            'supports_filtering' => $this->supportsFiltering,
            'supports_sorting' => $this->supportsSorting,
            'supports_sideloading' => $this->supportsSideloading,
            'supports_creation' => $this->supportsCreation,
            'supports_update' => $this->supportsUpdate,
            'supports_deletion' => $this->supportsDeletion,
            'supports_batch' => $this->supportsBatch,
            'default_includes' => $this->defaultIncludes,
            'endpoint' => $this->getBasePath()
        ];
    }

    // ... (rest of your existing methods)

    protected function applyIncludes(array $params = [], $includes = null)
    {
        if (!empty($includes)) {
            if (is_array($includes)) {
                $params['include'] = implode(',', $includes);
            } else {
                $params['include'] = $includes;
            }
        }

        return $params;
    }

    public function createParams(array $filters = [], $sort = null, $order = 'asc', $pageSize = 20, $pageNumber = 1, $includes = null)
    {
        $params = [];

        // Apply filters
        if (!empty($filters)) {
            $params = $this->applyFilters($params, $filters);
        }

        // Apply sorting
        if ($sort) {
            $params = $this->applySorting($params, $sort, $order);
        }

        // Apply pagination
        $params = $this->applyPagination($params, $pageSize, $pageNumber);

        // Apply includes for sideloading
        if (!empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        return $params;
    }

    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Base validation - remove empty values
        return array_filter($data, function($value) {
            return $value !== '' && $value !== null && $value !== [];
        });
    }

    public function __call(string $method, array $arguments)
    {
        // Handle withX() methods for common includes
        if (str_starts_with($method, 'with') && strlen($method) > 4) {
            $includeName = $this->convertMethodToInclude($method);
            return $this->with($includeName);
        }

        // Handle withCommonRelationships()
        if ($method === 'withCommonRelationships') {
            return $this->with($this->getSuggestedIncludes());
        }

        // Handle withoutIncludes()
        if ($method === 'withoutIncludes') {
            $this->clearPendingIncludes();
            return $this;
        }

        throw new \BadMethodCallException(
            "Method {$method} does not exist on " . static::class
        );
    }

    protected function convertMethodToInclude(string $method): string
    {
        // Remove 'with' prefix
        $include = substr($method, 4);

        // Convert camelCase to snake_case
        $include = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $include));

        // Handle special cases that are commonly used
        $includeMap = [
            'customer' => 'lead.customer',
            'responsible_user' => 'responsible_user',
            'department' => 'department',
            'company' => 'company',
            'user' => 'user',
            'project' => 'project',
            'deal_phase' => 'deal_phase',
            'deal_source' => 'deal_source'
        ];

        return $includeMap[$include] ?? $include;
    }

    protected function getSuggestedIncludes(): array
    {
        return $this->defaultIncludes;
    }
}
