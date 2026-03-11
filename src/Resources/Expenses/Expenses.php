<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use McoreServices\TeamleaderSDK\Resources\Resource;

class Expenses extends Resource
{
    protected string $description = 'Manage expenses in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'term' => 'Search by document number and supplier name (case-insensitive)',
        'source_types' => 'Filter by expense source type(s): incomingInvoice, incomingCreditNote, receipt',
        'review_statuses' => 'Filter by review status(es): pending, approved, refused',
        'bookkeeping_statuses' => 'Filter by bookkeeping status(es): sent, not_sent',
        'document_date' => 'Filter by document date with operators: is_empty, between, equals, before, after',
    ];

    // Usage examples specific to expenses
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all expenses',
            'code' => '$expenses = $teamleader->expenses()->list();',
        ],
        'list_pending' => [
            'description' => 'Get pending expenses',
            'code' => '$expenses = $teamleader->expenses()->pending();',
        ],
        'list_approved' => [
            'description' => 'Get approved expenses',
            'code' => '$expenses = $teamleader->expenses()->approved();',
        ],
        'search_by_term' => [
            'description' => 'Search expenses by document number or supplier name',
            'code' => '$expenses = $teamleader->expenses()->searchByTerm("Office Supplies Inc");',
        ],
        'filter_by_source' => [
            'description' => 'Get incoming invoices only',
            'code' => '$expenses = $teamleader->expenses()->bySourceType("incomingInvoice");',
        ],
        'date_range' => [
            'description' => 'Get expenses within date range',
            'code' => '$expenses = $teamleader->expenses()->byDateRange("2024-01-01", "2024-12-31");',
        ],
        'not_sent' => [
            'description' => 'Get expenses not sent to bookkeeping',
            'code' => '$expenses = $teamleader->expenses()->notSent();',
        ],
    ];

    /**
     * Get the base path for the expenses resource
     */
    protected function getBasePath(): string
    {
        return 'expenses';
    }

    /**
     * List expenses with filtering and pagination
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination)
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

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get expenses with pending review status
     */
    public function pending(): array
    {
        return $this->list(['review_statuses' => ['pending']]);
    }

    /**
     * Get expenses with approved review status
     */
    public function approved(): array
    {
        return $this->list(['review_statuses' => ['approved']]);
    }

    /**
     * Get expenses with refused review status
     */
    public function refused(): array
    {
        return $this->list(['review_statuses' => ['refused']]);
    }

    /**
     * Get expenses by source type
     *
     * @param  array|string  $sourceTypes  Source type(s): incomingInvoice, incomingCreditNote, receipt
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function bySourceType($sourceTypes, array $additionalFilters = []): array
    {
        $filters = $additionalFilters;

        if (is_string($sourceTypes)) {
            $filters['source_types'] = [$sourceTypes];
        } elseif (is_array($sourceTypes)) {
            $filters['source_types'] = $sourceTypes;
        }

        return $this->list($filters);
    }

    /**
     * Search expenses by term (document number or supplier name)
     *
     * @param  string  $term  Search term (case-insensitive)
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function searchByTerm(string $term, array $additionalFilters = []): array
    {
        $filters = array_merge(['term' => $term], $additionalFilters);

        return $this->list($filters);
    }

    /**
     * Get expenses within a date range
     *
     * @param  string  $startDate  Start date (ISO format)
     * @param  string  $endDate  End date (ISO format)
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function byDateRange(string $startDate, string $endDate, array $additionalFilters = []): array
    {
        $filters = array_merge([
            'document_date' => [
                'operator' => 'between',
                'start' => $startDate,
                'end' => $endDate,
            ],
        ], $additionalFilters);

        return $this->list($filters);
    }

    /**
     * Get expenses sent to bookkeeping
     */
    public function sent(): array
    {
        return $this->list(['bookkeeping_statuses' => ['sent']]);
    }

    /**
     * Get expenses not sent to bookkeeping
     */
    public function notSent(): array
    {
        return $this->list(['bookkeeping_statuses' => ['not_sent']]);
    }

    /**
     * Build filters array for the API request
     */
    private function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle term filter
        if (isset($filters['term']) && ! empty($filters['term'])) {
            $apiFilters['term'] = $filters['term'];
        }

        // Handle source_types filter
        if (isset($filters['source_types'])) {
            if (is_string($filters['source_types'])) {
                $apiFilters['source_types'] = [$filters['source_types']];
            } elseif (is_array($filters['source_types'])) {
                $apiFilters['source_types'] = $filters['source_types'];
            }
        }

        // Handle review_statuses filter
        if (isset($filters['review_statuses'])) {
            if (is_string($filters['review_statuses'])) {
                $apiFilters['review_statuses'] = [$filters['review_statuses']];
            } elseif (is_array($filters['review_statuses'])) {
                $apiFilters['review_statuses'] = $filters['review_statuses'];
            }
        }

        // Handle bookkeeping_statuses filter
        if (isset($filters['bookkeeping_statuses'])) {
            if (is_string($filters['bookkeeping_statuses'])) {
                $apiFilters['bookkeeping_statuses'] = [$filters['bookkeeping_statuses']];
            } elseif (is_array($filters['bookkeeping_statuses'])) {
                $apiFilters['bookkeeping_statuses'] = $filters['bookkeeping_statuses'];
            }
        }

        // Handle document_date filter
        if (isset($filters['document_date']) && is_array($filters['document_date'])) {
            $dateFilter = $filters['document_date'];

            if (isset($dateFilter['operator'])) {
                $apiFilters['document_date'] = [
                    'operator' => $dateFilter['operator'],
                ];

                // Add value for equals, before, after operators
                if (in_array($dateFilter['operator'], ['equals', 'before', 'after']) && isset($dateFilter['value'])) {
                    $apiFilters['document_date']['value'] = $dateFilter['value'];
                }

                // Add start and end for between operator
                if ($dateFilter['operator'] === 'between') {
                    if (isset($dateFilter['start'])) {
                        $apiFilters['document_date']['start'] = $dateFilter['start'];
                    }
                    if (isset($dateFilter['end'])) {
                        $apiFilters['document_date']['end'] = $dateFilter['end'];
                    }
                }
            }
        }

        return $apiFilters;
    }
}
