<?php

namespace McoreServices\TeamleaderSDK\Resources;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Traits\FilterTrait;

/**
 * Base resource class for all Teamleader API resources
 *
 * Provides common functionality for API resources including:
 * - CRUD operations
 * - Filtering and sorting
 * - Pagination
 * - Sideloading (including related resources)
 * - Resource introspection and documentation
 */
abstract class Resource
{
    use FilterTrait;

    /**
     * @var TeamleaderSDK The main SDK instance for making API requests
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
     * Resource constructor
     *
     * @param  TeamleaderSDK  $api  The SDK instance
     */
    public function __construct(TeamleaderSDK $api)
    {
        $this->api = $api;
    }

    /**
     * Generate interactive markdown documentation
     *
     * @return string Markdown formatted documentation
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
            $markdown .= '- **'.ucwords(str_replace('_', ' ', $capability))."**: {$status}\n";
        }
        $markdown .= "\n";

        // Common filters
        if (! empty($docs['common_filters'])) {
            $markdown .= "## Common Filters\n\n";
            foreach ($docs['common_filters'] as $filter => $description) {
                $markdown .= "- `{$filter}`: {$description}\n";
            }
            $markdown .= "\n";
        }

        // Usage examples
        if (! empty($docs['usage_examples'])) {
            $markdown .= "## Usage Examples\n\n";
            foreach ($docs['usage_examples'] as $example) {
                $markdown .= "**{$example['description']}**\n\n";
                $markdown .= "```php\n{$example['code']}\n```\n\n";
            }
        }

        return $markdown;
    }

    /**
     * Generate comprehensive API documentation for this resource
     *
     * Returns detailed documentation including:
     * - Resource description
     * - Available methods
     * - Filters and sorting options
     * - Sideloading capabilities
     * - Usage examples
     * - Rate limit information
     * - Response formats
     *
     * Can be used to generate dynamic documentation or help text.
     *
     * @return array Complete documentation array
     */
    public function getDocumentation(): array
    {
        return [
            'resource' => class_basename(static::class),
            'description' => $this->description,
            'endpoint' => $this->getBasePath(),
            'capabilities' => $this->getCapabilities(),
            'common_filters' => $this->getCommonFilters(),
            'available_sort_fields' => $this->getAvailableSortFields(),
            'usage_examples' => $this->getUsageExamples(),
            'rate_limit_costs' => $this->getRateLimitCost(),
            'response_formats' => $this->getResponseFormat(),
        ];
    }

    /**
     * Get the base API path for this resource
     *
     * Must be implemented by child classes to define the endpoint path.
     * Example: 'companies', 'deals', 'invoices', etc.
     *
     * @return string The base endpoint path (without leading slash)
     */
    abstract protected function getBasePath(): string;

    /**
     * Get comprehensive resource capabilities information
     *
     * Returns detailed information about what operations this resource supports,
     * what relationships can be included, available filters, and default configurations.
     *
     * Useful for runtime introspection and building dynamic interfaces.
     *
     * @return array Comprehensive capabilities information
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
            'available_includes' => $this->getAvailableIncludes(),
            'endpoint' => $this->getBasePath(),
        ];
    }

    /**
     * Get available relationship includes for sideloading
     *
     * Returns an array of relationship names that can be included
     * with requests to load related resources in a single API call.
     *
     * Example: ['addresses', 'responsible_user', 'tags']
     *
     * @return array List of available include names
     */
    protected function getAvailableIncludes(): array
    {
        return $this->availableIncludes;
    }

    /**
     * Get common filter options for this resource
     *
     * Returns an array describing the most commonly used filters,
     * their purpose, and expected format.
     *
     * Example: ['status' => 'Filter by status (active/inactive)', 'updated_since' => 'ISO 8601 date']
     *
     * @return array Associative array of filter names and descriptions
     */
    protected function getCommonFilters(): array
    {
        return $this->commonFilters;
    }

    /**
     * Get available sort fields for this resource
     *
     * Returns an array of field names that can be used for sorting,
     * along with descriptions of what each field represents.
     *
     * Example: ['name' => 'Sort by company name', 'created_at' => 'Sort by creation date']
     *
     * @return array Associative array of field names and descriptions
     */
    protected function getAvailableSortFields(): array
    {
        return $this->availableSortFields ?? [];
    }

    /**
     * Get usage examples specific to this resource
     *
     * Returns code examples demonstrating common use cases and patterns
     * for working with this resource.
     *
     * Each example includes a description and working code snippet.
     *
     * @return array Array of examples with 'description' and 'code' keys
     */
    protected function getUsageExamples(): array
    {
        $resourceName = strtolower(class_basename(static::class));
        $methodName = rtrim($resourceName, 's'); // Crude singularization

        return [
            'list' => [
                'description' => 'Get all resources with pagination',
                'code' => "\$results = \$teamleader->{$methodName}s()->list([], ['page_size' => 50]);",
            ],
            'info' => [
                'description' => 'Get a single resource',
                'code' => "\$resource = \$teamleader->{$methodName}s()->info('uuid-here');",
            ],
        ];
    }

    /**
     * Get rate limit cost information for each operation
     *
     * Returns the number of rate limit units consumed by each operation.
     * Used for rate limit management and optimization.
     *
     * Most operations cost 1 unit. Batch operations may cost more.
     *
     * @return array Associative array of operations and their costs
     */
    protected function getRateLimitCost(): array
    {
        return [
            'list' => 1,
            'info' => 1,
            'create' => 1,
            'update' => 1,
            'delete' => 1,
            'batch' => 'varies based on items',
        ];
    }

