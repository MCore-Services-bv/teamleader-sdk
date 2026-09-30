<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\CRM;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\CRM\Contacts;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for Contacts, against specification 1.221.0.
 *
 * v2.2.4 fixes covered here:
 *
 * - `company_id => null` was dropped with the other empty values, so the
 *   "contacts linked to no company" filter returned every contact
 * - unknown filter keys were dropped without a word, where Companies threw
 * - sort fields were never validated
 * - contacts.add requires last_name; first_name alone passed client-side
 * - unknown write fields and invalid enum values reached the API
 */
final class ContactsPayloadTest extends ResourceTestCase
{
    private Contacts $contacts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contacts = $this->resource(Contacts::class);
    }

    // -- list: filters ---------------------------------------------------------

    public function test_supported_filters_reach_the_request(): void
    {
        $this->contacts->list([
            'term' => 'Jan',
            'status' => 'active',
            'tags' => 'vip, partner',
            'ids' => 'contact-uuid',
        ]);

        $this->assertLastEndpoint('contacts.list');
        $this->assertLastBodyHas('filter.term', 'Jan');
        $this->assertLastBodyHas('filter.status', 'active');
        $this->assertLastBodyHas('filter.tags', ['vip', 'partner']);
        $this->assertLastBodyHas('filter.ids', ['contact-uuid']);
    }

    public function test_null_company_id_is_sent_as_null(): void
    {
        $this->contacts->withoutCompany();

        $body = $this->lastBody();

        $this->assertArrayHasKey('filter', $body);
        $this->assertArrayHasKey('company_id', $body['filter']);
        $this->assertNull($body['filter']['company_id']);
    }

    public function test_other_null_filters_are_still_skipped(): void
    {
        $this->contacts->list(['term' => null, 'company_id' => 'company-uuid']);

        $this->assertLastBodyMissing('filter.term');
        $this->assertLastBodyHas('filter.company_id', 'company-uuid');
    }

    public function test_unknown_filter_keys_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'name'");

        try {
            $this->contacts->list(['name' => 'Jan']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_email_filter_string_becomes_a_primary_object(): void
    {
        $this->contacts->byEmail('jan@example.com');

        $this->assertLastBodyHas('filter.email', ['type' => 'primary', 'email' => 'jan@example.com']);
    }

    public function test_email_filter_rejects_a_non_primary_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('filter.email.type');

        $this->contacts->list(['email' => ['type' => 'invoicing', 'email' => 'jan@example.com']]);
    }

    public function test_status_outside_the_enum_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->contacts->list(['status' => 'archived']);
    }

    // -- list: sort, page, includes ------------------------------------------

    public function test_sort_goes_out_as_objects(): void
    {
        $this->contacts->list([], ['sort' => 'updated_at', 'sort_order' => 'desc']);

        $this->assertLastBodyHas('sort', [['field' => 'updated_at', 'order' => 'desc']]);
    }

    public function test_unknown_sort_field_throws_before_the_request(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field: last_name');

        try {
            $this->contacts->list([], ['sort' => 'last_name']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_pagination_is_always_sent(): void
    {
        $this->contacts->list([], ['page_size' => 50, 'page_number' => 3]);

        $this->assertLastBodyHas('page', ['size' => 50, 'number' => 3]);
    }

    public function test_includes_go_out_plural(): void
    {
        $this->contacts->list([], ['include' => 'custom_fields']);

        $this->assertLastBodyHas('includes', 'custom_fields');
        $this->assertLastBodyMissing('include');
    }

    public function test_fluent_and_option_includes_are_merged_once(): void
    {
        $this->contacts->withCustomFields()->list([], ['includes' => 'custom_fields']);

        $this->assertLastBodyHas('includes', 'custom_fields');
    }

    public function test_unknown_include_throws_and_does_not_leak(): void
    {
        try {
            $this->contacts->with('price_list')->list();
            $this->fail('price_list is not a contacts.list include.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('price_list', $e->getMessage());
        }

        $this->contacts->list();

        $this->assertLastBodyMissing('includes');
    }

    public function test_info_rejects_includes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->contacts->info('contact-uuid', 'custom_fields');
    }

    // -- create / update -----------------------------------------------------

    public function test_create_requires_last_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('last_name');

        try {
            $this->contacts->create(['first_name' => 'Jan']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_create_sends_to_contacts_add(): void
    {
        $this->contacts->create([
            'last_name' => 'Peeters',
            'emails' => [['type' => 'primary', 'email' => 'jan@example.com']],
            'telephones' => [['type' => 'mobile', 'number' => '+32 470 00 00 00']],
            'gender' => 'male',
        ]);

        $this->assertLastEndpoint('contacts.add');
        $this->assertLastBodyHas('last_name', 'Peeters');
        $this->assertLastBodyHas('telephones.0.type', 'mobile');
    }

    public function test_unknown_write_field_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('contacts.update does not accept: company_id');

        try {
            $this->contacts->update('contact-uuid', ['company_id' => 'company-uuid']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_invoicing_email_is_rejected_on_contacts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('emails[0].type');

        $this->contacts->create([
            'last_name' => 'Peeters',
            'emails' => [['type' => 'invoicing', 'email' => 'jan@example.com']],
        ]);
    }

    public function test_invalid_gender_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('gender');

        $this->contacts->update('contact-uuid', ['gender' => 'other']);
    }

    public function test_null_clears_price_list(): void
    {
        $this->contacts->update('contact-uuid', ['price_list_id' => null]);

        $this->assertLastEndpoint('contacts.update');
        $this->assertArrayHasKey('price_list_id', $this->lastBody());
        $this->assertNull($this->lastBody()['price_list_id']);
    }

    public function test_link_endpoints(): void
    {
        $this->contacts->linkToCompany('contact-uuid', 'company-uuid', ['position' => 'CEO', 'decision_maker' => true]);
        $this->assertLastEndpoint('contacts.linkToCompany');
        $this->assertLastBodyHas('decision_maker', true);

        $this->contacts->unlinkFromCompany('contact-uuid', 'company-uuid');
        $this->assertLastEndpoint('contacts.unlinkFromCompany');
    }
}
