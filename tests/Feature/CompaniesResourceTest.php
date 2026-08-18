<?php

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use McoreServices\TeamleaderSDK\Resources\CRM\Companies;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\TestCase;

class CompaniesResourceTest extends TestCase
{
    private TeamleaderSDK $sdk;

    private Companies $companies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sdk = new TeamleaderSDK;
        $this->sdk->setAccessToken('test_token');
        $this->companies = $this->sdk->companies();
    }

    public function test_can_access_companies_resource(): void
    {
        $this->assertInstanceOf(Companies::class, $this->companies);
    }

    public function test_has_correct_base_path(): void
    {
        $reflection = new \ReflectionClass($this->companies);
        $method = $reflection->getMethod('getBasePath');
        $method->setAccessible(true);

        $basePath = $method->invoke($this->companies);
        $this->assertEquals('companies', $basePath);
    }

    public function test_supports_required_capabilities(): void
    {
        $capabilities = $this->companies->getCapabilities();

        $this->assertTrue($capabilities['supports_pagination']);
        $this->assertTrue($capabilities['supports_filtering']);
        $this->assertTrue($capabilities['supports_sideloading']);
        $this->assertTrue($capabilities['supports_creation']);
        $this->assertTrue($capabilities['supports_update']);
    }

    /**
     * companies.list accepts exactly one include: custom_fields.
     *
     * Until v2.1.2 this class declared seven — addresses, business_type,
     * responsible_user, added_by, tags, custom_fields and price_list — of which
     * six are not includes at all. Those fields are returned by default, so
     * requesting them looked like it worked: the API ignores unrecognised
     * include values and the data arrived regardless.
     *
     * The previous version of this test asserted the phantom values, which is
     * how they survived. Verified against
     *
     * @teamleader/focus-api-specification v1.197.0.
     */
    public function test_list_sideloading_options_match_the_api(): void
    {
        $capabilities = $this->companies->getCapabilities();

        $this->assertSame(['custom_fields'], $capabilities['available_includes']);
    }

    public function test_phantom_includes_are_not_advertised(): void
    {
        $includes = $this->companies->getCapabilities()['available_includes'];

        foreach (['addresses', 'business_type', 'responsible_user', 'added_by', 'tags', 'price_list'] as $phantom) {
            $this->assertNotContains(
                $phantom,
                $includes,
                "'{$phantom}' is not an include accepted by companies.list."
            );
        }
    }

    /**
     * No include is sent unless the caller asks for one.
     *
     * defaultIncludes was ['responsible_user', 'addresses'], so every companies
     * request carried two include values the API does not recognise.
     */
    public function test_no_default_includes_are_sent(): void
    {
        $this->assertSame([], $this->companies->getCapabilities()['default_includes']);
    }

    /**
     * price_list is returned automatically, not requested.
     *
     * Teamleader returns it on list and info whenever the account has access to
     * price lists, and null when no price list is set on the company. It was
     * never an include, and requesting it did nothing.
     */
    public function test_price_list_is_not_an_include(): void
    {
        $this->assertNotContains('price_list', $this->companies->getCapabilities()['available_includes']);
        $this->assertFalse(method_exists($this->companies, 'withPriceList'));
    }

    public function test_removed_fluent_include_methods_are_gone(): void
    {
        foreach (['withAddresses', 'withBusinessType', 'withResponsibleUser', 'withAddedBy', 'withCommonRelationships'] as $method) {
            $this->assertFalse(
                method_exists($this->companies, $method),
                "{$method}() requested an include the API does not accept and was removed in v2.1.2."
            );
        }
    }

    public function test_real_fluent_include_methods_exist(): void
    {
        $this->assertTrue(method_exists($this->companies, 'withCustomFields'));
        $this->assertTrue(method_exists($this->companies, 'withRelatedCompanies'));
        $this->assertTrue(method_exists($this->companies, 'withRelatedContacts'));
    }
}
