<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class IncomingCreditNotes extends Resource
{
    protected string $description = 'Manage incoming credit notes from suppliers in Teamleader Focus';

    // Resource capabilities - Incoming credit notes support create, update, delete, and info
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
        'not_paid',
    ];

    // Usage examples specific to incoming credit notes
    protected array $usageExamples = [
        'create_basic' => [
            'description' => 'Create a basic incoming credit note',
            'code' => '$creditNote = $teamleader->incomingCreditNotes()->add([\'title\' => \'Credit Note\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_exclusive\' => [\'amount\' => 500.00]]]);',
        ],
        'approve_creditnote' => [
            'description' => 'Approve a credit note',
            'code' => '$teamleader->incomingCreditNotes()->approve(\'creditnote-uuid\');',
        ],
        'refuse_creditnote' => [
            'description' => 'Refuse a credit note',
            'code' => '$teamleader->incomingCreditNotes()->refuse(\'creditnote-uuid\');',
        ],
        'send_to_bookkeeping' => [
            'description' => 'Send credit note to bookkeeping',
            'code' => '$teamleader->incomingCreditNotes()->sendToBookkeeping(\'creditnote-uuid\');',
        ],
        'delete_creditnote' => [
            'description' => 'Delete a credit note',
            'code' => '$teamleader->incomingCreditNotes()->delete(\'creditnote-uuid\');',
        ],
        'list_payments' => [
            'description' => 'List all payments for a credit note',
            'code' => '$payments = $teamleader->incomingCreditNotes()->listPayments(\'creditnote-uuid\');',
        ],
        'register_payment' => [
            'description' => 'Register a payment for a credit note',
            'code' => '$payment = $teamleader->incomingCreditNotes()->registerPayment(\'creditnote-uuid\', [\'amount\' => 500.00, \'currency\' => \'EUR\'], \'2024-01-20T10:00:00+00:00\');',
        ],
        'remove_payment' => [
            'description' => 'Remove a specific payment from a credit note',
            'code' => '$teamleader->incomingCreditNotes()->removePayment(\'creditnote-uuid\', \'payment-uuid\');',
        ],
        'update_payment' => [
            'description' => 'Update an existing payment on a credit note',
            'code' => '$teamleader->incomingCreditNotes()->updatePayment(\'creditnote-uuid\', \'payment-uuid\', [\'amount\' => 450.00, \'currency\' => \'EUR\']);',
        ],
    ];

    /**
     * Get the base path for the incoming credit notes resource
     */
    protected function getBasePath(): string
    {
        return 'incomingCreditNotes';
    }

    /**
     * Create a new incoming credit note
     *
     * @param  array  $data  Credit note data
     * @return array Created credit note response
     *
     * @throws InvalidArgumentException When required fields are missing
     */
    public function add(array $data): array
    {
        $data = $this->validateCreditNoteData($data);

        return $this->api->request('POST', $this->getBasePath().'.add', $data);
    }

    /**
     * Alias for add()
     *
     * @param  array  $data  Credit note data
     * @return array Created credit note response
     */
    public function create(array $data): array
    {
        return $this->add($data);
    }

    /**
     * Update an existing incoming credit note
     *
     * @param  string  $id  Credit note UUID
     * @param  array  $data  Credit note data to update
     * @return array Update response
     *
     * @throws InvalidArgumentException When ID is empty or data is invalid
     */
    public function update(string $id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        $data = $this->validateCreditNoteData($data, true);

        $params = array_merge(['id' => $id], $data);

        return $this->api->request('POST', $this->getBasePath().'.update', $params);
    }

    /**
     * Get information about a specific incoming credit note
     *
     * @param  string  $id  Credit note UUID
     * @param  mixed  $includes  Not used for incoming credit notes
     * @return array Credit note information including payment_status and iban_number
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function info(string $id, $includes = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.info', ['id' => $id]);
    }

    /**
     * Delete an incoming credit note
     *
     * @param  string  $id  Credit note UUID
     * @return array Delete response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function delete(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Approve an incoming credit note
     *
     * @param  string  $id  Credit note UUID
     * @return array Approval response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function approve(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.approve', ['id' => $id]);
    }

    /**
     * Refuse an incoming credit note
     *
     * @param  string  $id  Credit note UUID
     * @return array Refusal response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function refuse(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.refuse', ['id' => $id]);
    }

    /**
     * Mark an incoming credit note as pending review
     *
     * @param  string  $id  Credit note UUID
     * @return array Response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function markAsPendingReview(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.markAsPendingReview', ['id' => $id]);
    }

    /**
     * Send an incoming credit note to bookkeeping
     *
     * @param  string  $id  Credit note UUID
     * @return array Response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function sendToBookkeeping(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.sendToBookkeeping', ['id' => $id]);
    }

    /**
     * List payments for an incoming credit note
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
     * @param  string  $id  Credit note UUID
     * @return array List of payments with meta.total
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function listPayments(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.listPayments', ['id' => $id]);
    }

    /**
     * Register a payment for an incoming credit note
     *
     * @param  string  $id  Credit note UUID
     * @param  array  $payment  Payment data — must contain 'amount' (number) and 'currency' (CurrencyCode)
     * @param  string  $paidAt  ISO 8601 datetime of when the payment was made
     * @param  string|null  $paymentMethodId  Optional payment method UUID
     * @param  string|null  $remark  Optional remark/note
     * @return array Created payment response with data.type and data.id
     *
     * @throws InvalidArgumentException When required fields are missing or invalid
     */
    public function registerPayment(
        string $id,
        array $payment,
        string $paidAt,
        ?string $paymentMethodId = null,
        ?string $remark = null
    ): array {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        if (empty($paidAt)) {
            throw new InvalidArgumentException('paid_at is required when registering a payment');
        }

        $this->validatePaymentData($payment);

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
     * Remove a specific payment from an incoming credit note
     *
     * @param  string  $id  Credit note UUID
     * @param  string  $paymentId  Payment UUID to remove
     * @return array Response
     *
     * @throws InvalidArgumentException When ID or payment ID is empty
     */
    public function removePayment(string $id, string $paymentId): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
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
     * Update an existing payment on an incoming credit note
     *
     * @param  string  $id  Credit note UUID
     * @param  string  $paymentId  Payment UUID to update
     * @param  array  $payment  Payment data — must contain 'amount' (number) and 'currency' (CurrencyCode)
     * @param  string|null  $paidAt  Optional ISO 8601 datetime of when the payment was made
     * @param  string|null  $paymentMethodId  Optional payment method UUID
     * @param  string|null  $remark  Optional remark/note
     * @return array Response
     *
     * @throws InvalidArgumentException When required fields are missing or invalid
     */
    public function updatePayment(
        string $id,
        string $paymentId,
        array $payment,
        ?string $paidAt = null,
        ?string $paymentMethodId = null,
        ?string $remark = null
    ): array {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        if (empty($paymentId)) {
            throw new InvalidArgumentException('Payment ID is required');
        }

        $this->validatePaymentData($payment);

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
     * List method is not supported for incoming credit notes
     *
     * @throws InvalidArgumentException
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new InvalidArgumentException(
            'The list method is not supported for incoming credit notes. Use info() to get a specific credit note.'
        );
    }

    /**
     * Get valid currency codes for incoming credit notes
     *
     * @return array Array of valid currency codes
     */
    public function getValidCurrencyCodes(): array
    {
        return $this->validCurrencyCodes;
    }

    /**
     * Get valid review statuses for incoming credit notes
     *
     * @return array Array of valid review statuses
     */
    public function getValidReviewStatuses(): array
    {
        return $this->validReviewStatuses;
    }

    /**
     * Get valid payment statuses for incoming credit notes
     *
     * @return array Array of valid payment statuses
     */
    public function getValidPaymentStatuses(): array
    {
        return $this->validPaymentStatuses;
    }

    /**
     * Validate credit note data before creating or updating
     *
     * @param  array  $data  Credit note data to validate
     * @param  bool  $isUpdate  Whether this is for an update operation
     * @return array Validated data
     */
    protected function validateCreditNoteData(array $data, bool $isUpdate = false): array
    {
        if (! $isUpdate) {
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

        if (isset($data['currency']['code']) && ! in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: '.implode(', ', $this->validCurrencyCodes)
            );
        }

        return $data;
    }

    /**
     * Validate payment data (amount and currency are required)
     *
     * @param  array  $payment  Payment data to validate
     *
     * @throws InvalidArgumentException When required payment fields are missing or invalid
     */
    protected function validatePaymentData(array $payment): void
    {
        if (! isset($payment['amount']) || ! is_numeric($payment['amount'])) {
            throw new InvalidArgumentException('Payment amount is required and must be numeric');
        }

        if (empty($payment['currency'])) {
            throw new InvalidArgumentException('Payment currency is required');
        }

        if (! in_array($payment['currency'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid payment currency. Must be one of: '.implode(', ', $this->validCurrencyCodes)
            );
        }
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'add' => [
                'description' => 'Response contains the created credit note reference',
                'fields' => [
                    'data.type' => 'Resource type',
                    'data.id' => 'UUID of the created credit note',
                ],
            ],
            'info' => [
                'description' => 'Complete incoming credit note information',
                'fields' => [
                    'data.id' => 'Credit note UUID',
                    'data.title' => 'Credit note title',
                    'data.origin' => 'Origin object',
                    'data.supplier' => 'Supplier reference (nullable)',
                    'data.document_number' => 'Document/reference number (nullable)',
                    'data.invoice_date' => 'Invoice date (nullable)',
                    'data.due_date' => 'Payment due date (nullable)',
                    'data.currency' => 'Currency object with code',
                    'data.total' => 'Total amounts object',
                    'data.total.tax_exclusive' => 'Tax-exclusive total (nullable) with amount',
                    'data.total.tax_inclusive' => 'Tax-inclusive total (nullable) with amount',
                    'data.company_entity' => 'Company entity reference with type and id',
                    'data.file' => 'Attached file reference (nullable) with type and id',
                    'data.payment_reference' => 'Payment reference (nullable)',
                    'data.review_status' => 'Review status: pending, approved, or refused',
                    'data.iban_number' => 'IBAN number (nullable)',
                    'data.payment_status' => 'Payment status: unknown, paid, or not_paid',
                ],
            ],
            'listPayments' => [
                'description' => 'Array of payments for the credit note',
                'fields' => [
                    'data' => 'Array of payment objects',
                    'data[].id' => 'Payment UUID',
                    'data[].payment.amount' => 'Payment amount (number)',
                    'data[].payment.currency' => 'Payment currency code',
                    'data[].paid_at' => 'Payment datetime (ISO 8601)',
                    'data[].payment_method' => 'Payment method reference (nullable) with type and id',
                    'data[].remark' => 'Payment remark (nullable)',
                    'meta.total.amount' => 'Total amount across all payments',
                ],
            ],
            'registerPayment' => [
                'description' => 'Response contains the created payment reference',
                'fields' => [
                    'data.type' => 'Resource type',
                    'data.id' => 'UUID of the created payment',
                ],
            ],
        ];
    }
}
