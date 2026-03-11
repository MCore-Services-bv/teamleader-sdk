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
        'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

    // Valid review statuses
    protected array $validReviewStatuses = [
        'pending',
        'approved',
        'refused',
    ];

    // Valid payment statuses (returned by info endpoint)
    protected array $validPaymentStatuses = [
        'unknown',
        'paid',
        'partially_paid',
        'not_paid',
    ];

    // Usage examples specific to incoming invoices
    protected array $usageExamples = [
        'create_basic' => [
            'description' => 'Create a basic incoming invoice',
            'code' => '$invoice = $teamleader->incomingInvoices()->add([\'title\' => \'Invoice\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_exclusive\' => [\'amount\' => 1000]]]);',
        ],
        'create_complete' => [
            'description' => 'Create a complete incoming invoice with all details',
            'code' => '$invoice = $teamleader->incomingInvoices()->add([\'title\' => \'Monthly Services\', \'supplier_id\' => \'uuid\', \'document_number\' => \'INV-001\', \'invoice_date\' => \'2024-01-15\', \'due_date\' => \'2024-02-15\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_exclusive\' => [\'amount\' => 2500]]]);',
        ],
        'get_info' => [
            'description' => 'Get invoice details',
            'code' => '$invoice = $teamleader->incomingInvoices()->info(\'invoice-uuid\');',
        ],
        'update_invoice' => [
            'description' => 'Update an existing invoice',
            'code' => '$teamleader->incomingInvoices()->update(\'invoice-uuid\', [\'title\' => \'Updated Title\', \'due_date\' => \'2024-03-15\']);',
        ],
        'approve_invoice' => [
            'description' => 'Approve an invoice',
            'code' => '$teamleader->incomingInvoices()->approve(\'invoice-uuid\');',
        ],
        'refuse_invoice' => [
            'description' => 'Refuse an invoice',
            'code' => '$teamleader->incomingInvoices()->refuse(\'invoice-uuid\');',
        ],
        'send_to_bookkeeping' => [
            'description' => 'Send invoice to bookkeeping',
            'code' => '$teamleader->incomingInvoices()->sendToBookkeeping(\'invoice-uuid\');',
        ],
        'delete_invoice' => [
            'description' => 'Delete an invoice',
            'code' => '$teamleader->incomingInvoices()->delete(\'invoice-uuid\');',
        ],
        'list_payments' => [
            'description' => 'List all payments for an invoice',
            'code' => '$payments = $teamleader->incomingInvoices()->listPayments(\'invoice-uuid\');',
        ],
        'register_payment' => [
            'description' => 'Register a payment for an invoice',
            'code' => '$teamleader->incomingInvoices()->registerPayment(\'invoice-uuid\', [\'amount\' => 500.00, \'currency\' => \'EUR\'], \'2024-01-15T10:00:00Z\');',
        ],
        'remove_payment' => [
            'description' => 'Remove a payment from an invoice',
            'code' => '$teamleader->incomingInvoices()->removePayment(\'invoice-uuid\', \'payment-uuid\');',
        ],
        'update_payment' => [
            'description' => 'Update a payment on an invoice',
            'code' => '$teamleader->incomingInvoices()->updatePayment(\'invoice-uuid\', \'payment-uuid\', [\'amount\' => 600.00, \'currency\' => \'EUR\']);',
        ],
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
     * @param  array  $data  Invoice data
     * @return array Created invoice response
     *
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
        if (! in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: '.implode(', ', $this->validCurrencyCodes)
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.add', $data);
    }

    /**
     * Alias for add() method to maintain consistency with other resources
     *
     * @param  array  $data  Invoice data
     */
    public function create(array $data): array
    {
        return $this->add($data);
    }

    /**
     * Update an existing incoming invoice
     *
     * @param  string  $id  Invoice UUID
     * @param  array  $data  Data to update
     * @return array Update response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function update(string $id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        // Validate currency code if provided
        if (isset($data['currency']['code']) && ! in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: '.implode(', ', $this->validCurrencyCodes)
            );
        }

        $params = array_merge(['id' => $id], $data);

        return $this->api->request('POST', $this->getBasePath().'.update', $params);
    }

    /**
     * Get information about a specific incoming invoice
     *
     * Response includes payment_status with possible values:
     * unknown, paid, partially_paid, not_paid
     *
     * @param  string  $id  Invoice UUID
     * @param  mixed  $includes  Not used for incoming invoices
     * @return array Invoice information
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function info(string $id, $includes = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.info', ['id' => $id]);
    }

    /**
     * Delete an incoming invoice
     *
     * @param  string  $id  Invoice UUID
     * @return array Delete response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function delete(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Approve an incoming invoice
     *
     * @param  string  $id  Invoice UUID
     * @return array Approval response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function approve(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.approve', ['id' => $id]);
    }

    /**
     * Refuse an incoming invoice
     *
     * @param  string  $id  Invoice UUID
     * @return array Refusal response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function refuse(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.refuse', ['id' => $id]);
    }

    /**
     * Mark an incoming invoice as pending review
     *
     * @param  string  $id  Invoice UUID
     * @return array Response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function markAsPendingReview(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.markAsPendingReview', ['id' => $id]);
    }

    /**
     * Send an incoming invoice to bookkeeping
     *
     * @param  string  $id  Invoice UUID
     * @return array Response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function sendToBookkeeping(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.sendToBookkeeping', ['id' => $id]);
    }

    /**
     * List all payments for an incoming invoice
     *
     * Returns an array of payment objects, each containing:
     * - id (string): Payment UUID
     * - payment.amount (float): Payment amount
     * - payment.currency (string): Currency code
     * - paid_at (datetime): When the payment was made
     * - payment_method (object|null): Payment method reference (type + id)
     * - remark (string|null): Optional remark
     * Also includes meta.total.amount for the total paid amount.
     *
     * @param  string  $id  Invoice UUID
     * @return array List of payments with meta totals
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function listPayments(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.listPayments', ['id' => $id]);
    }

    /**
     * Register a payment for an incoming invoice
     *
     * @param  string  $id  Invoice UUID
     * @param  array  $payment  Payment details with required 'amount' (float) and 'currency' (string)
     * @param  string  $paidAt  ISO 8601 datetime when the payment was made
     * @param  string|null  $paymentMethodId  Optional payment method UUID
     * @param  string|null  $remark  Optional remark
     * @return array Created payment response with data.type and data.id
     *
     * @throws InvalidArgumentException When required fields are missing or invalid
     */
    public function registerPayment(string $id, array $payment, string $paidAt, ?string $paymentMethodId = null, ?string $remark = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        if (empty($payment['amount']) || ! is_numeric($payment['amount'])) {
            throw new InvalidArgumentException('payment.amount is required and must be numeric');
        }

        if (empty($payment['currency'])) {
            throw new InvalidArgumentException('payment.currency is required');
        }

        if (! in_array($payment['currency'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid payment currency. Must be one of: '.implode(', ', $this->validCurrencyCodes)
            );
        }

        if (empty($paidAt)) {
            throw new InvalidArgumentException('paid_at is required');
        }

        $data = [
            'id' => $id,
            'payment' => $payment,
            'paid_at' => $paidAt,
        ];

        if (! empty($paymentMethodId)) {
            $data['payment_method_id'] = $paymentMethodId;
        }

        if (! empty($remark)) {
            $data['remark'] = $remark;
        }

        return $this->api->request('POST', $this->getBasePath().'.registerPayment', $data);
    }

    /**
     * Remove a payment from an incoming invoice
     *
     * @param  string  $id  Invoice UUID
     * @param  string  $paymentId  Payment UUID to remove
     * @return array Response
     *
     * @throws InvalidArgumentException When ID or payment_id is empty
     */
    public function removePayment(string $id, string $paymentId): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        if (empty($paymentId)) {
            throw new InvalidArgumentException('Payment ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.removePayment', [
            'id' => $id,
            'payment_id' => $paymentId,
        ]);
    }

    /**
     * Update a payment on an incoming invoice
     *
     * @param  string  $id  Invoice UUID
     * @param  string  $paymentId  Payment UUID to update
     * @param  array  $payment  Updated payment details with required 'amount' (float) and 'currency' (string)
     * @param  string|null  $paidAt  Optional ISO 8601 datetime override
     * @param  string|null  $paymentMethodId  Optional payment method UUID
     * @param  string|null  $remark  Optional remark
     * @return array Response
     *
     * @throws InvalidArgumentException When required fields are missing or invalid
     */
    public function updatePayment(string $id, string $paymentId, array $payment, ?string $paidAt = null, ?string $paymentMethodId = null, ?string $remark = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Invoice ID is required');
        }

        if (empty($paymentId)) {
            throw new InvalidArgumentException('Payment ID is required');
        }

        if (empty($payment['amount']) || ! is_numeric($payment['amount'])) {
            throw new InvalidArgumentException('payment.amount is required and must be numeric');
        }

        if (empty($payment['currency'])) {
            throw new InvalidArgumentException('payment.currency is required');
        }

        if (! in_array($payment['currency'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid payment currency. Must be one of: '.implode(', ', $this->validCurrencyCodes)
            );
        }

        $data = [
            'id' => $id,
            'payment_id' => $paymentId,
            'payment' => $payment,
        ];

        if (! empty($paidAt)) {
            $data['paid_at'] = $paidAt;
        }

        if (! empty($paymentMethodId)) {
            $data['payment_method_id'] = $paymentMethodId;
        }

        if (! empty($remark)) {
            $data['remark'] = $remark;
        }

        return $this->api->request('POST', $this->getBasePath().'.updatePayment', $data);
    }

    /**
     * List method is not supported for incoming invoices
     *
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
     * Get valid payment statuses for incoming invoices
     *
     * @return array Array of valid payment statuses
     */
    public function getValidPaymentStatuses(): array
    {
        return $this->validPaymentStatuses;
    }

    /**
     * Validate invoice data before creating or updating
     *
     * @param  array  $data  Invoice data to validate
     * @param  bool  $isUpdate  Whether this is for an update operation
     * @return array Validated data
     */
    protected function validateInvoiceData(array $data, bool $isUpdate = false): array
    {
        // For updates, required fields are not mandatory
        if (! $isUpdate) {
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required for incoming invoices');
            }

            if (empty($data['currency']['code'])) {
                throw new InvalidArgumentException('currency.code is required for incoming invoices');
            }

            if (empty($data['total'])) {
                throw new InvalidArgumentException('total is required for incoming invoices');
            }

            if (empty($data['total']['tax_exclusive']) && empty($data['total']['tax_inclusive'])) {
                throw new InvalidArgumentException('Either total.tax_exclusive or total.tax_inclusive is required');
            }
        }

        // Validate currency code if provided
        if (isset($data['currency']['code']) && ! in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: '.implode(', ', $this->validCurrencyCodes)
            );
        }

        return $data;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'add' => [
                'description' => 'Response contains the created invoice ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created invoice',
                    'data.type' => 'Resource type (always "incomingInvoice")',
                ],
            ],
            'info' => [
                'description' => 'Complete incoming invoice information',
                'fields' => [
                    'data.id' => 'Invoice UUID',
                    'data.title' => 'Invoice title',
                    'data.origin' => 'Origin of the invoice (user or peppolIncomingDocument)',
                    'data.supplier' => 'Supplier reference (type: company|contact, id)',
                    'data.document_number' => 'Invoice document number (nullable)',
                    'data.invoice_date' => 'Invoice date (nullable)',
                    'data.due_date' => 'Due date (nullable)',
                    'data.currency' => 'Currency object with code',
                    'data.total' => 'Total amounts (tax_exclusive and tax_inclusive)',
                    'data.company_entity' => 'Company entity reference',
                    'data.file' => 'Attached file reference (nullable)',
                    'data.payment_reference' => 'Payment reference (nullable)',
                    'data.review_status' => 'Review status (pending, approved, refused)',
                    'data.iban_number' => 'IBAN number (nullable)',
                    'data.payment_status' => 'Payment status (unknown, paid, partially_paid, not_paid)',
                ],
            ],
            'listPayments' => [
                'description' => 'Array of payment objects for the invoice',
                'fields' => [
                    'data[].id' => 'Payment UUID',
                    'data[].payment.amount' => 'Payment amount',
                    'data[].payment.currency' => 'Currency code',
                    'data[].paid_at' => 'Payment datetime',
                    'data[].payment_method' => 'Payment method reference (nullable)',
                    'data[].remark' => 'Optional remark (nullable)',
                    'meta.total.amount' => 'Total amount paid across all payments',
                ],
            ],
            'registerPayment' => [
                'description' => 'Response contains the created payment ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created payment',
                    'data.type' => 'Resource type',
                ],
            ],
        ];
    }
}
