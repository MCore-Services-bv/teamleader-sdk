<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\General\CustomFields;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Regression tests for the v2.1.2 CustomFields fixes.
 *
 * The headline defect is the sale/deal mismatch: the API accepts `context: deal`
 * as a filter but returns `context: sale` in the response body, so any consumer
 * comparing a stored definition's context against 'deal' silently matched nothing.
 *
 * Alongside it: two helpers passed context values the API does not define,
 * forContext() validated nothing, and byType() sent a `type` filter the endpoint
 * has never supported — which was dropped, returning every definition.
 */
final class CustomFieldsResourceTest extends ResourceTestCase
{
    private CustomFields $customFields;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFields = $this->resource(CustomFields::class);
    }

    // ---------------------------------------------------------------------
    // sale → deal normalisation
    // ---------------------------------------------------------------------

    public function test_list_normalises_sale_context_to_deal(): void
    {
        $this->api->queueListResponse([
            ['id' => 'field-1', 'context' => 'sale', 'label' => 'Lead Source'],
            ['id' => 'field-2', 'context' => 'sale', 'label' => 'Margin'],
        ]);

        $response = $this->customFields->forDeals();

        $this->assertSame('deal', $response['data'][0]['context']);
        $this->assertSame('deal', $response['data'][1]['context']);
    }

    public function test_info_normalises_sale_context_to_deal(): void
    {
        $this->api->queueResponse([
            'data' => ['id' => 'field-1', 'context' => 'sale', 'label' => 'Lead Source'],
            'headers' => [],
        ]);

        $response = $this->customFields->info('field-1');

        $this->assertSame('deal', $response['data']['context']);
    }

    public function test_other_contexts_are_left_alone(): void
    {
        $this->api->queueListResponse([
            ['id' => 'field-1', 'context' => 'contact'],
            ['id' => 'field-2', 'context' => 'invoice'],
        ]);

        $response = $this->customFields->list();

        $this->assertSame('contact', $response['data'][0]['context']);
        $this->assertSame('invoice', $response['data'][1]['context']);
    }

    public function test_an_empty_data_array_passes_through_without_error(): void
    {
        $this->api->queueListResponse([]);

        $response = $this->customFields->list();

        $this->assertSame([], $response['data']);
    }

    public function test_a_response_without_data_passes_through_untouched(): void
    {
        $this->api->queueResponse(['error' => true, 'status_code' => 500]);

        $response = $this->customFields->list();

        $this->assertTrue($response['error']);
    }

    public function test_deal_is_what_gets_sent_as_the_filter(): void
    {
        // Normalisation is one-directional: `deal` goes out, `sale` comes back
        // and is rewritten. The filter must never become `sale`.
        $this->customFields->forDeals();

        $this->assertLastBodyHas('filter.context', 'deal');
    }

    // ---------------------------------------------------------------------
    // Context validation
    // ---------------------------------------------------------------------

    public function test_removed_helpers_no_longer_exist(): void
    {
        $this->assertFalse(method_exists($this->customFields, 'forQuotations'));
        $this->assertFalse(method_exists($this->customFields, 'forCreditnotes'));
    }

    public function test_for_context_rejects_quotation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid custom field context');

        try {
            $this->customFields->forContext('quotation');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_for_context_rejects_creditnote(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->customFields->forContext('creditnote');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_for_context_rejects_sale_and_explains_why(): void
    {
        // `sale` is what the API returns, not what it accepts. Rejecting it with
        // an explanation is better than quietly translating it to `deal`.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("use 'deal' instead");

        $this->customFields->forContext('sale');
    }

    public function test_for_sales_is_an_alias_for_for_deals(): void
    {
        $this->customFields->forSales();

        $this->assertLastBodyHas('filter.context', 'deal');
    }

    public function test_context_filter_is_validated_on_the_raw_list_call(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->customFields->list(['context' => 'quotation']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_create_rejects_an_invalid_context(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->customFields->create([
                'label' => 'Reference',
                'type' => 'single_line',
                'context' => 'quotation',
            ]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    // ---------------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------------

    public function test_type_filter_is_rejected_and_points_at_by_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('byType()');

        try {
            $this->customFields->list(['type' => 'single_line']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_unknown_filter_keys_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('banana');

        $this->customFields->list(['banana' => true]);
    }

    public function test_ids_string_is_coerced_to_an_array(): void
    {
        $this->customFields->byIds(['field-1', 'field-2']);

        $this->assertLastBodyHas('filter.ids', ['field-1', 'field-2']);
    }

    // ---------------------------------------------------------------------
    // byType — client-side
    // ---------------------------------------------------------------------

    public function test_by_type_filters_client_side(): void
    {
        $this->api->queueListResponse([
            ['id' => 'field-1', 'type' => 'single_line', 'context' => 'contact'],
            ['id' => 'field-2', 'type' => 'money', 'context' => 'sale'],
            ['id' => 'field-3', 'type' => 'single_line', 'context' => 'invoice'],
        ]);

        $result = $this->customFields->byType('single_line');

        $this->assertCount(2, $result['data']);
        $this->assertSame(2, $result['total_count']);
        $this->assertSame('field-1', $result['data'][0]['id']);
        $this->assertSame('field-3', $result['data'][1]['id']);
    }

    public function test_by_type_results_are_context_normalised(): void
    {
        $this->api->queueListResponse([
            ['id' => 'field-1', 'type' => 'money', 'context' => 'sale'],
        ]);

        $result = $this->customFields->byType('money');

        $this->assertSame('deal', $result['data'][0]['context']);
    }

    public function test_by_type_rejects_an_unknown_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->customFields->byType('banana');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    // ---------------------------------------------------------------------
    // all() paging
    // ---------------------------------------------------------------------

    public function test_all_stops_on_a_short_page(): void
    {
        $fullPage = array_map(
            fn (int $i) => ['id' => "field-{$i}", 'context' => 'contact'],
            range(1, 100)
        );

        $this->api->queueListResponse($fullPage);
        $this->api->queueListResponse([['id' => 'field-101', 'context' => 'contact']]);

        $result = $this->customFields->all();

        $this->assertSame(101, $result['total_count']);
        $this->assertRequestCount(2);
    }

    public function test_all_requests_the_page_size_it_was_given(): void
    {
        $this->api->queueListResponse([['id' => 'field-1', 'context' => 'contact']]);

        $this->customFields->all([], 50);

        $this->assertLastBodyHas('page.size', 50);
    }

    public function test_all_stops_immediately_on_an_empty_first_page(): void
    {
        $this->api->queueListResponse([]);

        $result = $this->customFields->all();

        $this->assertSame(0, $result['total_count']);
        $this->assertRequestCount(1);
    }

    // ---------------------------------------------------------------------
    // Sorting and sideloading
    // ---------------------------------------------------------------------

    public function test_sort_builds_the_object_shape(): void
    {
        $this->customFields->list([], ['sort' => 'label']);

        $this->assertSame(
            [['field' => 'label', 'order' => 'asc']],
            $this->lastBody()['sort'] ?? null
        );
    }

    public function test_unknown_sort_field_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field');

        $this->customFields->list([], ['sort' => 'created_at']);
    }

    public function test_info_rejects_sideloading(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not support sideloading');

        try {
            $this->customFields->info('field-1', 'custom_fields');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_info_sends_only_the_id(): void
    {
        $this->customFields->info('field-1');

        $this->assertLastEndpoint('customFieldDefinitions.info');
        $this->assertLastBody(['id' => 'field-1']);
    }

    // ---------------------------------------------------------------------
    // Pagination defaults
    // ---------------------------------------------------------------------

    public function test_pagination_defaults_are_always_sent(): void
    {
        $this->customFields->list();

        $this->assertLastBodyHas('page.size', 20);
        $this->assertLastBodyHas('page.number', 1);
    }
}
