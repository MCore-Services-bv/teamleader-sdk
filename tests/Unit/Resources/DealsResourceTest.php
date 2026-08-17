<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Regression tests for the v2.1.2 Deals fixes.
 *
 * Three defects, all in list():
 *
 * 1. Filters were assigned to the body verbatim — buildFilters() existed on the
 *    class but was never called, so any key a caller invented reached the API,
 *    which ignores unknown filter keys and answers 200 with everything.
 * 2. Sideloads were written to the `include` body key. The API wants `includes`
 *    (plural) and silently ignores the singular form, so
 *    list([], ['include' => 'custom_fields']) returned deals with no custom
 *    fields. The fluent form was unaffected, so the same class gave two
 *    different answers.
 * 3. buildSort() was called but never defined on this class or its parents,
 *    making any sort option a fatal "call to undefined method".
 */
final class DealsResourceTest extends ResourceTestCase
{
    private Deals $deals;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deals = $this->resource(Deals::class);
    }

    // ---------------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------------

    public function test_supported_filters_reach_the_request(): void
    {
        $this->deals->list([
            'term' => 'roof',
            'phase_id' => 'phase-uuid',
        ]);

        $this->assertLastEndpoint('deals.list');
        $this->assertLastBodyHas('filter.term', 'roof');
        $this->assertLastBodyHas('filter.phase_id', 'phase-uuid');
    }

    public function test_unknown_filter_keys_throw(): void
    {
        // `tags` is the specific key that prompted this fix: the SDK once had
        // tag(), untag() and withTags() for endpoints Teamleader never exposed.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tags');

        try {
            $this->deals->list(['tags' => ['vip']]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_unknown_filter_message_lists_the_supported_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pipeline_ids');

        $this->deals->list(['banana' => true]);
    }

    public function test_status_string_is_coerced_to_an_array(): void
    {
        $this->deals->list(['status' => 'open']);

        $this->assertLastBodyHas('filter.status', ['open']);
    }

    public function test_ids_string_is_coerced_to_an_array(): void
    {
        $this->deals->list(['ids' => 'deal-uuid']);

        $this->assertLastBodyHas('filter.ids', ['deal-uuid']);
    }

    public function test_open_helper_filters_by_open_status(): void
    {
        $this->deals->open();

        $this->assertLastBodyHas('filter.status', ['open']);
    }

    public function test_for_customer_builds_a_customer_filter(): void
    {
        $this->deals->forCustomer('company', 'company-uuid');

        $this->assertLastBodyHas('filter.customer.type', 'company');
        $this->assertLastBodyHas('filter.customer.id', 'company-uuid');
    }

    public function test_for_customer_rejects_an_invalid_customer_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->deals->forCustomer('banana', 'some-uuid');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_no_filter_key_is_sent_when_no_filters_are_given(): void
    {
        $this->deals->list();

        $this->assertLastBodyMissing('filter');
    }

    // ---------------------------------------------------------------------
    // Sideloading
    // ---------------------------------------------------------------------

    public function test_include_option_is_sent_as_includes_plural(): void
    {
        $this->deals->list([], ['include' => 'custom_fields']);

        $this->assertLastBodyHas('includes', 'custom_fields');
        $this->assertLastBodyMissing('include');
    }

    public function test_include_array_is_sent_as_a_comma_separated_string(): void
    {
        $this->deals->list([], ['include' => ['custom_fields', 'responsible_user']]);

        $this->assertLastBodyHas('includes', 'custom_fields,responsible_user');
        $this->assertLastBodyMissing('include');
    }

    public function test_fluent_and_options_forms_produce_the_same_body(): void
    {
        $this->deals->list([], ['include' => 'custom_fields']);
        $viaOptions = $this->lastBody();

        $this->resource(Deals::class)->withCustomFields()->list();
        $viaFluent = $this->lastBody();

        $this->assertSame($viaOptions, $viaFluent);
    }

    public function test_info_sends_includes_plural(): void
    {
        $this->deals->info('deal-uuid', 'custom_fields');

        $this->assertLastEndpoint('deals.info');
        $this->assertLastBodyHas('id', 'deal-uuid');
        $this->assertLastBodyHas('includes', 'custom_fields');
    }

    // ---------------------------------------------------------------------
    // Sorting
    // ---------------------------------------------------------------------

    public function test_sorting_by_a_field_name_no_longer_fatals(): void
    {
        // Before v2.1.2 this raised Error: call to undefined method buildSort().
        $this->deals->list([], ['sort' => 'created_at']);

        $this->assertSame(
            [['field' => 'created_at', 'order' => 'desc']],
            $this->lastBody()['sort'] ?? null
        );
    }

    public function test_sort_order_is_honoured(): void
    {
        $this->deals->list([], ['sort' => 'weighted_value', 'sort_order' => 'asc']);

        $this->assertLastBodyHas('sort.0.order', 'asc');
    }

    public function test_sort_accepts_a_list_of_objects(): void
    {
        $this->deals->list([], [
            'sort' => [
                ['field' => 'created_at', 'order' => 'asc'],
                ['field' => 'weighted_value'],
            ],
        ]);

        $this->assertSame(
            [
                ['field' => 'created_at', 'order' => 'asc'],
                ['field' => 'weighted_value', 'order' => 'desc'],
            ],
            $this->lastBody()['sort'] ?? null
        );
    }

    public function test_sort_accepts_a_list_of_field_names(): void
    {
        $this->deals->list([], ['sort' => ['created_at', 'weighted_value']]);

        $this->assertLastBodyHas('sort.0.field', 'created_at');
        $this->assertLastBodyHas('sort.1.field', 'weighted_value');
    }

    public function test_unknown_sort_field_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field');

        try {
            $this->deals->list([], ['sort' => 'title']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_invalid_sort_order_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort order');

        $this->deals->list([], ['sort' => 'created_at', 'sort_order' => 'sideways']);
    }

    public function test_no_sort_key_is_sent_when_no_sort_is_requested(): void
    {
        $this->deals->list();

        $this->assertLastBodyMissing('sort');
    }

    // ---------------------------------------------------------------------
    // Pagination
    // ---------------------------------------------------------------------

    public function test_pagination_options_are_forwarded(): void
    {
        $this->deals->list([], ['page_size' => 100, 'page_number' => 2]);

        $this->assertLastBodyHas('page.size', 100);
        $this->assertLastBodyHas('page.number', 2);
    }

    public function test_no_page_key_is_sent_when_no_pagination_is_requested(): void
    {
        $this->deals->list();

        $this->assertLastBodyMissing('page');
    }

    // ---------------------------------------------------------------------
    // Write operations
    // ---------------------------------------------------------------------

    public function test_create_requires_a_customer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer is required');

        try {
            $this->deals->create(['title' => 'New deal']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_create_sends_the_payload_unchanged(): void
    {
        $payload = [
            'lead' => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
            'title' => 'New Business Deal',
        ];

        $this->deals->create($payload);

        $this->assertLastEndpoint('deals.create');
        $this->assertLastBody($payload);
    }

    public function test_update_adds_the_id_to_the_payload(): void
    {
        $this->deals->update('deal-uuid', ['title' => 'Updated']);

        $this->assertLastEndpoint('deals.update');
        $this->assertLastBodyHas('id', 'deal-uuid');
        $this->assertLastBodyHas('title', 'Updated');
    }

    public function test_move_requires_a_phase_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->deals->move('deal-uuid', '');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_lose_omits_optional_parameters_when_not_given(): void
    {
        $this->deals->lose('deal-uuid');

        $this->assertLastEndpoint('deals.lose');
        $this->assertLastBody(['id' => 'deal-uuid']);
    }

    public function test_lose_includes_the_reason_when_given(): void
    {
        $this->deals->lose('deal-uuid', 'reason-uuid', 'Price too high');

        $this->assertLastBodyHas('reason_id', 'reason-uuid');
        $this->assertLastBodyHas('extra_info', 'Price too high');
    }

    public function test_win_sends_only_the_id(): void
    {
        $this->deals->win('deal-uuid');

        $this->assertLastEndpoint('deals.win');
        $this->assertLastBody(['id' => 'deal-uuid']);
    }
}
