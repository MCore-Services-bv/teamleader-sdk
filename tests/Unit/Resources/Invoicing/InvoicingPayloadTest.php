<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Creditnotes;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Invoices;
use McoreServices\TeamleaderSDK\Resources\Invoicing\PaymentMethods;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Subscriptions;
use McoreServices\TeamleaderSDK\Resources\Invoicing\TaxRates;
use McoreServices\TeamleaderSDK\Resources\Invoicing\WithholdingTaxRates;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the Invoicing category, against specification 1.221.0.
 */
final class InvoicingPayloadTest extends ResourceTestCase
{
    private function lineItem(array $overrides = []): array
    {
        return array_merge(['quantity' => 1, 'description' => 'Consultancy', 'tax_rate_id' => 'tax-rate-uuid'], $overrides);
    }

    private function draft(array $overrides = []): array
    {
        return array_merge([
            'invoicee' => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
            'department_id' => 'department-uuid',
            'payment_term' => ['type' => 'cash'],
            'grouped_lines' => [['line_items' => [$this->lineItem()]]],
        ], $overrides);
    }

    private function subscription(array $overrides = []): array
    {
        return array_merge([
            'invoicee' => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
            'department_id' => 'department-uuid',
            'starts_on' => '2026-10-01',
            'billing_cycle' => ['periodicity' => ['unit' => 'month', 'period' => 1], 'days_in_advance' => 7],
            'title' => 'Maintenance',
            'grouped_lines' => [['line_items' => [$this->lineItem()]]],
            'payment_term' => ['type' => 'cash'],
            'invoice_generation' => [
                'action' => 'book_and_send',
                'sending_methods' => [['method' => 'peppol'], ['method' => 'email']],
            ],
        ], $overrides);
    }

    // -- Invoices ------------------------------------------------------------

    public function test_a_line_item_without_unit_price_is_accepted(): void
    {
        $this->resource(Invoices::class)->create($this->draft(['invoice_content' => 'services']));

        $this->assertLastEndpoint('invoices.draft');
        $this->assertLastBodyMissing('grouped_lines.0.line_items.0.unit_price');
        $this->assertLastBodyHas('invoice_content', 'services');
    }

    public function test_invoice_content_is_checked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invoice_content');

        $this->resource(Invoices::class)->create($this->draft(['invoice_content' => 'products']));
    }

    public function test_update_booked_rejects_fields_only_update_accepts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invoices.updateBooked does not accept: currency');

        try {
            $this->resource(Invoices::class)->updateBooked('invoice-uuid', ['currency' => ['code' => 'EUR']]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_update_accepts_them(): void
    {
        $this->resource(Invoices::class)->update('invoice-uuid', ['currency' => ['code' => 'EUR'], 'delivery_date' => null]);

        $this->assertLastEndpoint('invoices.update');
        $this->assertLastBodyHas('currency.code', 'EUR');
    }

    /**
     * A field name as the sort made foreach() iterate a string until v2.2.7.
     */
    public function test_sort_by_field_name(): void
    {
        $this->resource(Invoices::class)->list([], ['sort' => 'invoice_date']);

        $this->assertLastBodyHas('sort', [['field' => 'invoice_date', 'order' => 'desc']]);
    }

    public function test_unknown_filter_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(Invoices::class)->list(['paid' => true]);
    }

    public function test_includes_on_list_and_info(): void
    {
        $this->resource(Invoices::class)->list([], ['include' => ['late_fees', 'totals.interest']]);
        $this->assertLastBodyHas('includes', 'late_fees,totals.interest');

        $this->resource(Invoices::class)->with('totals.fixed_late_fee')->info('invoice-uuid');
        $this->assertLastBody(['id' => 'invoice-uuid', 'includes' => 'totals.fixed_late_fee']);

        $this->expectException(InvalidArgumentException::class);

        $this->resource(Invoices::class)->info('invoice-uuid', 'custom_fields');
    }

    // -- Credit notes --------------------------------------------------------

    public function test_credit_note_downloads_all_four_formats(): void
    {
        foreach (['pdf', 'ubl/e-fff', 'ubl/peppol_bis_3', 'ubl/xrechnung'] as $format) {
            $this->resource(Creditnotes::class)->download('credit-note-uuid', $format);
            $this->assertLastBodyHas('format', $format);
        }
    }

    public function test_credit_note_unknown_filter_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(Creditnotes::class)->list(['status' => 'booked']);
    }

    // -- Subscriptions -------------------------------------------------------

    public function test_subscription_create(): void
    {
        $this->resource(Subscriptions::class)->create($this->subscription());

        $this->assertLastEndpoint('subscriptions.create');
        $this->assertLastBodyHas('invoice_generation.sending_methods.1.method', 'email');
    }

    public function test_subscription_requires_department(): void
    {
        $payload = $this->subscription();
        unset($payload['department_id']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('department_id');

        $this->resource(Subscriptions::class)->create($payload);
    }

    public function test_sending_methods_must_include_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must always include email');

        $this->resource(Subscriptions::class)->create($this->subscription([
            'invoice_generation' => ['action' => 'book_and_send', 'sending_methods' => [['method' => 'peppol']]],
        ]));
    }

    public function test_period_depends_on_unit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('a monthly cycle takes 1, 2, 3, 4, 6');

        $this->resource(Subscriptions::class)->create($this->subscription([
            'billing_cycle' => ['periodicity' => ['unit' => 'month', 'period' => 5], 'days_in_advance' => 7],
        ]));
    }

    public function test_days_in_advance_is_checked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('days_in_advance');

        $this->resource(Subscriptions::class)->update('subscription-uuid', [
            'billing_cycle' => ['periodicity' => ['unit' => 'year', 'period' => 1], 'days_in_advance' => 10],
        ]);
    }

    /**
     * A field name as the sort reached array_map() as a string until v2.2.7 —
     * a TypeError.
     */
    public function test_subscription_sort_by_field_name(): void
    {
        $this->resource(Subscriptions::class)->list(['status' => 'active'], ['sort' => 'title']);

        $this->assertLastBodyHas('filter.status', ['active']);
        $this->assertLastBodyHas('sort', [['field' => 'title', 'order' => 'asc']]);
    }

    // -- Reference resources -------------------------------------------------

    public function test_tax_rate_sort_by_field_name(): void
    {
        $this->resource(TaxRates::class)->list([], ['sort' => 'rate']);

        $this->assertLastBodyHas('sort', [['field' => 'rate', 'order' => 'asc']]);
    }

    public function test_payment_method_status_is_wrapped(): void
    {
        $this->resource(PaymentMethods::class)->list(['status' => 'active']);

        $this->assertLastBodyHas('filter.status', ['active']);
    }

    /**
     * list() ignored its arguments until v2.2.7.
     */
    public function test_withholding_tax_rates_filter_by_department(): void
    {
        $this->resource(WithholdingTaxRates::class)->forDepartment('department-uuid');

        $this->assertLastEndpoint('withholdingTaxRates.list');
        $this->assertLastBodyHas('filter.department_id', 'department-uuid');
    }
}
