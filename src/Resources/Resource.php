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

    /**
     * Whether this resource asks the API for pagination metadata.
     *
     * Teamleader only returns a `meta` block when `includes=pagination` is sent
     * with the request. Resources that send it should set this to true so the
     * generated documentation describes the response they actually produce.
     *
     * When false, callers get no total count and no page count, so the end of a
     * list can only be inferred from a page shorter than the requested page size.
     */
    protected bool $requestsPaginationMeta = false;

    // Documentation properties
    protected array $commonFilters = [];

    protected array $availableIncludes = [];

    protected array $availableSortFields = [];

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

        // Sort fields
        if (! empty($docs['available_sort_fields'])) {
            $markdown .= "## Sort Fields\n\n";
            foreach ($docs['available_sort_fields'] as $field => $description) {
                $markdown .= is_string($field)
                    ? "- `{$field}`: {$description}\n"
                    : "- `{$description}`\n";
            }
            $markdown .= "\n";
        }

        // Pagination
        $markdown .= "## Pagination\n\n";
        $markdown .= $docs['pagination']['supported'] ? "Supported.\n\n" : "Not supported.\n\n";
        $markdown .= "> **Note:** {$docs['pagination']['note']}\n\n";

        // Response formats
        if (! empty($docs['response_formats'])) {
            $markdown .= "## Response Formats\n\n";
            foreach ($docs['response_formats'] as $operation => $keys) {
                $markdown .= "**`{$operation}`**\n\n";
                foreach ($keys as $key => $description) {
                    $markdown .= "- `{$key}`: {$description}\n";
                }
                $markdown .= "\n";
            }
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
            'pagination' => $this->getPaginationBehaviour(),
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
        return $this->availableSortFields;
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
     * Describes the array the SDK hands back, which is not quite the API's own
     * response: the SDK adds a `headers` key to every successful response, and
     * converts an HTTP 204 into a `success` / `status_code` / `message` array.
     *
     * Derived from this resource's capability flags rather than asserted for all
     * resources, because the keys genuinely differ. Before v2.1.2 this method
     * claimed every `list` response carried `pagination`, `included` and `meta`;
     * none of the three is returned by default, and `included` does not exist in
     * the API at all — sideloaded data is embedded inside each record in `data`,
     * not in a separate top-level block.
     *
     * @return array Nested array describing response formats
     */
    protected function getResponseFormat(): array
    {
        $headers = 'Response headers, including X-RateLimit-Limit, '
            .'X-RateLimit-Remaining and X-RateLimit-Reset. Added by the SDK.';

        $listFormat = [
            'data' => 'Array of resource objects',
            'headers' => $headers,
        ];

        if ($this->requestsPaginationMeta) {
            $listFormat['meta'] = 'Pagination metadata (page.size, page.number, matches). '
                .'Only returned because this resource sends includes=pagination.';
        }

        return [
            'list' => $listFormat,
            'info' => [
                'data' => 'Single resource object',
                'headers' => $headers,
            ],
            'create' => [
                'data' => 'Created resource object with generated ID',
                'headers' => $headers,
            ],
            'update' => [
                'data' => 'Updated resource object, when the endpoint returns a body',
                'headers' => $headers,
                'success' => 'Present instead of data when the API answers 204 No Content',
                'status_code' => 'Present alongside success on a 204 response',
                'message' => 'Present alongside success on a 204 response',
            ],
            'delete' => [
                'success' => 'Boolean indicating success (the API answers 204)',
                'status_code' => 'HTTP status code',
                'message' => 'Confirmation message',
                'headers' => $headers,
            ],
        ];
    }

    /**
     * Describe how this resource paginates
     *
     * Surfaced through getDocumentation() so the pagination behaviour and the
     * supportsPagination flag cannot drift apart.
     *
     * @return array Pagination support, metadata availability, and how to detect
     *               the end of a list
     */
    protected function getPaginationBehaviour(): array
    {
        if (! $this->supportsPagination) {
            return [
                'supported' => false,
                'returns_metadata' => false,
                'note' => 'This endpoint is not paginated. It returns every record in one '
                    .'response, and page_size / page_number are not accepted.',
            ];
        }

        return [
            'supported' => true,
            'returns_metadata' => $this->requestsPaginationMeta,
            'default_page_size' => 20,
            'note' => $this->requestsPaginationMeta
                ? 'A meta block carrying the total match count is returned, because this '
                .'resource sends includes=pagination.'
                : 'No total count and no page count are returned. The only end-of-list '
                .'signal is a page shorter than the requested page size, so a full '
                .'final page costs one extra empty request and the number of requests '
                .'a complete enumeration needs cannot be known in advance.',
        ];
    }

    /**
     * Validate data before a create or update request
     *
     * A pass-through hook. Resources override it to enforce their own rules and
     * may return a modified payload — several call `parent::validateData()` at
     * the end of their override, which is why this base implementation has to
     * exist even though it does nothing.
     *
     * Note that most resources do their validation in a bespoke method instead
     * (validateDealData(), validateCreateData(), validateTimeTrackingData(), and
     * so on) and never call this. It is a hook, not a guaranteed pipeline step:
     * nothing in this base class invokes it, so overriding it does not by itself
     * make validation run.
     *
     * @param  array  $data  The payload about to be sent
     * @param  string  $operation  'create' or 'update'
     * @return array The payload to send
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        return $data;
    }

    /**
     * Reject list() arguments this endpoint cannot use
     *
     * Every resource inherits the same list(array $filters, array $options)
     * signature, but a number of Teamleader endpoints accept no filter, sort or
     * page parameters at all. Silently discarding what the caller asked for is
     * the worst option available: the request succeeds, the full unfiltered list
     * comes back, and the mistake surfaces much later in whatever consumed it.
     *
     * A pager that treats a short page as "end of list" reads a complete list as
     * complete by coincidence, and starts looping forever once the account grows
     * past the requested page size.
     *
     * Resources wrapping such an endpoint call this at the top of list(). What
     * gets rejected is driven by the capability flags, so a resource that
     * supports pagination but not filtering — Tags, for example — only rejects
     * filters.
     *
     * @param  array  $filters  The filters the caller passed
     * @param  array  $options  The options the caller passed
     *
     * @throws InvalidArgumentException When an argument cannot be honoured
     */
    protected function rejectUnsupportedListArguments(array $filters, array $options): void
    {
        $endpoint = $this->getBasePath().'.list';

        if ($filters !== [] && ! $this->supportsFiltering) {
            throw new InvalidArgumentException(
                "{$endpoint} does not support filtering. Passed: "
                .implode(', ', array_keys($filters))
                .'. Call list() without filters; the endpoint returns every record. '
                .'See getCapabilities() for what this resource supports.'
            );
        }

        if (! $this->supportsSorting) {
            $sortKeys = array_intersect(['sort', 'sort_order', 'sort_field'], array_keys($options));

            if ($sortKeys !== []) {
                throw new InvalidArgumentException(
                    "{$endpoint} does not support sorting. Passed: "
                    .implode(', ', $sortKeys).'. Records come back in the order the API chooses.'
                );
            }
        }

        if (! $this->supportsPagination) {
            $pageKeys = array_intersect(['page', 'page_size', 'page_number'], array_keys($options));

            if ($pageKeys !== []) {
                throw new InvalidArgumentException(
                    "{$endpoint} does not support pagination. Passed: "
                    .implode(', ', $pageKeys).'. The endpoint returns every record in a '
                    .'single response, so there are no pages to request.'
                );
            }
        }
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
     * NOTE: buildQueryParams, applyFilters, applySorting and applyPagination are
     * provided by FilterTrait. The old implementations here called buildFilters()
     * and were removed to prevent conflicts with the trait's applyFilters().
     *
     * There is no buildSort() on this class or on FilterTrait — the trait's
     * equivalent is applySorting(). Resources that call $this->buildSort() must
     * define it themselves; fifteen do. Deals called it without defining it,
     * which was a fatal error on any sorted list() call until v2.1.2.
     */

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
