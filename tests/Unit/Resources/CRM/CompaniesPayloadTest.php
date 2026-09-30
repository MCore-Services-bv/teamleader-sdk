<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\CRM;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\CRM\Companies;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for Companies, against specification 1.221.0.
 *
 * Capability declarations are covered by Feature\CompaniesResourceTest; this
 * file covers what list(), info(), create() and update() actually send.
 */
final class CompaniesPayloadTest extends ResourceTestCase
{
    private Companies $companies;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companies = $this->resource(Companies::class);
    }

    // -- list ----------------------------------------------------------------

    public function test_supported_filters_reach_the_request(): void
    {
        $this->companies->list([
            'vat_number' => 'BE0123456789',
            'status' => 'active',
            'marketing_mails_consent' => 1,
        ]);

        $this->assertLastEndpoint('companies.list');
        $this->assertLastBodyHas('filter.vat_number', 'BE0123456789');
        $this->assertLastBodyHas('filter.status', 'active');
        $this->assertLastBodyHas('filter.marketing_mails_consent', true);
    }

    public function test_status_array_throws_instead_of_taking_the_first_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('one status at a time');

        try {
            $this->companies->list(['status' => ['active', 'deactivated']]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_name_filter_throws_with_guidance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("use 'term'");

        $this->companies->list(['name' => 'Acme']);
    }

    public function test_sort_is_validated_and_shaped(): void
    {
        $this->companies->list([], ['sort' => 'name']);

        $this->assertLastBodyHas('sort', [['field' => 'name', 'order' => 'asc']]);
    }

    public function test_unknown_sort_field_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->companies->list([], ['sort' => 'vat_number']);
    }

    public function test_list_accepts_only_custom_fields_include(): void
    {
        $this->companies->withCustomFields()->list();
        $this->assertLastBodyHas('includes', 'custom_fields');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('companies.list');

        $this->companies->list([], ['include' => 'related_contacts']);
    }

    // -- info ----------------------------------------------------------------

    public function test_info_accepts_related_includes(): void
    {
        $this->companies->withRelatedCompanies()->withRelatedContacts()->info('company-uuid');

        $this->assertLastEndpoint('companies.info');
        $this->assertLastBodyHas('includes', 'related_companies,related_contacts');
    }

    public function test_info_rejects_custom_fields_with_explanation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('custom_fields is a companies.list include');

        $this->companies->info('company-uuid', 'custom_fields');
    }

    // -- create / update -----------------------------------------------------

    public function test_create_requires_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->companies->create(['vat_number' => 'BE0123456789']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_create_accepts_invoicing_email_and_currency(): void
    {
        $this->companies->create([
            'name' => 'Acme',
            'emails' => [['type' => 'invoicing', 'email' => 'billing@acme.test']],
            'preferred_currency' => 'EUR',
            'price_list_id' => 'price-list-uuid',
        ]);

        $this->assertLastEndpoint('companies.add');
        $this->assertLastBodyHas('emails.0.type', 'invoicing');
        $this->assertLastBodyHas('price_list_id', 'price-list-uuid');
    }

    public function test_mobile_telephone_is_rejected_on_companies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('telephones[0].type');

        $this->companies->create([
            'name' => 'Acme',
            'telephones' => [['type' => 'mobile', 'number' => '+32 470 00 00 00']],
        ]);
    }

    public function test_unknown_currency_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('preferred_currency');

        $this->companies->update('company-uuid', ['preferred_currency' => 'AUD']);
    }

    public function test_unknown_write_field_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('companies.update does not accept: vat');

        $this->companies->update('company-uuid', ['vat' => 'BE0123456789']);
    }

    public function test_tag_endpoints(): void
    {
        $this->companies->tag('company-uuid', 'vip');
        $this->assertLastEndpoint('companies.tag');
        $this->assertLastBodyHas('tags', ['vip']);

        $this->companies->untag('company-uuid', ['vip']);
        $this->assertLastEndpoint('companies.untag');
    }
}