    /**
     * Get response format information for each operation
     *
     * Documents the structure of API responses for different operations.
     * Useful for understanding what data to expect in responses.
     *
     * @return array Nested array describing response formats
     */
    protected function getResponseFormat(): array
    {
        return [
            'list' => [
                'data' => 'Array of resource objects',
                'pagination' => 'Pagination metadata (if applicable)',
                'included' => 'Sideloaded related resources (if requested)',
                'meta' => 'Additional metadata',
            ],
            'info' => [
                'data' => 'Single resource object',
                'included' => 'Sideloaded related resources (if requested)',
            ],
            'create' => [
                'data' => 'Created resource object with generated ID',
            ],
            'update' => [
                'data' => 'Updated resource object',
            ],
            'delete' => [
                'success' => 'Boolean indicating success',
                'message' => 'Confirmation message (if applicable)',
            ],
        ];
    }

    /**
     * Validate a UUID format
     *
     * Ensures the provided ID matches UUID v4 format.
     * Throws InvalidArgumentException if invalid.
     *
     * @param  string  $id  The UUID to validate
     *
     * @throws InvalidArgumentException If the UUID format is invalid
     */
    protected function validateId(string $id): void
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            throw new InvalidArgumentException("Invalid UUID format: {$id}");
        }
    }

    /**
     * Build query parameters for API requests
     *
     * Constructs a properly formatted array of query parameters including:
     * - Includes (sideloading)
     * - Filters
     * - Sorting
     * - Pagination
     *
     * Handles validation and formatting of all parameter types.
     *
     * @param  array  $includes  Include strings for sideloading
     * @param  array  $filters  Filter criteria
     * @param  string|null  $sort  Field to sort by
     * @param  string  $sortOrder  Sort direction ('asc' or 'desc')
     * @param  int  $pageSize  Number of results per page
     * @param  int  $pageNumber  Page number to retrieve
     * @param  string|null  $include  Alternative include parameter
     * @return array Formatted query parameters ready for API request
     */
    protected function buildQueryParams(
        array $includes = [],
        array $filters = [],
        ?string $sort = null,
        string $sortOrder = 'desc',
        int $pageSize = 20,
        int $pageNumber = 1,
        ?string $include = null
    ): array {
        $params = [];

        // Handle includes (sideloading)
        if (! empty($includes) || ! empty($include)) {
            $includeString = $include ?? implode(',', $includes);
            $params['include'] = $includeString;
        }

        // Handle filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Handle sorting
        if ($sort !== null) {
            $params['sort'] = $this->buildSort($sort, $sortOrder);
        }

        // Handle pagination
        if ($this->supportsPagination) {
            $params['page'] = [
                'size' => $pageSize,
                'number' => $pageNumber,
            ];
        }

        return $params;
    }

    /**
     * Build sort parameter from field and order
     *
     * Formats sort parameters according to Teamleader API requirements.
     * Supports multiple sort fields with different directions.
     *
     * @param  string|array  $sort  Field name(s) to sort by
     * @param  string  $order  Sort direction: 'asc' or 'desc'
     * @return array|string Formatted sort parameter
     */
    protected function buildSort($sort, string $order = 'desc')
    {
        if (is_array($sort)) {
            return array_map(function ($field) use ($order) {
                return [
                    'field' => $field,
                    'order' => $order,
                ];
            }, $sort);
        }

        return [
            'field' => $sort,
            'order' => $order,
        ];
    }

    /**
     * Invalidate cache after updates
     *
     * @param  string  $id  Resource ID that was updated
     */
    protected function invalidateCache(string $id): void
    {
        $this->clearCache($id);

        // Also clear list caches as they might include this resource
        if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
            Cache::tags(["{$this->getBasePath()}_list"])->flush();
        }
    }

    /**
     * Clear cache for a specific resource
     *
     * @param  string|null  $id  Resource ID to clear cache for (null = all)
     */
    protected function clearCache(?string $id = null): void
    {
        if (! config('teamleader.caching.enabled')) {
            return; // Caching not enabled, nothing to clear
        }

        if ($id) {
            // Clear cache for specific resource
            $cacheKey = $this->getCacheKey($id);
            Cache::forget($cacheKey);

            $this->api->getLogger()->debug('Cache cleared for resource', [
                'resource' => $this->getBasePath(),
                'id' => $id,
                'cache_key' => $cacheKey,
            ]);
        } else {
            // Clear all cache for this resource type using tags
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags([$this->getBasePath()])->flush();

                $this->api->getLogger()->debug('All cache cleared for resource', [
                    'resource' => $this->getBasePath(),
                ]);
            } else {
                // Fallback for drivers that don't support tags
                $this->api->getLogger()->warning('Cache tags not supported by cache driver', [
                    'resource' => $this->getBasePath(),
                    'cache_driver' => config('cache.default'),
                ]);
            }
        }
    }

    /**
     * Generate cache key for a resource
     *
     * @param  string  $id  Resource ID
     * @param  array  $params  Additional params to include in key
     */
    protected function getCacheKey(string $id, array $params = []): string
    {
        $key = "teamleader:{$this->getBasePath()}:{$id}";

        if (! empty($params)) {
            $key .= ':'.md5(serialize($params));
        }

        return $key;
    }
}
