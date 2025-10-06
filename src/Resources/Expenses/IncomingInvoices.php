<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class IncomingInvoices extends Resource
{
    protected string $description = 'Manage incoming invoices from suppliers in Teamleader Focus';

    // Resource capabilities - Incoming invoices support create, update, delete, and info
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = false;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters - Not supported for this resource
    protected array $commonFilters = [];

    // Valid currency codes
    protected array $validCurrencyCodes = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK',
        'EUR', 'GBP', 'INR', 'ISK', 'JPY', 'MAD', 'MXN', 'NOK',
        'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR'
    ];

    // Valid review statuses
    protected array $validReviewStatuses = [
        'pending',
        'approved',
        'refused',
    ];

    // Usage examples specific to incoming invoices
    protected array $usageExamples = [
        'create_basic' => [
            'description' => 'Create a basic incoming invoice',
            'code' => '$invoice = $teamleader->incomingInvoices()->add([\'title\' => \'Invoice\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_exclusive\' => [\'amount\' => 1000]]]);'
        ],
        'create_complete' => [
            'description' => 'Create a complete incoming invoice with all details',
            'code' => '$invoice = $teamleader->incomingInvoices()->add([\'title\' => \'Monthly Services\', \'supplier_id\' => \'uuid\', \'document_number\' => \'INV-001\', \'invoice_date\' => \'2024-01-15\', \'due_date\' => \'2024-02-15\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_exclusive\' => [\'amount\' => 2500]]]);'
        ],
        'get_info' => [
            'description' => 'Get invoice details',
            'code' => '$invoice = $teamleader->incomingInvoices()->info(\'invoice-uuid\');'
        ],
        'update_invoice' => [
            'description' => 'Update an existing invoice',
            'code' => '$teamleader->incomingInvoices()->update(\'invoice-uuid\', [\'title\' => \'Updated Title\', \'due_date\' => \'2024-03-15\']);'
        ],
        'approve_invoice' => [
            'description' => 'Approve an invoice',
            'code' => '$teamleader->incomingInvoices()->approve(\'invoice-uuid\');'
        ],
        'refuse_invoice' => [
            'description' => 'Refuse an invoice',
            'code' => '$teamleader->incomingInvoices()->refuse(\'invoice-uuid\');'
        ],
        'send_to_bookkeeping' => [
            'description' => 'Send invoice to bookkeeping',
            'code' => '$teamleader->incomingInvoices()->sendToBookkeeping(\'invoice-uuid\');'
        ],
        'delete_invoice' => [
            'description' => 'Delete an invoice',
            'code' => '$teamleader->incomingInvoices()->delete(\'invoice-uuid\');'
        ]
    ];

    /**
     * Get the base path for the incoming invoices resource
     */
    protected function getBasePath(): string
    {
        return 'incomingInvoices';
    }

    /**
     * Create a new incoming invoice
     *
     * @param array $data Invoice data
     * @return array Created invoice response
     * @throws InvalidArgumentException When required fields are missing
     */
    public function add(array $data): array
    {
        // Validate required fields
        if (empty($data['title'])) {
            throw new InvalidArgumentException('title is required for incoming invoices');
        }

        if (empty($data['currency']['code'])) {
            throw new InvalidArgumentException('currency.code is required for incoming invoices');
        }

        if (empty($data['total'])) {
            throw new InvalidArgumentException('total is required for incoming invoices');
        }

        // Validate that either tax_exclusive or tax_inclusive is provided
        if (empty($data['total']['tax_exclusive']) && empty($data['total']['tax_inclusive'])) {
            throw new InvalidArgumentException('Either total.tax_exclusive or total.tax_inclusive is required');
        }

        // Validate currency code
        if (!in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: ' . implode(', ', $this->validCurrencyCodes)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.add', $data);
    }

    /**
     * Alias for add() method to maintain consistency with other resources
     *
     * @param array $data Invoice data
     * @return array
     */
    public function create(array $data): array
    {
        return $this->add($data);
    }

    /**
     * Update an existing incoming invoice
     *
     * @param string $id Invoice UUID
     * @param array $data Data to update
     * @return array Update response
     * @throws InvalidArgumentException When ID is empty
     */
    public function update(string $id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        // Validate currency code if provided
        if (isset($data['currency']['code']) && !in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: ' . implode(', ', $this->validCurrencyCodes)
            );
        }

        $params = array_merge(['id' => $id], $data);

        return $this->api->request('POST', $this->getBasePath() . '.update', $params);
    }

    /**
     * Get information about a specific incoming invoice
     *
     * @param string $id Invoice UUID
     * @param mixed $includes Not used for incoming invoices
     * @return array Invoice information
     * @throws InvalidArgumentException When ID is empty
     */
    public function info(string $id, $includes = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.info', ['id' => $id]);
    }

    /**
     * Delete an incoming invoice
     *
     * @param string $id Invoice UUID
     * @return array Delete response
     * @throws InvalidArgumentException When ID is empty
     */
    public function delete(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Approve an incoming invoice
     *
     * @param string $id Invoice UUID
     * @return array Approval response
     * @throws InvalidArgumentException When ID is empty
     */
    public function approve(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.approve', ['id' => $id]);
    }

    /**
     * Refuse an incoming invoice
     *
     * @param string $id Invoice UUID
     * @return array Refusal response
     * @throws InvalidArgumentException When ID is empty
     */
    public function refuse(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.refuse', ['id' => $id]);
    }

    /**
     * Mark an incoming invoice as pending review
     *
     * @param string $id Invoice UUID
     * @return array Response
     * @throws InvalidArgumentException When ID is empty
     */
    public function markAsPendingReview(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.markAsPendingReview', ['id' => $id]);
    }

    /**
     * Send an incoming invoice to bookkeeping
     *
     * @param string $id Invoice UUID
     * @return array Response
     * @throws InvalidArgumentException When ID is empty
     */
    public function sendToBookkeeping(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.sendToBookkeeping', ['id' => $id]);
    }

    /**
     * List method is not supported for incoming invoices
     *
     * @param array $filters
     * @param array $options
     * @return array
     * @throws InvalidArgumentException
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new InvalidArgumentException(
            'The list method is not supported for incoming invoices. Use info() to get a specific invoice.'
        );
    }

    /**
     * Get valid currency codes for incoming invoices
     *
     * @return array Array of valid currency codes
     */
    public function getValidCurrencyCodes(): array
    {
        return $this->validCurrencyCodes;
    }

    /**
     * Get valid review statuses for incoming invoices
     *
     * @return array Array of valid review statuses
     */
    public function getValidReviewStatuses(): array
    {
        return $this->validReviewStatuses;
    }

    /**
     * Validate invoice data before creating or updating
     *
     * @param array $data Invoice data to validate
     * @param bool $isUpdate Whether this is for an update operation
     * @return array Validated data
     */
    protected function validateInvoiceData(array $data, bool $isUpdate = false): array
    {
        // For updates, required fields are not mandatory
        if (!$isUpdate) {
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required');
            }

            if (empty($data['currency']['code'])) {
                throw new InvalidArgumentException('currency.code is required');
            }

            if (empty($data['total'])) {
                throw new InvalidArgumentException('total is required');
            }

            if (empty($data['total']['tax_exclusive']) && empty($data['total']['tax_inclusive'])) {
                throw new InvalidArgumentException('Either total.tax_exclusive or total.tax_inclusive is required');
            }
        }

        // Validate currency code if provided
        if (isset($data['currency']['code']) && !in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: ' . implode(', ', $this->validCurrencyCodes)
            );
        }

        return $data;
    }
}
