<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

/**
 * Shared behaviour of the three expense documents: incoming invoices,
 * incoming credit notes and receipts.
 *
 * The Teamleader API gives all three the same twelve endpoints — add, update,
 * info, delete, approve, refuse, markAsPendingReview, sendToBookkeeping,
 * listPayments, registerPayment, removePayment, updatePayment — with the same
 * request shapes, differing only in their field lists: receipts have a
 * `receipt_date` instead of `invoice_date`, no `due_date`, `iban_number` or
 * `payment_reference`, and a total that is tax-inclusive only.
 *
 * Until v2.2.8 each resource carried its own ~550-line copy of this code, and
 * the copies had drifted: incoming invoices and credit notes required a
 * `total` the API does not require, receipts required `total.tax_inclusive`,
 * all three required a `payment` on updatePayment() where the API makes it
 * optional, and none checked field names. The differences now live in the
 * three constants each subclass declares.
 *
 * None of the three has a list endpoint — expenses.list lists all of them.
 */
abstract class ExpenseDocument extends Resource
{
    use ValidatesWritePayload;

    /** `currency.code` on add / update, and `payment.currency` on the payment endpoints */
    public const CURRENCIES = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK', 'EUR', 'GBP', 'INR', 'ISK',
        'JPY', 'MAD', 'MXN', 'NOK', 'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

    /** `review_status` on info responses */
    public const REVIEW_STATUSES = ['pending', 'approved', 'refused'];

    /** Body fields the payment endpoints accept, besides `id` */
    public const REGISTER_PAYMENT_FIELDS = ['payment', 'paid_at', 'payment_method_id', 'remark'];

    public const UPDATE_PAYMENT_FIELDS = ['payment_id', 'payment', 'paid_at', 'payment_method_id', 'remark'];

    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    // No list endpoint of their own — use expenses()->list()
    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = false;

    protected bool $supportsSideloading = false;

    protected array $availableIncludes = [];

    protected array $defaultIncludes = [];

    protected array $commonFilters = [];

    // Kept for backwards compatibility — see CURRENCIES / REVIEW_STATUSES
    protected array $validCurrencyCodes = self::CURRENCIES;

    protected array $validReviewStatuses = self::REVIEW_STATUSES;

    /**
     * `payment_status` values the info endpoint returns for this document.
     *
     * @var list<string>
     */
    protected array $validPaymentStatuses = [];

    /**
     * Body fields `{base}.add` accepts; `{base}.update` accepts the same plus `id`.
     *
     * @return list<string>
     */
    abstract protected function writeFields(): array;

    /**
     * Keys `total` may carry: tax_exclusive and/or tax_inclusive.
     *
     * @return list<string>
     */
    abstract protected function totalKeys(): array;

    /**
     * Singular, human-readable name for messages: "incoming invoice".
     */
    abstract protected function documentLabel(): string;

    /**
     * Create a new expense document
     *
     * `{base}.add` requires `title` and `currency.code`. Everything else —
     * including `total` — is optional.
     *
     * @param  array  $data  Document data
     * @return array Created document: data.type and data.id
     *
     * @throws InvalidArgumentException When a required field is missing, or a field or value is not accepted
     */
    public function add(array $data): array
    {
        $endpoint = $this->getBasePath().'.add';

        if (empty($data['title'])) {
            throw new InvalidArgumentException("title is required for {$this->documentLabel()}s");
        }

        if (empty($data['currency']['code'])) {
            throw new InvalidArgumentException("currency.code is required for {$this->documentLabel()}s");
        }

        $this->validateDocumentData($data, $this->writeFields(), $endpoint);

        return $this->api->request('POST', $endpoint, $data);
    }

    /**
     * Alias for add()
     *
     * @param  array  $data  Document data
     */
    public function create(array $data): array
    {
        return $this->add($data);
    }

    /**
     * Update an expense document
     *
     * @param  string  $id  Document UUID
     * @param  array  $data  Fields to update; null clears a nullable field
     *
     * @throws InvalidArgumentException When the ID is empty, or a field or value is not accepted
     */
    public function update(string $id, array $data): array
    {
        $this->requireId($id);

        $endpoint = $this->getBasePath().'.update';
        $this->validateDocumentData($data, $this->writeFields(), $endpoint);

        return $this->api->request('POST', $endpoint, array_merge(['id' => $id], $data));
    }

