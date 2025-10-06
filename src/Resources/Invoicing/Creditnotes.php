<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Creditnotes extends Resource
{
    protected string $description = 'Manage credit notes in Teamleader Focus';

    // Resource capabilities - Credit notes are read-only (created via invoice credit operations)
    protected bool $supportsCreation = false;
    protected bool $supportsUpdate = false;
    protected bool $supportsDeletion = false;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = true;
    protected bool $supportsSorting = false; // No sorting mentioned in API docs
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of credit note UUIDs',
        'department_id' => 'Filter on department (company entity)',
        'updated_since' => 'ISO 8601 datetime',
        'invoice_id' => 'Filter on invoice UUID',
        'project_id' => 'Filter on project UUID',
        'customer' => 'Customer object with type and id',
        'credit_note_date_after' => 'Date (inclusive, YYYY-MM-DD)',
        'credit_note_date_before' => 'Date (exclusive, YYYY-MM-DD)',
    ];

    // Valid download formats
    protected array $validDownloadFormats = [
        'pdf',
        'ubl/e-fff',
    ];

    // Valid customer types
    protected array $validCustomerTypes = [
        'contact',
        'company',
    ];

    // Valid currency codes
    protected array $validCurrencyCodes = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK',
        'EUR', 'GBP', 'INR', 'ISK', 'JPY', 'MAD', 'MXN', 'NOK',
        'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

    // Usage examples specific to credit notes
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all credit notes',
            'code' => '$creditNotes = $teamleader->creditnotes()->list();'
        ],
        'filter_by_invoice' => [
            'description' => 'Get credit notes for a specific invoice',
            'code' => '$creditNotes = $teamleader->creditnotes()->forInvoice(\'invoice-uuid\');'
        ],
        'filter_by_customer' => [
            'description' => 'Get credit notes for a specific customer',
            'code' => '$creditNotes = $teamleader->creditnotes()->forCustomer(\'company\', \'customer-uuid\');'
        ],
        'filter_by_project' => [
            'description' => 'Get credit notes for a specific project',
            'code' => '$creditNotes = $teamleader->creditnotes()->forProject(\'project-uuid\');'
        ],
        'filter_by_date_range' => [
            'description' => 'Get credit notes within a date range',
            'code' => '$creditNotes = $teamleader->creditnotes()->betweenDates(\'2022-01-01\', \'2023-01-01\');'
        ],
        'get_info' => [
            'description' => 'Get detailed credit note information',
            'code' => '$creditNote = $teamleader->creditnotes()->info(\'credit-note-uuid\');'
        ],
        'download_pdf' => [
            'description' => 'Download credit note as PDF',
            'code' => '$download = $teamleader->creditnotes()->download(\'credit-note-uuid\', \'pdf\');'
        ],
        'download_ubl' => [
            'description' => 'Download credit note as UBL e-fff format',
            'code' => '$download = $teamleader->creditnotes()->download(\'credit-note-uuid\', \'ubl/e-fff\');'
        ],
        'send_peppol' => [
            'description' => 'Send credit note via Peppol network',
            'code' => '$result = $teamleader->creditnotes()->sendViaPeppol(\'credit-note-uuid\');'
        ],
        'get_booked' => [
            'description' => 'Get only booked credit notes',
            'code' => '$creditNotes = $teamleader->creditnotes()->booked();'
        ],
        'get_paid' => [
            'description' => 'Get paid credit notes',
            'code' => '$creditNotes = $teamleader->creditnotes()->paid();'
        ],
        'get_unpaid' => [
            'description' => 'Get unpaid credit notes',
            'code' => '$creditNotes = $teamleader->creditnotes()->unpaid();'
        ],
    ];

    /**
     * Get the base path for the credit notes resource
     */
    protected function getBasePath(): string
    {
        return 'creditNotes';
    }

    /**
     * List credit notes with filtering and pagination
     *
     * @param array $filters Filter parameters
     * @param array $options Pagination options
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1
            ];
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get detailed information about a specific credit note
     *
     * @param string $id Credit note UUID
     * @param mixed $includes Optional includes (not used for credit notes)
     * @return array
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.info', [
            'id' => $id
        ]);
    }

    /**
     * Download a credit note in a specific format
     *
     * @param string $id Credit note UUID
     * @param string $format Format (pdf, ubl/e-fff)
     * @return array Returns temporary download URL and expiration
     */
    public function download(string $id, string $format = 'pdf'): array
    {
        if (!in_array($format, $this->validDownloadFormats)) {
            throw new InvalidArgumentException(
                "Invalid format '{$format}'. Must be one of: " . implode(', ', $this->validDownloadFormats)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.download', [
            'id' => $id,
            'format' => $format
        ]);
    }

    /**
     * Send a credit note via the Peppol network
     *
     * @param string $id Credit note UUID
     * @return array
     */
    public function sendViaPeppol(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.sendViaPeppol', [
            'id' => $id
        ]);
    }

    /**
     * Get booked credit notes (convenience method)
     *
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function booked(array $additionalFilters = [], array $options = []): array
    {
        return $this->list($additionalFilters, $options);
    }

    /**
     * Get paid credit notes
     *
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function paid(array $additionalFilters = [], array $options = []): array
    {
        $filters = $additionalFilters;
        $filters['_paid'] = true; // Custom filter flag for internal use

        return $this->list($filters, $options);
    }

    /**
     * Get unpaid credit notes
     *
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function unpaid(array $additionalFilters = [], array $options = []): array
    {
        $filters = $additionalFilters;
        $filters['_paid'] = false; // Custom filter flag for internal use

        return $this->list($filters, $options);
    }

    /**
     * Get credit notes for a specific invoice
     *
     * @param string $invoiceId Invoice UUID
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function forInvoice(string $invoiceId, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['invoice_id' => $invoiceId], $additionalFilters),
            $options
        );
    }

    /**
     * Get credit notes for a specific customer
     *
     * @param string $customerType Customer type (contact or company)
     * @param string $customerId Customer UUID
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function forCustomer(string $customerType, string $customerId, array $additionalFilters = [], array $options = []): array
    {
        $this->validateCustomerType($customerType);

        return $this->list(
            array_merge([
                'customer' => [
                    'type' => $customerType,
                    'id' => $customerId
                ]
            ], $additionalFilters),
            $options
        );
    }

    /**
     * Get credit notes for a specific project
     *
     * @param string $projectId Project UUID
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function forProject(string $projectId, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['project_id' => $projectId], $additionalFilters),
            $options
        );
    }

    /**
     * Get credit notes for a specific department
     *
     * @param string $departmentId Department UUID
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function forDepartment(string $departmentId, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['department_id' => $departmentId], $additionalFilters),
            $options
        );
    }

    /**
     * Get credit notes between specific dates
     *
     * @param string $dateAfter Start date (inclusive, YYYY-MM-DD)
     * @param string $dateBefore End date (exclusive, YYYY-MM-DD)
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function betweenDates(string $dateAfter, string $dateBefore, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge([
                'credit_note_date_after' => $dateAfter,
                'credit_note_date_before' => $dateBefore
            ], $additionalFilters),
            $options
        );
    }

    /**
     * Get credit notes created/updated since a specific date
     *
     * @param string $since ISO 8601 datetime
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function updatedSince(string $since, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['updated_since' => $since], $additionalFilters),
            $options
        );
    }

    /**
     * Build filters for the API request
     *
     * @param array $filters
     * @return array
     */
    protected function buildFilters(array $filters): array
    {
        $built = [];

        foreach ($filters as $key => $value) {
            // Skip internal filter flags
            if (strpos($key, '_') === 0) {
                continue;
            }

            $built[$key] = $value;
        }

        return $built;
    }

    /**
     * Validate customer type
     *
     * @param string $type
     * @throws InvalidArgumentException
     */
    private function validateCustomerType(string $type): void
    {
        if (!in_array($type, $this->validCustomerTypes)) {
            throw new InvalidArgumentException(
                "Invalid customer type '{$type}'. Must be one of: " .
                implode(', ', $this->validCustomerTypes)
            );
        }
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of credit notes',
                'fields' => [
                    'data' => 'Array of credit note objects',
                    'data[].id' => 'Credit note UUID',
                    'data[].department' => 'Department reference',
                    'data[].credit_note_number' => 'Credit note number (nullable)',
                    'data[].credit_note_date' => 'Credit note date (nullable)',
                    'data[].status' => 'Status (booked)',
                    'data[].invoice' => 'Related invoice reference (nullable)',
                    'data[].paid' => 'Payment status (boolean)',
                    'data[].paid_at' => 'Payment date (nullable)',
                    'data[].invoicee' => 'Invoicee information',
                    'data[].customer' => 'Customer reference',
                    'data[].total' => 'Total amounts (tax_exclusive, tax_inclusive, payable)',
                    'data[].taxes' => 'Tax breakdown',
                    'data[].created_at' => 'Creation timestamp',
                    'data[].updated_at' => 'Last update timestamp'
                ]
            ],
            'info' => [
                'description' => 'Complete credit note information',
                'fields' => [
                    'data.id' => 'Credit note UUID',
                    'data.department' => 'Department reference',
                    'data.credit_note_number' => 'Credit note number (nullable)',
                    'data.credit_note_date' => 'Credit note date (nullable)',
                    'data.status' => 'Status (booked)',
                    'data.invoice' => 'Related invoice reference (nullable)',
                    'data.paid' => 'Payment status (boolean)',
                    'data.paid_at' => 'Payment date (nullable)',
                    'data.invoicee' => 'Invoicee information with name, vat_number, customer',
                    'data.customer' => 'Customer reference with email and national_identification_number',
                    'data.discounts' => 'Applied discounts',
                    'data.total' => 'Total amounts breakdown',
                    'data.taxes' => 'Tax calculations',
                    'data.grouped_lines' => 'Line items grouped by section',
                    'data.currency' => 'Currency code',
                    'data.currency_exchange_rate' => 'Exchange rate information (nullable)',
                    'data.created_at' => 'Creation timestamp',
                    'data.updated_at' => 'Last update timestamp',
                    'data.document_template' => 'Document template reference'
                ]
            ],
            'download' => [
                'description' => 'Temporary download URL',
                'fields' => [
                    'data.location' => 'Temporary URL where file can be downloaded',
                    'data.expires' => 'Expiration time of the download link'
                ]
            ]
        ];
    }
}
