<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use InvalidArgumentException;
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

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Valid payment statuses returned by list endpoint
    protected array $validPaymentStatuses = [
        'paid',
        'unpaid',
    ];

    // Valid sort fields for the list endpoint
    protected array $validSortFields = [
        'document_date',
        'due_date',
        'supplier_name',
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'term' => 'Search by document number and supplier name (case-insensitive)',
        'source_types' => 'Filter by expense source type(s): incomingInvoice, incomingCreditNote, receipt',
        'review_statuses' => 'Filter by review status(es): pending, approved, refused',
        'bookkeeping_statuses' => 'Filter by bookkeeping status(es): sent, not_sent',
        'payment_statuses' => 'Filter by payment status(es): paid, unpaid',
        'department_ids' => 'Filter by one or more department UUIDs',
        'supplier' => 'Filter by a specific supplier (object with type and id)',
        'document_date' => 'Filter by document date with operators: is_empty, between, equals, before, after',
        'paid_at' => 'Filter by payment date with operators: is_empty, between, equals, before, after',
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
        'list_unpaid' => [
            'description' => 'Get unpaid expenses',
            'code' => '$expenses = $teamleader->expenses()->unpaid();',
        ],
        'search_by_term' => [
            'description' => 'Search expenses by document number or supplier name',
            'code' => '$expenses = $teamleader->expenses()->searchByTerm("Office Supplies Inc");',
        ],
        'filter_by_source' => [
            'description' => 'Get incoming invoices only',
            'code' => '$expenses = $teamleader->expenses()->bySourceType("incomingInvoice");',
        ],
        'filter_by_supplier' => [
            'description' => 'Get expenses from a specific supplier',
            'code' => '$expenses = $teamleader->expenses()->bySupplier("company", "company-uuid");',
        ],
        'filter_by_department' => [
            'description' => 'Get expenses for a specific department',
            'code' => '$expenses = $teamleader->expenses()->byDepartment("department-uuid");',
        ],
        'date_range' => [
            'description' => 'Get expenses within document date range',
            'code' => '$expenses = $teamleader->expenses()->byDateRange("2024-01-01", "2024-12-31");',
        ],
        'paid_at_range' => [
            'description' => 'Get expenses paid within a date range',
            'code' => '$expenses = $teamleader->expenses()->byPaidAtRange("2024-01-01", "2024-12-31");',
        ],
        'not_sent' => [
            'description' => 'Get expenses not sent to bookkeeping',
            'code' => '$expenses = $teamleader->expenses()->notSent();',
        ],
        'sort_by_date' => [
            'description' => 'Get expenses sorted by document date descending',
            'code' => '$expenses = $teamleader->expenses()->list([], ["sort" => [["field" => "document_date", "order" => "desc"]]]);',
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
     * List expenses with filtering, sorting, and pagination
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination, sort)
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
        if (isset($options['sort']) && is_array($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
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
     * Get expenses with paid payment status
     */
    public function paid(): array
    {
        return $this->list(['payment_statuses' => ['paid']]);
    }

    /**
     * Get expenses with unpaid payment status
     */
    public function unpaid(): array
    {
        return $this->list(['payment_statuses' => ['unpaid']]);
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
     * Get expenses for a specific supplier
     *
     * @param  string  $type  Supplier type: company or contact
     * @param  string  $id  Supplier UUID
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function bySupplier(string $type, string $id, array $additionalFilters = []): array
    {
        if (! in_array($type, ['company', 'contact'])) {
            throw new InvalidArgumentException(
                "Invalid supplier type '{$type}'. Must be one of: company, contact"
            );
        }

        $filters = array_merge([
            'supplier' => [
                'type' => $type,
                'id' => $id,
            ],
        ], $additionalFilters);

        return $this->list($filters);
    }

    /**
     * Get expenses for one or more departments
     *
     * @param  array|string  $departmentIds  Department UUID or array of UUIDs
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function byDepartment($departmentIds, array $additionalFilters = []): array
    {
        $filters = array_merge([
            'department_ids' => is_string($departmentIds) ? [$departmentIds] : $departmentIds,
        ], $additionalFilters);

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
     * Get expenses within a document date range
     *
     * @param  string  $startDate  Start date (ISO format: YYYY-MM-DD)
     * @param  string  $endDate  End date (ISO format: YYYY-MM-DD)
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
     * Get expenses paid within a date range
     *
     * @param  string  $startDate  Start date (ISO format: YYYY-MM-DD)
     * @param  string  $endDate  End date (ISO format: YYYY-MM-DD)
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function byPaidAtRange(string $startDate, string $endDate, array $additionalFilters = []): array
    {
        $filters = array_merge([
            'paid_at' => [
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
     * Get valid payment statuses for expenses
     */
    public function getValidPaymentStatuses(): array
    {
        return $this->validPaymentStatuses;
    }

    /**
     * Get valid sort fields for expenses
     */
    public function getValidSortFields(): array
    {
        return $this->validSortFields;
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
            $apiFilters['source_types'] = is_string($filters['source_types'])
                ? [$filters['source_types']]
                : $filters['source_types'];
        }

        // Handle review_statuses filter
        if (isset($filters['review_statuses'])) {
            $apiFilters['review_statuses'] = is_string($filters['review_statuses'])
                ? [$filters['review_statuses']]
                : $filters['review_statuses'];
        }

        // Handle bookkeeping_statuses filter
        if (isset($filters['bookkeeping_statuses'])) {
            $apiFilters['bookkeeping_statuses'] = is_string($filters['bookkeeping_statuses'])
                ? [$filters['bookkeeping_statuses']]
                : $filters['bookkeeping_statuses'];
        }

        // Handle payment_statuses filter
        if (isset($filters['payment_statuses'])) {
            $apiFilters['payment_statuses'] = is_string($filters['payment_statuses'])
                ? [$filters['payment_statuses']]
                : $filters['payment_statuses'];
        }

        // Handle department_ids filter
        if (isset($filters['department_ids'])) {
            $apiFilters['department_ids'] = is_string($filters['department_ids'])
                ? [$filters['department_ids']]
                : $filters['department_ids'];
        }

        // Handle supplier filter
        if (isset($filters['supplier']) && is_array($filters['supplier'])) {
            if (empty($filters['supplier']['type']) || empty($filters['supplier']['id'])) {
                throw new InvalidArgumentException(
                    'Supplier filter requires both type (company or contact) and id'
                );
            }

            if (! in_array($filters['supplier']['type'], ['company', 'contact'])) {
                throw new InvalidArgumentException(
                    "Invalid supplier type '{$filters['supplier']['type']}'. Must be one of: company, contact"
                );
            }

            $apiFilters['supplier'] = [
                'type' => $filters['supplier']['type'],
                'id' => $filters['supplier']['id'],
            ];
        }

        // Handle document_date filter
        if (isset($filters['document_date']) && is_array($filters['document_date'])) {
            $apiFilters['document_date'] = $this->buildDateFilter($filters['document_date']);
        }

        // Handle paid_at filter
        if (isset($filters['paid_at']) && is_array($filters['paid_at'])) {
            $apiFilters['paid_at'] = $this->buildDateFilter($filters['paid_at']);
        }

        return $apiFilters;
    }

    /**
     * Build a date filter object for the API request
     *
     * @param  array  $dateFilter  Date filter with operator and value/start/end
     */
    private function buildDateFilter(array $dateFilter): array
    {
        if (empty($dateFilter['operator'])) {
            throw new InvalidArgumentException(
                'Date filter requires an operator: is_empty, between, equals, before, after'
            );
        }

        $built = ['operator' => $dateFilter['operator']];

        if (in_array($dateFilter['operator'], ['equals', 'before', 'after'])) {
            if (isset($dateFilter['value'])) {
                $built['value'] = $dateFilter['value'];
            }
        }

        if ($dateFilter['operator'] === 'between') {
            if (isset($dateFilter['start'])) {
                $built['start'] = $dateFilter['start'];
            }
            if (isset($dateFilter['end'])) {
                $built['end'] = $dateFilter['end'];
            }
        }

        return $built;
    }

    /**
     * Build sort array for the API request
     *
     * @param  array  $sort  Array of sort items with field and optional order
     */
    private function buildSort(array $sort): array
    {
        $apiSort = [];

        foreach ($sort as $item) {
            if (empty($item['field'])) {
                continue;
            }

            if (! in_array($item['field'], $this->validSortFields)) {
                throw new InvalidArgumentException(
                    "Invalid sort field '{$item['field']}'. Available fields: ".
                    implode(', ', $this->validSortFields)
                );
            }

            $apiSort[] = [
                'field' => $item['field'],
                'order' => $item['order'] ?? 'asc',
            ];
        }

        return $apiSort;
    }
}