    /**
     * Get one expense document
     *
     * @param  string  $id  Document UUID
     * @param  mixed  $includes  Not supported — the info endpoint takes no includes
     *
     * @throws InvalidArgumentException When the ID is empty or includes are requested
     */
    public function info(string $id, $includes = null): array
    {
        $this->requireId($id);

        if (! empty($includes) || ! empty($this->getPendingIncludes())) {
            $this->applyPendingIncludes([]);

            throw new InvalidArgumentException("{$this->getBasePath()}.info accepts no includes.");
        }

        return $this->api->request('POST', $this->getBasePath().'.info', ['id' => $id]);
    }

    /**
     * Delete an expense document
     */
    public function delete(string $id): array
    {
        $this->requireId($id);

        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Approve an expense document
     */
    public function approve(string $id): array
    {
        $this->requireId($id);

        return $this->api->request('POST', $this->getBasePath().'.approve', ['id' => $id]);
    }

    /**
     * Refuse an expense document
     */
    public function refuse(string $id): array
    {
        $this->requireId($id);

        return $this->api->request('POST', $this->getBasePath().'.refuse', ['id' => $id]);
    }

    /**
     * Put an expense document back to pending review
     */
    public function markAsPendingReview(string $id): array
    {
        $this->requireId($id);

        return $this->api->request('POST', $this->getBasePath().'.markAsPendingReview', ['id' => $id]);
    }

    /**
     * Send an expense document to bookkeeping
     */
    public function sendToBookkeeping(string $id): array
    {
        $this->requireId($id);

        return $this->api->request('POST', $this->getBasePath().'.sendToBookkeeping', ['id' => $id]);
    }

    /**
     * List the payments registered on an expense document
     *
     * Returns `data[]` — id, payment {amount, currency}, paid_at,
     * payment_method, remark — and `meta.total` with the amount paid and, since
     * specification 1.221.0, its currency.
     */
    public function listPayments(string $id): array
    {
        $this->requireId($id);

        return $this->api->request('POST', $this->getBasePath().'.listPayments', ['id' => $id]);
    }

    /**
     * Register a payment
     *
     * @param  string  $id  Document UUID
     * @param  array  $payment  ['amount' => float, 'currency' => 'EUR']
     * @param  string  $paidAt  ISO 8601 date-time
     * @param  string|null  $paymentMethodId  Payment method UUID
     * @param  string|null  $remark  Free text
     * @return array Created payment: data.type and data.id
     *
     * @throws InvalidArgumentException When a required value is missing or invalid
     */
    public function registerPayment(
        string $id,
        array $payment,
        string $paidAt,
        ?string $paymentMethodId = null,
        ?string $remark = null
    ): array {
        $this->requireId($id);

        if ($paidAt === '') {
            throw new InvalidArgumentException('paid_at is required when registering a payment');
        }

        $this->validatePaymentData($payment);

        $data = ['id' => $id, 'payment' => $payment, 'paid_at' => $paidAt];

        if ($paymentMethodId !== null && $paymentMethodId !== '') {
            $data['payment_method_id'] = $paymentMethodId;
        }

        if ($remark !== null && $remark !== '') {
            $data['remark'] = $remark;
        }

        return $this->api->request('POST', $this->getBasePath().'.registerPayment', $data);
    }

    /**
     * Remove one payment
     *
     * @throws InvalidArgumentException When either ID is empty
     */
    public function removePayment(string $id, string $paymentId): array
    {
        $this->requireId($id);

        if ($paymentId === '') {
            throw new InvalidArgumentException('Payment ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.removePayment', [
            'id' => $id,
            'payment_id' => $paymentId,
        ]);
    }

    /**
     * Update one payment
     *
     * Only the document and payment IDs are required: pass null for
     * $payment to change only the date, method or remark. Before v2.2.8 the
     * payment amount was required on every update.
     *
     * @param  string  $id  Document UUID
     * @param  string  $paymentId  Payment UUID
     * @param  array|null  $payment  ['amount' => float, 'currency' => 'EUR'], or null to leave it
     * @param  string|null  $paidAt  ISO 8601 date-time
     * @param  string|null  $paymentMethodId  Payment method UUID
     * @param  string|null  $remark  Free text
     *
     * @throws InvalidArgumentException When an ID is empty or the payment is invalid
     */
    public function updatePayment(
        string $id,
        string $paymentId,
        ?array $payment = null,
        ?string $paidAt = null,
        ?string $paymentMethodId = null,
        ?string $remark = null
    ): array {
        $this->requireId($id);

        if ($paymentId === '') {
            throw new InvalidArgumentException('Payment ID is required');
        }

        $data = ['id' => $id, 'payment_id' => $paymentId];

        if ($payment !== null) {
            $this->validatePaymentData($payment);
            $data['payment'] = $payment;
        }

        if ($paidAt !== null && $paidAt !== '') {
            $data['paid_at'] = $paidAt;
        }

        if ($paymentMethodId !== null && $paymentMethodId !== '') {
            $data['payment_method_id'] = $paymentMethodId;
        }

        if ($remark !== null && $remark !== '') {
            $data['remark'] = $remark;
        }

        return $this->api->request('POST', $this->getBasePath().'.updatePayment', $data);
    }

    /**
     * Not available — there is no list endpoint for a single expense type
     *
     * @throws InvalidArgumentException Always
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new InvalidArgumentException(
            "{$this->getBasePath()} has no list endpoint. Use Teamleader::expenses()->list() with "
            ."the source_types filter, or info() for a single {$this->documentLabel()}."
        );
    }

    /**
     * @return list<string>
     */
    public function getValidCurrencyCodes(): array
    {
        return $this->validCurrencyCodes;
    }

    /**
     * @return list<string>
     */
    public function getValidReviewStatuses(): array
    {
        return $this->validReviewStatuses;
    }

    /**
     * @return list<string>
     */
    public function getValidPaymentStatuses(): array
    {
        return $this->validPaymentStatuses;
    }

    /**
     * Field names, the currency, and the shape of `total`
     *
     * @throws InvalidArgumentException
     */
    protected function validateDocumentData(array $data, array $fields, string $endpoint): void
    {
        $this->rejectUnknownFields($data, $fields, $endpoint);

        if (isset($data['currency'])) {
            if (! is_array($data['currency']) || ! isset($data['currency']['code'])) {
                throw new InvalidArgumentException("currency must be ['code' => 'EUR'] on {$endpoint}");
            }

            $this->assertEnum($data['currency']['code'], self::CURRENCIES, 'currency.code', $endpoint);
        }

        if (! isset($data['total'])) {
            return;
        }

        if (! is_array($data['total'])) {
            throw new InvalidArgumentException("total must be an object on {$endpoint}");
        }

        $unknown = array_diff(array_keys($data['total']), $this->totalKeys());

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                "{$endpoint} total accepts only ".implode(' and ', $this->totalKeys())
                .'. Passed: '.implode(', ', $unknown).'. The API would ignore it.'
            );
        }

        foreach ($data['total'] as $key => $amount) {
            if ($amount !== null && (! is_array($amount) || ! isset($amount['amount']) || ! is_numeric($amount['amount']))) {
                throw new InvalidArgumentException("total.{$key} must be ['amount' => number], or null");
            }
        }
    }

    /**
     * A payment is ['amount' => number, 'currency' => code]
     *
     * @throws InvalidArgumentException
     */
    protected function validatePaymentData(array $payment): void
    {
        if (! isset($payment['amount']) || ! is_numeric($payment['amount'])) {
            throw new InvalidArgumentException('Payment amount is required and must be numeric');
        }

        if (empty($payment['currency'])) {
            throw new InvalidArgumentException('Payment currency is required');
        }

        if (! in_array($payment['currency'], self::CURRENCIES, true)) {
            throw new InvalidArgumentException(
                'Invalid payment currency. Must be one of: '.implode(', ', self::CURRENCIES)
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function requireId(string $id): void
    {
        if ($id === '') {
            throw new InvalidArgumentException(ucfirst($this->documentLabel()).' ID is required');
        }
    }
}
