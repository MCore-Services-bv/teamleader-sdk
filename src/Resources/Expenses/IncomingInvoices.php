<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

class IncomingInvoices extends ExpenseDocument
{
    /**
     * Body fields incomingInvoices.add accepts; incomingInvoices.update accepts the same
     * set plus `id`. From @teamleader/focus-api-specification v1.221.0.
     */
    public const WRITE_FIELDS = [
        'title', 'supplier_id', 'document_number', 'invoice_date', 'due_date', 'currency', 'total',
        'company_entity_id', 'file_id', 'payment_reference', 'iban_number',
    ];

    /** Keys `total` accepts on add / update */
    public const TOTAL_KEYS = ['tax_exclusive', 'tax_inclusive'];

    /** `payment_status` on incomingInvoices.info */
    public const PAYMENT_STATUSES = ['unknown', 'paid', 'partially_paid', 'credited', 'not_paid'];

    protected string $description = 'Manage incoming invoices from suppliers in Teamleader Focus';

    // `payment_status` values incomingInvoices.info returns. Until v2.2.8 this
    // missed `credited`.
    protected array $validPaymentStatuses = self::PAYMENT_STATUSES;

    protected array $usageExamples = [
        'create_basic' => [
            'description' => 'Create a basic incoming invoice',
            'code' => '$invoice = $teamleader->incomingInvoices()->add([\'title\' => \'Invoice\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_exclusive\' => [\'amount\' => 500.00]]]);',
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
        return 'incoming invoice';
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
                    'data.payment_status' => 'Payment status (unknown, paid, partially_paid, credited, not_paid)',
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
                    'meta.total.currency' => 'Currency of meta.total.amount (since specification 1.221.0)',
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
