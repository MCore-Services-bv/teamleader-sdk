<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class PaymentTerms extends Resource
{
    protected string $description = 'Manage payment terms in Teamleader Focus';

    // Resource capabilities - Payment terms are read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = false;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters - none available for payment terms
    protected array $commonFilters = [];

    // Valid payment term types
    protected array $validTypes = [
        'cash',
        'end_of_month',
        'after_invoice_date',
    ];

    // Usage examples specific to payment terms
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all payment terms',
            'code' => '$paymentTerms = $teamleader->paymentTerms()->list();',
        ],
        'get_default' => [
            'description' => 'Get the default payment term',
            'code' => '$defaultTerm = $teamleader->paymentTerms()->getDefault();',
        ],
        'find_by_type' => [
            'description' => 'Find payment terms by type',
            'code' => '$cashTerms = $teamleader->paymentTerms()->findByType("cash");',
        ],
        'find_by_days' => [
            'description' => 'Find payment terms by number of days',
            'code' => '$term = $teamleader->paymentTerms()->findByDays(30);',
        ],
        'as_options' => [
            'description' => 'Get payment terms as key-value pairs for dropdowns',
            'code' => '$options = $teamleader->paymentTerms()->asOptions();',
        ],
    ];

    /**
     * Get the base path for the payment terms resource
     */
    protected function getBasePath(): string
    {
        return 'paymentTerms';
    }

    /**
     * List all payment terms
     *
     * @param  array  $filters  Not used for payment terms
     * @param  array  $options  Not used for payment terms
     */
    public function list(array $filters = [], array $options = []): array
    {
        return $this->api->request('POST', $this->getBasePath().'.list', []);
    }

    /**
     * Get the default payment term
     *
     * @return array|null Payment term data or null if no default is set
     */
    public function getDefault(): ?array
    {
        $result = $this->list();

        if (empty($result['meta']['default'])) {
            return null;
        }

        $defaultId = $result['meta']['default'];

        foreach ($result['data'] as $term) {
            if ($term['id'] === $defaultId) {
                return $term;
            }
        }

        return null;
    }

    /**
     * Get the default payment term ID
     *
     * @return string|null Default payment term ID or null if not set
     */
    public function getDefaultId(): ?string
    {
        $result = $this->list();

        return $result['meta']['default'] ?? null;
    }

    /**
     * Find payment terms by type
     *
     * @param  string  $type  Payment term type (cash, end_of_month, after_invoice_date)
     * @return array Array of matching payment terms
     */
    public function findByType(string $type): array
    {
        if (! in_array($type, $this->validTypes)) {
            throw new InvalidArgumentException(
                "Invalid payment term type '{$type}'. Must be one of: ".
                implode(', ', $this->validTypes)
            );
        }

        $result = $this->list();
        $matches = [];

        foreach ($result['data'] as $term) {
            if ($term['type'] === $type) {
                $matches[] = $term;
            }
        }

        return $matches;
    }

    /**
     * Find a payment term by number of days
     *
     * @param  int  $days  Number of days
     * @param  string|null  $type  Optional payment term type to filter by
     * @return array|null Payment term data or null if not found
     */
    public function findByDays(int $days, ?string $type = null): ?array
    {
        if ($type !== null && ! in_array($type, $this->validTypes)) {
            throw new InvalidArgumentException(
                "Invalid payment term type '{$type}'. Must be one of: ".
                implode(', ', $this->validTypes)
            );
        }

        $result = $this->list();

        foreach ($result['data'] as $term) {
            // Match days
            if (! isset($term['days']) || $term['days'] !== $days) {
                continue;
            }

            // Match type if specified
            if ($type !== null && $term['type'] !== $type) {
                continue;
            }

            return $term;
        }

        return null;
    }

    /**
     * Find a payment term by ID
     *
     * @param  string  $id  Payment term UUID
     * @return array|null Payment term data or null if not found
     */
    public function find(string $id): ?array
    {
        $result = $this->list();

        foreach ($result['data'] as $term) {
            if ($term['id'] === $id) {
                return $term;
            }
        }

        return null;
    }

    /**
     * Check if a payment term exists by ID
     *
     * @param  string  $id  Payment term UUID
     */
    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Get payment terms formatted as options for select dropdowns
     *
     * @return array Array with id => description pairs
     */
    public function asOptions(): array
    {
        $result = $this->list();
        $options = [];

        foreach ($result['data'] as $term) {
            $options[$term['id']] = $this->formatPaymentTermDescription($term);
        }

        return $options;
    }

    /**
     * Format a payment term as a human-readable description
     *
     * @param  array  $term  Payment term data
     * @return string Formatted description
     */
    public function formatPaymentTermDescription(array $term): string
    {
        switch ($term['type']) {
            case 'cash':
                return 'Cash (immediate payment)';

            case 'end_of_month':
                if (isset($term['days']) && $term['days'] > 0) {
                    return "End of month + {$term['days']} days";
                }

                return 'End of month';

            case 'after_invoice_date':
                $days = $term['days'] ?? 0;

                return "{$days} days after invoice date";

            default:
                return ucfirst(str_replace('_', ' ', $term['type']));
        }
    }

    /**
     * Get cash payment terms (immediate payment)
     *
     * @return array Array of cash payment terms
     */
    public function cash(): array
    {
        return $this->findByType('cash');
    }

    /**
     * Get end of month payment terms
     *
     * @return array Array of end of month payment terms
     */
    public function endOfMonth(): array
    {
        return $this->findByType('end_of_month');
    }

    /**
     * Get after invoice date payment terms
     *
     * @return array Array of after invoice date payment terms
     */
    public function afterInvoiceDate(): array
    {
        return $this->findByType('after_invoice_date');
    }

    /**
     * Validate payment term type
     */
    public function isValidType(string $type): bool
    {
        return in_array($type, $this->validTypes);
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of payment terms with default indicator',
                'fields' => [
                    'data' => 'Array of payment term objects',
                    'data[].id' => 'Payment term UUID',
                    'data[].type' => 'Payment term type (cash, end_of_month, after_invoice_date)',
                    'data[].days' => 'Number of days modifier (not required for cash type)',
                    'meta' => 'Metadata object',
                    'meta.default' => 'UUID of the default payment term',
                ],
            ],
        ];
    }
}
