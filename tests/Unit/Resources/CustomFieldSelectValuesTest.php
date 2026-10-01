<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\General\CustomFields;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Teamleader takes a select field's option label as its value and refuses the
 * option id ("has an invalid single selection value") — found in the Nova
 * Credit migration, where it cost two failed runs. options() and
 * selectValue() resolve either form to the label before anything is sent.
 */
final class CustomFieldSelectValuesTest extends ResourceTestCase
{
    private CustomFields $customFields;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFields = $this->resource(CustomFields::class);
    }

    private function queueSelectField(string $type = 'single_select'): void
    {
        $this->api->queueResponse(['data' => [
            'id' => 'field-1',
            'context' => 'deal',
            'type' => $type,
            'label' => 'Kredietvorm',
            'configuration' => ['options' => [
                ['id' => 'opt-woon', 'value' => 'Woonkrediet'],
                ['id' => 'opt-hypo', 'value' => 'Hypothecair'],
                ['id' => 'opt-pers', 'value' => 'Persoonlijke lening'],
            ]],
        ], 'headers' => []]);
    }

    public function test_options_maps_labels_to_option_ids(): void
    {
        $this->queueSelectField();

        $this->assertSame(
            ['Woonkrediet' => 'opt-woon', 'Hypothecair' => 'opt-hypo', 'Persoonlijke lening' => 'opt-pers'],
            $this->customFields->options('field-1')
        );
        $this->assertSame('customFieldDefinitions.info', $this->api->lastEndpoint());
    }

    public function test_options_are_read_once_per_field(): void
    {
        $this->queueSelectField();

        $this->customFields->options('field-1');
        $this->customFields->selectValue('field-1', 'Woonkrediet');
        $this->customFields->selectValue('field-1', 'opt-hypo');

        $this->assertSame(1, $this->api->callCount());
    }

    public function test_select_value_turns_an_option_id_into_its_label(): void
    {
        $this->queueSelectField();

        $this->assertSame('Woonkrediet', $this->customFields->selectValue('field-1', 'opt-woon'));
    }

    public function test_select_value_keeps_a_label(): void
    {
        $this->queueSelectField();

        $this->assertSame('Woonkrediet', $this->customFields->selectValue('field-1', 'Woonkrediet'));
    }

    public function test_multi_select_takes_and_returns_a_list(): void
    {
        $this->queueSelectField('multi_select');

        $this->assertSame(
            ['Hypothecair', 'Persoonlijke lening'],
            $this->customFields->selectValue('field-1', ['opt-hypo', 'Persoonlijke lening'])
        );
    }

    public function test_an_unknown_value_throws_naming_the_options(): void
    {
        $this->queueSelectField();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'Leasing' is not an option of custom field field-1. Options: 'Woonkrediet', 'Hypothecair', 'Persoonlijke lening'.");

        $this->customFields->selectValue('field-1', 'Leasing');
    }

    public function test_a_field_without_options_is_refused(): void
    {
        $this->api->queueResponse(['data' => ['id' => 'field-2', 'type' => 'single_line', 'context' => 'deal'], 'headers' => []]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only single_select and multi_select fields have options');

        $this->customFields->options('field-2');
    }
}
