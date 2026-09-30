<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

class IncomingCreditNotes extends ExpenseDocument
{
    /**
     * Body fields incomingCreditNotes.add accepts; incomingCreditNotes.update accepts the same
     * set plus `id`. From @teamleader/focus-api-specification v1.221.0.
     */
    public const WRITE_FIELDS = [
        'title', 'supplier_id', 'document_number', 'invoice_date', 'due_date', 'currency', 'total',
        'company_entity_id', 'file_id', 'payment_reference', 'iban_number',
    ];

    /** Keys `total` accepts on add / update */
    public const TOTAL_KEYS = ['tax_exclusive', 'tax_inclusive'];

    /** `payment_status` on incomingCreditNotes.info */
    public const PAYMENT_STATUSES = ['unknown', 'paid', 'not_paid'];

    protected string $description = 'Manage incoming credit notes from suppliers in Teamleader Focus';

    // `payment_status` values incomingCreditNotes.info returns
    protected array $validPaymentStatuses = self::PAYMENT_STATUSES;

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

    protected function writeFields(): array
    {
        return self::WRITE_FIELDS;
    }

    protected function totalKeys(): array
    {
        return self::TOTAL_KEYS;
    }

    protected function documentLabel(): string
    {
        return 'incoming credit note';
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
                    'meta.total.currency' => 'Currency of meta.total.amount (since specification 1.221.0)',
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
