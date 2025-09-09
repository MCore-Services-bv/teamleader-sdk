<?php

namespace McoreServices\TeamleaderSDK\Transformers;

use Carbon\Carbon;

class ResponseTransformer
{
    /**
     * Transform API response to standardized format
     */
    public static function transform(array $response, ?string $transformer = null): array
    {
        // Handle paginated responses
        if (isset($response['data']) && isset($response['meta'])) {
            return [
                'data' => static::transformData($response['data'], $transformer),
                'pagination' => static::transformPagination($response['meta']),
                'included' => static::transformIncluded($response['included'] ?? []),
                'meta' => $response['meta'] ?? []
            ];
        }

        // Handle single resource responses
        if (isset($response['data'])) {
            return [
                'data' => static::transformData($response['data'], $transformer),
                'included' => static::transformIncluded($response['included'] ?? [])
            ];
        }

        // Handle error responses - pass through unchanged
        if (isset($response['error'])) {
            return $response;
        }

        return $response;
    }

    /**
     * Transform pagination meta data
     */
    protected static function transformPagination(array $meta): array
    {
        $page = $meta['page'] ?? [];
        $matches = $meta['matches'] ?? 0;
        $pageSize = $page['size'] ?? 20;
        $pageNumber = $page['number'] ?? 1;

        return [
            'current_page' => $pageNumber,
            'per_page' => $pageSize,
            'total' => $matches,
            'has_more' => $matches > ($pageNumber * $pageSize),
            'total_pages' => ceil($matches / max($pageSize, 1)),
            'from' => (($pageNumber - 1) * $pageSize) + 1,
            'to' => min($pageNumber * $pageSize, $matches),
            'last_page' => ceil($matches / max($pageSize, 1))
        ];
    }

    /**
     * Transform included resources
     */
    protected static function transformIncluded(array $included): array
    {
        $transformed = [];

        foreach ($included as $type => $resources) {
            if (is_array($resources)) {
                $transformed[$type] = array_map(function ($resource) {
                    return static::transformSingleResource($resource);
                }, $resources);
            }
        }

        return $transformed;
    }

    /**
     * Transform data array (single or multiple resources)
     */
    protected static function transformData($data, ?string $transformer): mixed
    {
        if (is_array($data) && isset($data[0])) {
            // Array of resources
            return array_map([static::class, 'transformSingleResource'], $data);
        } elseif (is_array($data)) {
            // Single resource
            return static::transformSingleResource($data);
        }

        return $data;
    }

    /**
     * Transform a single resource
     */
    protected static function transformSingleResource(array $resource): array
    {
        // Add common transformations for dates
        $dateFields = [
            'created_at', 'updated_at', 'deleted_at',
            'due_date', 'invoice_date', 'starts_at', 'ends_at',
            'estimated_closing_date', 'closed_at', 'completed_at',
            'started_at', 'ended_at', 'published_at'
        ];

        foreach ($dateFields as $field) {
            if (isset($resource[$field]) && !empty($resource[$field])) {
                try {
                    $carbon = Carbon::parse($resource[$field]);
                    $resource[$field . '_human'] = $carbon->diffForHumans();
                    $resource[$field . '_formatted'] = $carbon->format('Y-m-d H:i:s');

                    // Add date-only version for date fields
                    if (in_array($field, ['due_date', 'invoice_date', 'starts_at', 'ends_at'])) {
                        $resource[$field . '_date'] = $carbon->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    // If date parsing fails, leave original value
                    continue;
                }
            }
        }

        // Add currency formatting for money fields
        $moneyFields = [
            'total', 'subtotal', 'total_discounted', 'total_tax',
            'amount', 'value', 'estimated_value', 'budget',
            'unit_price', 'discount_value', 'price', 'cost'
        ];

        foreach ($moneyFields as $field) {
            if (isset($resource[$field]) && is_numeric($resource[$field])) {
                $currency = $resource['currency'] ?? 'EUR';
                $resource[$field . '_formatted'] = static::formatMoney($resource[$field], $currency);
            }
        }

        // Add boolean status helpers
        if (isset($resource['status'])) {
            $resource['is_active'] = in_array($resource['status'], ['active', 'open', 'in_progress', 'ongoing']);
            $resource['is_completed'] = in_array($resource['status'], ['completed', 'won', 'closed', 'paid', 'done']);
            $resource['is_cancelled'] = in_array($resource['status'], ['cancelled', 'lost', 'deleted', 'rejected']);
            $resource['is_draft'] = $resource['status'] === 'draft';
            $resource['is_pending'] = in_array($resource['status'], ['pending', 'waiting', 'sent']);
        }

        // Add ID helpers
        if (isset($resource['id'])) {
            $resource['uuid'] = $resource['id']; // Alias for clarity
        }

        // Add type helpers
        if (isset($resource['type'])) {
            $resource['resource_type'] = $resource['type'];
        }

        // Add relationship existence helpers
        if (isset($resource['responsible_user_id'])) {
            $resource['has_responsible_user'] = !empty($resource['responsible_user_id']);
        }

        if (isset($resource['company_id'])) {
            $resource['has_company'] = !empty($resource['company_id']);
        }

        if (isset($resource['customer'])) {
            $resource['has_customer'] = !empty($resource['customer']);
            if (isset($resource['customer']['type'])) {
                $resource['customer_type'] = $resource['customer']['type'];
            }
        }

        return $resource;
    }

    /**
     * Format money values
     */
    protected static function formatMoney(float $amount, string $currency = 'EUR'): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CHF' => 'CHF',
            'SEK' => 'kr',
            'DKK' => 'kr',
            'NOK' => 'kr',
        ];

