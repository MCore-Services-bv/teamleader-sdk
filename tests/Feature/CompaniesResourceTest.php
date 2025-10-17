<?php

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Resources\CRM\Companies;

class CompaniesResourceTest extends TestCase
{
    private TeamleaderSDK $sdk;
    private Companies $companies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sdk = new TeamleaderSDK();
        $this->sdk->setAccessToken('test_token');
        $this->companies = $this->sdk->companies();
    }

    /** @test */
    public function it_can_access_companies_resource(): void
    {
        $this->assertInstanceOf(Companies::class, $this->companies);
    }

    /** @test */
    public function it_has_correct_base_path(): void
    {
        $reflection = new \ReflectionClass($this->companies);
        $method = $reflection->getMethod('getBasePath');
        $method->setAccessible(true);

        $basePath = $method->invoke($this->companies);
        $this->assertEquals('companies', $basePath);
    }

    /** @test */
    public function it_supports_required_capabilities(): void
    {
        $capabilities = $this->companies->getCapabilities();

        $this->assertTrue($capabilities['supports_pagination']);
        $this->assertTrue($capabilities['supports_filtering']);
        $this->assertTrue($capabilities['supports_sideloading']);
        $this->assertTrue($capabilities['supports_creation']);
        $this->assertTrue($capabilities['supports_update']);
    }

    /** @test */
    public function it_has_sideloading_options(): void
    {
        $capabilities = $this->companies->getCapabilities();

        $this->assertIsArray($capabilities['available_includes']);
        $this->assertContains('addresses', $capabilities['available_includes']);
        $this->assertContains('responsible_user', $capabilities['available_includes']);
    }
}
