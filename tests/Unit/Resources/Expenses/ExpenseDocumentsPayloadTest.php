<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Expenses;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Expenses\BookkeepingSubmissions;
use McoreServices\TeamleaderSDK\Resources\Expenses\Expenses;
use McoreServices\TeamleaderSDK\Resources\Expenses\IncomingCreditNotes;
use McoreServices\TeamleaderSDK\Resources\Expenses\IncomingInvoices;
use McoreServices\TeamleaderSDK\Resources\Expenses\Receipts;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Payload tests for the Expenses category, against specification 1.221.0.
 *
 * The three expense documents share one implementation since v2.2.8
 * (ExpenseDocument); the shared behaviour is tested once per class through
 * the data provider, so a future divergence shows up as a failure on one of
 * the three.
 */
final class ExpenseDocumentsPayloadTest extends ResourceTestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function documents(): array
    {
        return [
            'incoming invoices' => [IncomingInvoices::class, 'incomingInvoices'],
            'incoming credit notes' => [IncomingCreditNotes::class, 'incomingCreditNotes'],
            'receipts' => [Receipts::class, 'receipts'],
        ];
    }

    // -- shared behaviour ----------------------------------------------------

    /**
     * `total` is optional; before v2.2.8 all three required it.
     */
    #[DataProvider('documents')]
    public function test_add_needs_only_title_and_currency(string $class, string $base): void
    {
        $this->resource($class)->add(['title' => 'Office supplies', 'currency' => ['code' => 'EUR']]);

        $this->assertLastEndpoint("{$base}.add");
        $this->assertLastBody(['title' => 'Office supplies', 'currency' => ['code' => 'EUR']]);
    }

    #[DataProvider('documents')]
    public function test_add_requires_title_and_currency(string $class, string $base): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->resource($class)->add(['currency' => ['code' => 'EUR']]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    #[DataProvider('documents')]
    public function test_unknown_fields_throw(string $class, string $base): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("{$base}.update does not accept: supplier");

        $this->resource($class)->update('document-uuid', ['supplier' => 'company-uuid']);
    }

    #[DataProvider('documents')]
    public function test_currency_is_checked(string $class, string $base): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency.code');

        $this->resource($class)->update('document-uuid', ['currency' => ['code' => 'AUD']]);
    }

    #[DataProvider('documents')]
    public function test_status_endpoints(string $class, string $base): void
    {
        foreach (['approve', 'refuse', 'markAsPendingReview', 'sendToBookkeeping', 'delete', 'listPayments'] as $action) {
            $this->resource($class)->{$action}('document-uuid');

            $this->assertLastEndpoint("{$base}.{$action}");
            $this->assertLastBody(['id' => 'document-uuid']);
        }
    }

    /**
     * Only the two IDs are required; before v2.2.8 the payment amount was.
     */
    #[DataProvider('documents')]
    public function test_update_payment_without_an_amount(string $class, string $base): void
    {
        $this->resource($class)->updatePayment('document-uuid', 'payment-uuid', null, null, null, 'Corrected');

        $this->assertLastEndpoint("{$base}.updatePayment");
        $this->assertLastBody(['id' => 'document-uuid', 'payment_id' => 'payment-uuid', 'remark' => 'Corrected']);
    }

    #[DataProvider('documents')]
    public function test_register_payment(string $class, string $base): void
    {
        $this->resource($class)->registerPayment('document-uuid', ['amount' => 12.5, 'currency' => 'EUR'], '2026-09-30T10:00:00+00:00', 'method-uuid');

        $this->assertLastEndpoint("{$base}.registerPayment");
        $this->assertLastBodyHas('payment_method_id', 'method-uuid');
    }

    #[DataProvider('documents')]
    public function test_list_points_to_expenses(string $class, string $base): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expenses()->list()');

        $this->resource($class)->list();
    }

    // -- per-document differences --------------------------------------------

    public function test_receipt_total_is_tax_inclusive_only(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('receipts.add total accepts only tax_inclusive');

        $this->resource(Receipts::class)->add([
            'title' => 'Lunch',
            'currency' => ['code' => 'EUR'],
            'total' => ['tax_exclusive' => ['amount' => 40]],
        ]);
    }

    public function test_receipts_have_no_due_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('due_date');

        $this->resource(Receipts::class)->update('receipt-uuid', ['due_date' => '2026-10-31']);
    }

    public function test_incoming_invoice_accepts_both_totals_and_a_null_clear(): void
    {
        $this->resource(IncomingInvoices::class)->update('invoice-uuid', [
            'total' => ['tax_exclusive' => ['amount' => 100], 'tax_inclusive' => null],
            'due_date' => null,
        ]);

        $this->assertLastBodyHas('total.tax_exclusive.amount', 100);
        $this->assertNull($this->lastBody()['due_date']);
    }

    public function test_incoming_invoice_payment_statuses_include_credited(): void
    {
        $this->assertContains('credited', $this->resource(IncomingInvoices::class)->getValidPaymentStatuses());
    }

    // -- Expenses (list) -------------------------------------------------------

    /**
     * unpaid() sent `unpaid`, which is not a payment status, until v2.2.8.
     */
    public function test_unpaid_sends_real_statuses(): void
    {
        $this->resource(Expenses::class)->unpaid();

        $this->assertLastBodyHas('filter.payment_statuses', ['not_paid', 'partially_paid']);
        $this->assertLastBodyHas('includes', 'pagination');
    }

    public function test_sort_by_field_name(): void
    {
        $this->resource(Expenses::class)->list([], ['sort' => 'supplier_name', 'sort_order' => 'asc']);

        $this->assertLastBodyHas('sort', [['field' => 'supplier_name', 'order' => 'asc']]);
    }

    public function test_filter_enums_are_checked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('filter.payment_statuses[]');

        $this->resource(Expenses::class)->list(['payment_statuses' => 'unpaid']);
    }

    public function test_unknown_filter_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(Expenses::class)->list(['status' => 'approved']);
    }

    public function test_between_needs_both_ends(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('needs start and end');

        $this->resource(Expenses::class)->list(['document_date' => ['operator' => 'between', 'start' => '2026-01-01']]);
    }

    public function test_is_empty_takes_no_operands(): void
    {
        $this->resource(Expenses::class)->list(['paid_at' => ['operator' => 'is_empty']]);

        $this->assertLastBodyHas('filter.paid_at', ['operator' => 'is_empty']);
    }

    // -- Bookkeeping submissions -----------------------------------------------

    /**
     * forInvoice() and forCreditNote() sent snake_case subject types the API
     * does not accept until v2.2.8.
     */
    public function test_subject_types_are_camel_case(): void
    {
        $submissions = $this->resource(BookkeepingSubmissions::class);

        $submissions->forInvoice('invoice-uuid');
        $this->assertLastBodyHas('filter.subject.type', 'incomingInvoice');

        $submissions->forCreditNote('credit-note-uuid');
        $this->assertLastBodyHas('filter.subject.type', 'incomingCreditNote');

        $submissions->forReceipt('receipt-uuid');
        $this->assertLastBodyHas('filter.subject.type', 'receipt');
    }

    public function test_legacy_subject_types_are_translated(): void
    {
        $this->resource(BookkeepingSubmissions::class)->forDocument('invoice-uuid', 'incoming_invoice');

        $this->assertLastBodyHas('filter.subject.type', 'incomingInvoice');
    }

    public function test_unknown_subject_type_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(BookkeepingSubmissions::class)->forDocument('invoice-uuid', 'invoice');
    }
}