        $symbol = $symbols[$currency] ?? $currency;

        // Format according to European standards for EUR
        if ($currency === 'EUR') {
            return $symbol . number_format($amount, 2, ',', '.');
        }

        // Default format for other currencies
        return $symbol . number_format($amount, 2);
    }

    /**
     * Create a collection-style response
     */
    public static function collection(array $items, array $meta = []): array
    {
        return [
            'data' => array_map([static::class, 'transformSingleResource'], $items),
            'meta' => array_merge([
                'count' => count($items),
                'transformed_at' => now()->toISOString()
            ], $meta)
        ];
    }

    /**
     * Create a single resource response
     */
    public static function item(array $item, array $included = []): array
    {
        return [
            'data' => static::transformSingleResource($item),
            'included' => static::transformIncluded($included),
            'meta' => [
                'transformed_at' => now()->toISOString()
            ]
        ];
    }

    /**
     * Transform error responses to consistent format
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): array
    {
        return [
            'error' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'timestamp' => now()->toISOString()
            ]
        ];
    }

    /**
     * Add relationship helpers to response
     */
    public static function withRelationships(array $response): array
    {
        if (!isset($response['data']) || !isset($response['included'])) {
            return $response;
        }

        // Add helper method to find related resources
        $response['findRelated'] = function(string $type, string $id) use ($response) {
            $included = $response['included'][$type] ?? [];

            foreach ($included as $resource) {
                if (isset($resource['id']) && $resource['id'] === $id) {
                    return $resource;
                }
            }

            return null;
        };

        return $response;
    }

    /**
     * Extract summary information from response
     */
    public static function extractSummary(array $response): array
    {
        $summary = [];

        // Count items
        if (isset($response['data'])) {
            $summary['total_items'] = is_array($response['data']) ? count($response['data']) : 1;
        }

        // Extract pagination info
        if (isset($response['pagination'])) {
            $summary['pagination'] = [
                'current_page' => $response['pagination']['current_page'],
                'total' => $response['pagination']['total'],
                'has_more' => $response['pagination']['has_more']
            ];
        }

        // Count included resources
        if (isset($response['included'])) {
            $summary['included_resources'] = [];
            foreach ($response['included'] as $type => $resources) {
                $summary['included_resources'][$type] = count($resources);
            }
        }

        return $summary;
    }

    /**
     * Merge multiple API responses into one
     */
    public static function merge(array $responses): array
    {
        $merged = [
            'data' => [],
            'included' => [],
            'meta' => [
                'merged_from' => count($responses),
                'merged_at' => now()->toISOString()
            ]
        ];

        foreach ($responses as $response) {
            // Merge data
            if (isset($response['data'])) {
                if (is_array($response['data']) && isset($response['data'][0])) {
                    // Array of items
                    $merged['data'] = array_merge($merged['data'], $response['data']);
                } else {
                    // Single item
                    $merged['data'][] = $response['data'];
                }
            }

            // Merge included resources
            if (isset($response['included'])) {
                foreach ($response['included'] as $type => $resources) {
                    if (!isset($merged['included'][$type])) {
                        $merged['included'][$type] = [];
                    }
                    $merged['included'][$type] = array_merge($merged['included'][$type], $resources);
                }
            }
        }

        // Remove duplicate included resources by ID
        foreach ($merged['included'] as $type => $resources) {
            $unique = [];
            $seen = [];

            foreach ($resources as $resource) {
                $id = $resource['id'] ?? uniqid();
                if (!in_array($id, $seen)) {
                    $unique[] = $resource;
                    $seen[] = $id;
                }
            }

            $merged['included'][$type] = $unique;
        }

        return $merged;
    }
}
