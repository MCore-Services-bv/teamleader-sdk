<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\CRM;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\CRM\Addresses;
use McoreServices\TeamleaderSDK\Resources\CRM\BusinessTypes;
use McoreServices\TeamleaderSDK\Resources\CRM\Tags;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the read-only CRM reference resources: Addresses
 * (levelTwoAreas), BusinessTypes and Tags.
 *
 * levelTwoAreas.list and businessTypes.list take their criteria as top-level
 * body properties, not inside a filter object — these tests pin that shape.
 */
final class CrmReferencePayloadTest extends ResourceTestCase
{
    public function test_level_two_areas_send_country_and_language_top_level(): void
    {
        $this->resource(Addresses::class)->list(['country' => 'be', 'language' => 'NL']);

        $this->assertLastEndpoint('levelTwoAreas.list');
        $this->assertLastBody(['country' => 'BE', 'language' => 'nl']);
    }

    public function test_level_two_areas_require_country(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(Addresses::class)->list(['language' => 'nl']);
    }

    public function test_level_two_areas_reject_unknown_keys_and_options(): void
    {
        try {
            $this->resource(Addresses::class)->list(['country' => 'BE', 'term' => 'Antw']);
            $this->fail('term is not a levelTwoAreas.list parameter.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('term', $e->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not support pagination');

        try {
            $this->resource(Addresses::class)->list(['country' => 'BE'], ['page_size' => 50]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_business_types_send_country_top_level(): void
    {
        $this->resource(BusinessTypes::class)->list(['country' => 'nl']);

        $this->assertLastEndpoint('businessTypes.list');
        $this->assertLastBody(['country' => 'NL']);
    }

    public function test_business_types_reject_unknown_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(BusinessTypes::class)->list(['country' => 'BE', 'language' => 'nl']);
    }

    public function test_country_code_validators_return_booleans(): void
    {
        $this->assertTrue($this->resource(Addresses::class)->isValidCountryCode('be'));
        $this->assertFalse($this->resource(Addresses::class)->isValidLanguageCode('nld'));
        $this->assertFalse($this->resource(BusinessTypes::class)->isValidCountryCode('BEL'));
    }

    public function test_tags_sort_by_tag_ascending(): void
    {
        $this->resource(Tags::class)->list();

        $this->assertLastEndpoint('tags.list');
        $this->assertLastBodyHas('sort', [['field' => 'tag', 'order' => 'asc']]);
        $this->assertLastBodyMissing('filter');
    }

    public function test_tags_reject_descending_order(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(Tags::class)->list([], ['sort_order' => 'desc']);
    }

    public function test_tags_advertise_their_sort_field(): void
    {
        $this->assertSame(['tag'], array_keys($this->resource(Tags::class)->getAvailableSortFields()));
    }
}
