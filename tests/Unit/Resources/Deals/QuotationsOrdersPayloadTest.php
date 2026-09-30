<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Deals\Orders;
use McoreServices\TeamleaderSDK\Resources\Deals\Quotations;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for Quotations and Orders, against specification 1.221.0.
 */
final class QuotationsOrdersPayloadTest extends ResourceTestCase
{
    // -- Quotations: expiry include ------------------------------------------

    /**
     * v2.2.2 removed the `expiry` include because neither endpoint declares an
     * includes property. Both responses document `expiry` as returned when
     * `includes=expiry` is requested, so it is offered again.
     */
    public function test_expiry_include_on_info_and_list(): void
    {
        $quotations = $this->resource(Quotations::class);

        $quotations->withExpiry()->info('quotation-uuid');
        $this->assertLastBody(['id' => 'quotation-uuid', 'includes' => 'expiry']);

        $quotations->list([], ['include' => 'expiry']);
        $this->assertLastBodyHas('includes', 'expiry');
    }

    public function test_other_includes_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quotations.info');

        $this->resource(Quotations::class)->info('quotation-uuid', 'custom_fields');
    }

    // -- Quotations: writes --------------------------------------------------

    public function test_create_needs_only_a_deal(): void
    {
        $this->resource(Quotations::class)->create(['deal_id' => 'deal-uuid', 'name' => 'Offerte 521']);

        $this->assertLastEndpoint('quotations.create');
        $this->assertLastBody(['deal_id' => 'deal-uuid', 'name' => 'Offerte 521']);
    }

    public function test_create_without_deal_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(Quotations::class)->create(['name' => 'Offerte']);
    }

    public function test_unknown_field_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quotations.update does not accept: title');

        $this->resource(Quotations::class)->update('quotation-uuid', ['title' => 'Offerte']);
    }

    public function test_expiry_action_is_checked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expiry.action_after_expiry');

        $this->resource(Quotations::class)->update('quotation-uuid', [
            'expiry' => ['expires_after' => '2026-12-31', 'action_after_expiry' => 'delete'],
        ]);
    }

    public function test_send_does_not_require_from(): void
    {
        $this->resource(Quotations::class)->send([
            'quotations' => ['quotation-uuid'],
            'recipients' => ['to' => [['customer' => ['type' => 'contact', 'id' => 'contact-uuid'], 'email_address' => 'jan@example.com']]],
            'subject' => 'Uw offerte',
            'content' => 'In bijlage',
            'language' => 'nl',
        ]);

        $this->assertLastEndpoint('quotations.send');
        $this->assertLastBodyMissing('from');
    }

    public function test_send_language_is_checked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('language');

        $this->resource(Quotations::class)->send([
            'quotations' => ['quotation-uuid'],
            'recipients' => ['to' => [['email_address' => 'jan@example.com']]],
            'subject' => 'Offerte',
            'content' => 'In bijlage',
            'language' => 'vl',
        ]);
    }

    public function test_statuses_match_the_response_enum(): void
    {
        $this->assertSame(['open', 'accepted', 'refused', 'expired'], Quotations::STATUSES);
    }

    // -- Orders --------------------------------------------------------------

    public function test_order_includes_are_checked_on_list_and_info(): void
    {
        $this->resource(Orders::class)->with('custom_fields')->info('order-uuid');
        $this->assertLastBody(['id' => 'order-uuid', 'includes' => 'custom_fields']);

        $this->expectException(InvalidArgumentException::class);

        $this->resource(Orders::class)->list([], ['include' => 'lines']);
    }
}
