<?php

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Services\ConfigurationValidator;

class ConfigurationValidatorTest extends TestCase
{
    private ConfigurationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ConfigurationValidator();
    }

    /** @test */
    public function it_validates_configuration_successfully(): void
    {
        $result = $this->validator->validate();

        $this->assertInstanceOf(\McoreServices\TeamleaderSDK\Services\ValidationResult::class, $result);
        $this->assertTrue($result->isValid());
    }

    /** @test */
    public function it_provides_summary(): void
    {
        $result = $this->validator->validate();

        $summary = $result->getSummary();
        $this->assertIsString($summary);
        $this->assertNotEmpty($summary);
    }

    /** @test */
    public function it_generates_configuration_report(): void
    {
        $report = $this->validator->generateReport();

        $this->assertArrayHasKey('overall_status', $report);
        $this->assertArrayHasKey('php_version', $report);
        $this->assertArrayHasKey('laravel_version', $report);
        $this->assertArrayHasKey('configuration_summary', $report);
    }

    /** @test */
    public function it_provides_suggestions(): void
    {
        $suggestions = $this->validator->getSuggestions();

        $this->assertIsArray($suggestions);

        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('title', $suggestion);
            $this->assertArrayHasKey('description', $suggestion);
        }
    }
}
