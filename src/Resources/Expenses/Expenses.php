<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Expenses extends Resource
{
    use ValidatesWritePayload;

    /** `filter.source_types[]` */
    public const SOURCE_TYPES = ['incomingInvoice', 'incomingCreditNote', 'receipt'];

    /** `filter.review_statuses[]` */
    public const REVIEW_STATUSES = ['pending', 'approved', 'refused'];

    /** `filter.bookkeeping_statuses[]` */
    public const BOOKKEEPING_STATUSES = ['sent', 'not_sent'];

    /** `filter.payment_statuses[]` */
    public const PAYMENT_STATUSES = ['unknown', 'paid', 'partially_paid', 'credited', 'not_paid'];

    /** `filter.supplier.type` */
    public const SUPPLIER_TYPES = ['company', 'contact'];

    /** `filter.document_date.operator` and `filter.paid_at.operator` */
    public const DATE_OPERATORS = ['is_empty', 'between', 'equals', 'before', 'after'];

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

    /**
     * `includes=pagination` adds a meta block with the total match count —
     * expenses.list documents it, so it is requested on every call.
     */
    protected bool $requestsPaginationMeta = true;

    // Payment statuses expenses.list filters on. Until v2.2.8 this was
    // ['paid', 'unpaid'] — `unpaid` is not a value the API knows, so
    // unpaid() could never work.
    protected array $validPaymentStatuses = self::PAYMENT_STATUSES;

    // Sort fields accepted by expenses.list — a keyed map, so normaliseSort()
    // validates against it
    protected array $availableSortFields = [
        'document_date' => 'Document date',
        'due_date' => 'Due date',
        'supplier_name' => 'Supplier name',
    ];

    // Kept for backwards compatibility — see $availableSortFields
    protected array $validSortFields = ['document_date', 'due_date', 'supplier_name'];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'term' => 'Search by document number, title and supplier name (case-insensitive)',
        'source_types' => 'Filter by expense source type(s): incomingInvoice, incomingCreditNote, receipt',
        'review_statuses' => 'Filter by review status(es): pending, approved, refused',
        'bookkeeping_statuses' => 'Filter by bookkeeping status(es): sent, not_sent',
        'payment_statuses' => 'Filter by payment status(es): unknown, paid, partially_paid, credited, not_paid',
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
            'code' => '$expenses = $teamleader->expenses()->list([], ["sort" => "document_date", "sort_order" => "desc"]);',
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

        // Apply sorting — a field name, a list of names, or sort objects.
        // Before v2.2.8 only the last form was read; a field name was dropped.
        if (isset($options['sort'])) {
            $params['sort'] = $this->normaliseSort($options['sort'], $options['sort_order'] ?? 'desc');
        }

        // Request the meta block (total match count) unless told otherwise
        $params = $this->applyIncludes($params, $this->resolveIncludesOption($options) ?? 'pagination');

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
     * Get expenses not (fully) paid: not_paid and partially_paid
     *
     * Before v2.2.8 this sent `unpaid`, which is not a payment status the API
     * knows.
     */
    public function unpaid(): array
    {
        return $this->list(['payment_statuses' => ['not_paid', 'partially_paid']]);
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
     * Build the `filter` object for expenses.list
     *
     * Before v2.2.8: unknown keys were dropped without a word, enum values
     * (statuses, source types, date operators) were not checked, and a date
     * filter with `between` but no start or end was sent half-built.
     *
     * @throws InvalidArgumentException When a key, value or date filter is not valid
     */
    private function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'expenses.list');

        $enums = [
            'source_types' => self::SOURCE_TYPES,
            'review_statuses' => self::REVIEW_STATUSES,
            'bookkeeping_statuses' => self::BOOKKEEPING_STATUSES,
            'payment_statuses' => self::PAYMENT_STATUSES,
        ];

        $apiFilters = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if (isset($enums[$key]) || $key === 'department_ids') {
                $value = is_array($value) ? array_values($value) : [$value];

                foreach (isset($enums[$key]) ? $value : [] as $item) {
                    $this->assertEnum($item, $enums[$key], "filter.{$key}[]", 'expenses.list');
                }

                $apiFilters[$key] = $value;

                continue;
            }

            if ($key === 'supplier') {
                if (! is_array($value) || empty($value['type']) || empty($value['id'])) {
                    throw new InvalidArgumentException(
                        'Supplier filter requires both type (company or contact) and id'
                    );
                }

                $this->assertEnum($value['type'], self::SUPPLIER_TYPES, 'filter.supplier.type', 'expenses.list');
                $apiFilters['supplier'] = ['type' => $value['type'], 'id' => $value['id']];

                continue;
            }

            if ($key === 'document_date' || $key === 'paid_at') {
                if (! is_array($value)) {
                    throw new InvalidArgumentException(
                        "{$key} takes ['operator' => ..., 'value' | 'start' + 'end' => 'YYYY-MM-DD']"
                    );
                }

                $apiFilters[$key] = $this->buildDateFilter($value, $key);

                continue;
            }

            $apiFilters[$key] = $value;
        }

        return $apiFilters;
    }

    /**
     * Build a date filter object for the API request
     *
     * - `is_empty` takes nothing else
     * - `equals`, `before`, `after` take `value`
     * - `between` takes `start` and `end`
     *
     * @throws InvalidArgumentException When the operator or its operands are missing or invalid
     */
    private function buildDateFilter(array $dateFilter, string $key = 'date'): array
    {
        $operator = $dateFilter['operator'] ?? null;

        if (! is_string($operator) || ! in_array($operator, self::DATE_OPERATORS, true)) {
            throw new InvalidArgumentException(
                "filter.{$key}.operator must be one of: ".implode(', ', self::DATE_OPERATORS)
            );
        }

        $built = ['operator' => $operator];

        if (in_array($operator, ['equals', 'before', 'after'], true)) {
            if (empty($dateFilter['value'])) {
                throw new InvalidArgumentException("filter.{$key} with operator {$operator} needs a value");
            }

            $built['value'] = $dateFilter['value'];
        }

        if ($operator === 'between') {
            if (empty($dateFilter['start']) || empty($dateFilter['end'])) {
                throw new InvalidArgumentException("filter.{$key} with operator between needs start and end");
            }

            $built['start'] = $dateFilter['start'];
            $built['end'] = $dateFilter['end'];
        }

        return $built;
    }
}
