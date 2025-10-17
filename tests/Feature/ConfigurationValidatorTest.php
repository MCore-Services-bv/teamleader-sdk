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

    public function testValidatesConfigurationSuccessfully(): void
    {
        $result = $this->validator->validate();

        $this->assertInstanceOf(\McoreServices\TeamleaderSDK\Services\ValidationResult::class, $result);
        $this->assertTrue($result->isValid());
    }

    public function testProvidesSummary(): void
    {
        $result = $this->validator->validate();

        $summary = $result->getSummary();
        $this->assertIsString($summary);
        $this->assertNotEmpty($summary);
    }

    public function testGeneratesConfigurationReport(): void
    {
        $report = $this->validator->generateReport();

        $this->assertArrayHasKey('overall_status', $report);
        $this->assertArrayHasKey('php_version', $report);
        $this->assertArrayHasKey('laravel_version', $report);
        $this->assertArrayHasKey('configuration_summary', $report);
    }

    public function testProvidesSuggestions(): void
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
