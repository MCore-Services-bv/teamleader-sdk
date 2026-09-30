<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

class Receipts extends ExpenseDocument
{
    /**
     * Body fields receipts.add accepts; receipts.update accepts the same
     * set plus `id`. From @teamleader/focus-api-specification v1.221.0.
     */
    public const WRITE_FIELDS = [
        'title', 'supplier_id', 'document_number', 'receipt_date', 'currency', 'total',
        'company_entity_id', 'file_id',
    ];

    /** Keys `total` accepts on add / update */
    public const TOTAL_KEYS = ['tax_inclusive'];

    /** `payment_status` on receipts.info */
    public const PAYMENT_STATUSES = ['unknown', 'paid', 'not_paid'];

    protected string $description = 'Manage expense receipts in Teamleader Focus';

    // `payment_status` values receipts.info returns
    protected array $validPaymentStatuses = self::PAYMENT_STATUSES;

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
        return 'receipt';
    }
}
