<?php

namespace McoreServices\TeamleaderSDK\Resources\CRM;

use McoreServices\TeamleaderSDK\Resources\Resource;

class Tags extends Resource
{
    protected string $description = 'Manage tags in Teamleader Focus';

    // Resource capabilities - Tags appear to be read-only based on API docs
    protected bool $supportsCreation = false;  // No create endpoint shown

    protected bool $supportsUpdate = false;    // No update endpoint shown

    protected bool $supportsDeletion = false;  // No delete endpoint shown

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = false; // No filters shown in API docs

    protected bool $supportsSideloading = false;

    // Available includes (none for tags)
    protected array $availableIncludes = [];

    // Common filters - none based on API documentation
    protected array $commonFilters = [];

    // Usage examples specific to tags
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all tags',
            'code' => '$tags = $teamleader->tags()->list();',
        ],
        'list_paginated' => [
            'description' => 'Get tags with pagination',
            'code' => '$tags = $teamleader->tags()->list([], [\'page_size\' => 50, \'page_number\' => 2]);',
        ],
        'list_sorted' => [
            'description' => 'Get tags sorted alphabetically',
            'code' => '$tags = $teamleader->tags()->list([], [\'sort\' => \'tag\', \'sort_order\' => \'asc\']);',
        ],
        'search_tags' => [
            'description' => 'Search for specific tags',
            'code' => '$campaignTags = $teamleader->tags()->search(\'campaign\');',
        ],
    ];

    /**
     * Get the base path for the tags resource
     */
    protected function getBasePath(): string
    {
        return 'tags';
    }

    /**
     * List tags with enhanced parameter handling
     *
     * @param  array  $filters  Filters (not supported for tags)
     * @param  array  $options  Pagination and sorting options
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Handle pagination
        if ($this->supportsPagination) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        // Handle sorting - tags only support sorting by 'tag' field
        if ($this->supportsSorting) {
            $sortField = $options['sort'] ?? 'tag';
            $sortOrder = $options['sort_order'] ?? 'asc';

            // Validate sort field (only 'tag' is supported)
            if ($sortField !== 'tag') {
                $sortField = 'tag';
            }

            // Validate sort order (only 'asc' is supported according to API docs)
            if ($sortOrder !== 'asc') {
                $sortOrder = 'asc';
            }

            $params['sort'] = [
                [
                    'field' => $sortField,
                    'order' => $sortOrder,
                ],
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get all tags without pagination
     *
     * @return array All tags combined from all pages
     */
    public function all(): array
    {
        $allTags = [];
        $pageNumber = 1;
        $pageSize = 100; // Use larger page size for efficiency

        do {
            $response = $this->list([], [
                'page_size' => $pageSize,
                'page_number' => $pageNumber,
            ]);

            if (isset($response['data']) && is_array($response['data'])) {
                $allTags = array_merge($allTags, $response['data']);

                // Check if there are more pages
                $hasMorePages = count($response['data']) === $pageSize;
                $pageNumber++;
            } else {
                break;
            }

        } while ($hasMorePages && $pageNumber <= 50); // Safety limit

        return [
            'data' => $allTags,
            'total_count' => count($allTags),
        ];
    }

    /**
     * Search tags by name (client-side filtering)
     * Note: The API doesn't support server-side filtering, so we fetch all and filter
     *
     * @param  string  $query  Search query
     * @param  bool  $exactMatch  Whether to match exactly or use partial matching
     */
    public function search(string $query, bool $exactMatch = false): array
    {
        $allTags = $this->all();

        if (empty($query)) {
            return $allTags;
        }

        $filteredTags = array_filter($allTags['data'], function ($tag) use ($query, $exactMatch) {
            if (! isset($tag['tag'])) {
                return false;
            }

            if ($exactMatch) {
                return strcasecmp($tag['tag'], $query) === 0;
            } else {
                return stripos($tag['tag'], $query) !== false;
            }
        });

        return [
            'data' => array_values($filteredTags), // Reset array keys
            'total_count' => count($filteredTags),
            'query' => $query,
            'exact_match' => $exactMatch,
        ];
    }

    /**
     * Get tags containing specific text
     *
     * @param  string  $text  Text to search for
     */
    public function containing(string $text): array
    {
        return $this->search($text, false);
    }

    /**
     * Get tags starting with specific text
     *
     * @param  string  $prefix  Prefix to search for
     */
    public function startingWith(string $prefix): array
    {
        $allTags = $this->all();

        $filteredTags = array_filter($allTags['data'], function ($tag) use ($prefix) {
            if (! isset($tag['tag'])) {
                return false;
            }

            return stripos($tag['tag'], $prefix) === 0;
        });

        return [
            'data' => array_values($filteredTags),
            'total_count' => count($filteredTags),
            'prefix' => $prefix,
        ];
    }

    /**
     * Get paginated results with better metadata
     *
     * @param  int  $pageSize  Items per page
     * @param  int  $pageNumber  Page number (1-based)
     */
    public function paginate(int $pageSize = 20, int $pageNumber = 1): array
    {
        $response = $this->list([], [
            'page_size' => $pageSize,
            'page_number' => $pageNumber,
        ]);

        // Add enhanced pagination metadata
        if (isset($response['data'])) {
            $response['pagination'] = [
                'current_page' => $pageNumber,
                'per_page' => $pageSize,
                'total_on_page' => count($response['data']),
                'has_more_pages' => count($response['data']) === $pageSize,
            ];
        }

        return $response;
    }

    /**
     * Get tag statistics
     */
    public function getStatistics(): array
    {
        $allTags = $this->all();
        $tags = $allTags['data'];

        $stats = [
            'total_count' => count($tags),
            'average_length' => 0,
            'shortest_tag' => '',
            'longest_tag' => '',
            'most_common_prefixes' => [],
        ];

        if (empty($tags)) {
            return $stats;
        }

        $lengths = [];
        $prefixes = [];

        foreach ($tags as $tag) {
            if (isset($tag['tag'])) {
                $tagName = $tag['tag'];
                $length = strlen($tagName);
                $lengths[] = $length;

                // Track shortest and longest
                if (empty($stats['shortest_tag']) || $length < strlen($stats['shortest_tag'])) {
                    $stats['shortest_tag'] = $tagName;
                }

                if (empty($stats['longest_tag']) || $length > strlen($stats['longest_tag'])) {
                    $stats['longest_tag'] = $tagName;
                }

                // Track prefixes (first 3 characters)
                if ($length >= 3) {
                    $prefix = strtolower(substr($tagName, 0, 3));
                    $prefixes[$prefix] = ($prefixes[$prefix] ?? 0) + 1;
                }
            }
        }

        $stats['average_length'] = ! empty($lengths) ? round(array_sum($lengths) / count($lengths), 2) : 0;

        // Sort prefixes by frequency
        arsort($prefixes);
        $stats['most_common_prefixes'] = array_slice($prefixes, 0, 5, true);

        return $stats;
    }

    /**
     * Get available sort options
     */
    public function getAvailableSortFields(): array
    {
        return [
            'tag' => 'Sort by tag name (only option available)',
        ];
    }

    /**
     * Override info method as individual tag info is not supported
     *
     * @param  string  $id
     * @param  mixed  $includes
     *
     * @throws \InvalidArgumentException
     */
    public function info($id, $includes = null): array
    {
        throw new \InvalidArgumentException(
            'Tags do not support individual info requests. Use list() or search() to get tag information.'
        );
    }

    /**
     * Override getSuggestedIncludes as tags don't have includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Tags don't have sideloadable relationships
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'data' => [
                'description' => 'Array of tags',
                'type' => 'array',
                'items' => [
                    'tag' => 'Tag name/label (e.g., "campaign", "priority", "client")',
                ],
            ],
            'pagination' => [
                'description' => 'Pagination metadata (added by SDK)',
                'type' => 'object',
            ],
        ];
    }

    /**
     * Override validation since tags don't support create/update
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Tags are read-only based on API docs, no validation needed
        return $data;
    }
}
