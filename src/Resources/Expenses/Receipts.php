<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Receipts extends Resource
{
    protected string $description = 'Manage expense receipts in Teamleader Focus';

    // Resource capabilities - Receipts support create, update, delete, info, and payment management
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

    // Usage examples specific to receipts
    protected array $usageExamples = [
        'create_basic' => [
            'description' => 'Create a basic receipt',
            'code' => '$receipt = $teamleader->receipts()->add([\'title\' => \'Office Lunch\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_inclusive\' => [\'amount\' => 45.50]]]);',
        ],
        'create_complete' => [
            'description' => 'Create a complete receipt with all details',
            'code' => '$receipt = $teamleader->receipts()->add([\'title\' => \'Business Dinner\', \'supplier_id\' => \'uuid\', \'document_number\' => \'REC-001\', \'receipt_date\' => \'2024-01-15\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_inclusive\' => [\'amount\' => 125.00]]]);',
        ],
        'get_info' => [
            'description' => 'Get receipt details',
            'code' => '$receipt = $teamleader->receipts()->info(\'receipt-uuid\');',
        ],
        'update_receipt' => [
            'description' => 'Update an existing receipt',
            'code' => '$teamleader->receipts()->update(\'receipt-uuid\', [\'title\' => \'Updated Title\', \'receipt_date\' => \'2024-01-16\']);',
        ],
        'approve_receipt' => [
            'description' => 'Approve a receipt',
            'code' => '$teamleader->receipts()->approve(\'receipt-uuid\');',
        ],
        'refuse_receipt' => [
            'description' => 'Refuse a receipt',
            'code' => '$teamleader->receipts()->refuse(\'receipt-uuid\');',
        ],
        'mark_pending_review' => [
            'description' => 'Mark a receipt as pending review',
            'code' => '$teamleader->receipts()->markAsPendingReview(\'receipt-uuid\');',
        ],
        'send_to_bookkeeping' => [
            'description' => 'Send receipt to bookkeeping',
            'code' => '$teamleader->receipts()->sendToBookkeeping(\'receipt-uuid\');',
        ],
        'list_payments' => [
            'description' => 'List payments for a receipt',
            'code' => '$payments = $teamleader->receipts()->listPayments(\'receipt-uuid\');',
        ],
        'register_payment' => [
            'description' => 'Register a payment for a receipt',
            'code' => '$teamleader->receipts()->registerPayment(\'receipt-uuid\', [\'amount\' => 45.50, \'currency\' => \'EUR\'], \'2024-01-15T10:00:00Z\');',
        ],
        'remove_payment' => [
            'description' => 'Remove a payment from a receipt',
            'code' => '$teamleader->receipts()->removePayment(\'receipt-uuid\', \'payment-uuid\');',
        ],
        'update_payment' => [
            'description' => 'Update a payment on a receipt',
            'code' => '$teamleader->receipts()->updatePayment(\'receipt-uuid\', \'payment-uuid\', [\'amount\' => 50.00, \'currency\' => \'EUR\']);',
        ],
        'delete_receipt' => [
            'description' => 'Delete a receipt',
            'code' => '$teamleader->receipts()->delete(\'receipt-uuid\');',
        ],
    ];

    /**
     * Get the base path for the receipts resource
     */
    protected function getBasePath(): string
    {
        return 'receipts';
    }

    /**
     * Create a new receipt
     *
     * @param  array  $data  Receipt data
     * @return array Created receipt response
     *
     * @throws InvalidArgumentException When required fields are missing
     */
    public function add(array $data): array
    {
        // Validate required fields
        if (empty($data['title'])) {
            throw new InvalidArgumentException('title is required for receipts');
        }

        if (empty($data['currency']['code'])) {
            throw new InvalidArgumentException('currency.code is required for receipts');
        }

        if (empty($data['total']['tax_inclusive'])) {
            throw new InvalidArgumentException('total.tax_inclusive is required for receipts');
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
     * @param  array  $data  Receipt data
     */
    public function create(array $data): array
    {
        return $this->add($data);
    }

    /**
     * Update an existing receipt
     *
     * @param  string  $id  Receipt UUID
     * @param  array  $data  Data to update
     * @return array Update response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function update(string $id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
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
     * Get information about a specific receipt
     *
     * @param  string  $id  Receipt UUID
     * @param  mixed  $includes  Not used for receipts
     * @return array Receipt information
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function info(string $id, $includes = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.info', ['id' => $id]);
    }

    /**
     * Delete a receipt
     *
     * @param  string  $id  Receipt UUID
     * @return array Delete response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function delete(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Approve a receipt
     *
     * @param  string  $id  Receipt UUID
     * @return array Approval response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function approve(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.approve', ['id' => $id]);
    }

    /**
     * Refuse a receipt
     *
     * @param  string  $id  Receipt UUID
     * @return array Refusal response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function refuse(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.refuse', ['id' => $id]);
    }

    /**
     * Mark a receipt as pending review
     *
     * @param  string  $id  Receipt UUID
     * @return array Response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function markAsPendingReview(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.markAsPendingReview', ['id' => $id]);
    }

    /**
     * Send a receipt to bookkeeping for processing
     *
     * @param  string  $id  Receipt UUID
     * @return array Response
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function sendToBookkeeping(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.sendToBookkeeping', ['id' => $id]);
    }

    /**
     * List all payments for a receipt
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
     * @param  string  $id  Receipt UUID
     * @return array List of payments with meta totals
     *
     * @throws InvalidArgumentException When ID is empty
     */
    public function listPayments(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.listPayments', ['id' => $id]);
    }

    /**
     * Register a payment for a receipt
     *
     * @param  string  $id  Receipt UUID
     * @param  array  $payment  Payment details with required 'amount' (float) and 'currency' (string)
     * @param  string  $paidAt  ISO 8601 datetime when the payment was made
     * @param  string|null  $paymentMethodId  Optional payment method UUID
     * @param  string|null  $remark  Optional remark
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
            throw new InvalidArgumentException('Receipt ID is required');
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
     * Remove a specific payment from a receipt
     *
     * @param  string  $id  Receipt UUID
     * @param  string  $paymentId  Payment UUID to remove
     * @return array Response
     *
     * @throws InvalidArgumentException When ID or payment ID is empty
     */
    public function removePayment(string $id, string $paymentId): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Receipt ID is required');
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
     * Update an existing payment on a receipt
     *
     * @param  string  $id  Receipt UUID
     * @param  string  $paymentId  Payment UUID to update
     * @param  array  $payment  Updated payment details with required 'amount' (float) and 'currency' (string)
     * @param  string|null  $paidAt  Optional ISO 8601 datetime override
     * @param  string|null  $paymentMethodId  Optional payment method UUID
     * @param  string|null  $remark  Optional remark
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
            throw new InvalidArgumentException('Receipt ID is required');
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
     * List method is not supported for receipts
     *
     * @throws InvalidArgumentException
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new InvalidArgumentException(
            'The list method is not supported for receipts. Use info() to get a specific receipt.'
        );
    }

    /**
     * Get valid currency codes for receipts
     *
     * @return array Array of valid currency codes
     */
    public function getValidCurrencyCodes(): array
    {
        return $this->validCurrencyCodes;
    }

    /**
     * Get valid review statuses for receipts
     *
     * @return array Array of valid review statuses
     */
    public function getValidReviewStatuses(): array
    {
        return $this->validReviewStatuses;
    }

    /**
     * Get valid payment statuses for receipts
     *
     * @return array Array of valid payment statuses
     */
    public function getValidPaymentStatuses(): array
    {
        return $this->validPaymentStatuses;
    }

    /**
     * Validate receipt data before creating or updating
     *
     * @param  array  $data  Receipt data to validate
     * @param  bool  $isUpdate  Whether this is for an update operation
     * @return array Validated data
     */
    protected function validateReceiptData(array $data, bool $isUpdate = false): array
    {
        // For updates, required fields are not mandatory
        if (! $isUpdate) {
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required');
            }

            if (empty($data['currency']['code'])) {
                throw new InvalidArgumentException('currency.code is required');
            }

            if (empty($data['total']['tax_inclusive'])) {
                throw new InvalidArgumentException('total.tax_inclusive is required for receipts');
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
}
