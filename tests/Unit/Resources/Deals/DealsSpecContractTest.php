<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Deals;

use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Resources\Deals\Phases;
use McoreServices\TeamleaderSDK\Resources\Deals\Pipelines;
use McoreServices\TeamleaderSDK\Resources\Deals\Quotations;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of the Deals category, compared against
 * the specification fixture. Values come from contract.json, never from
 * literals here.
 */
#[Group('spec-contract')]
final class DealsSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function enums(): array
    {
        return [
            'deals.create lead.customer.type' => ['deals.create', 'lead.customer.type', Deals::CUSTOMER_TYPES],
            'deals.create estimated_value.currency' => ['deals.create', 'estimated_value.currency', Deals::CURRENCIES],
            'deals.create currency.code' => ['deals.create', 'currency.code', Deals::CURRENCIES],
            'deals.list filter.status[]' => ['deals.list', 'filter.status[]', Deals::STATUSES],
            'deals.list filter.customer.type' => ['deals.list', 'filter.customer.type', Deals::CUSTOMER_TYPES],
            'dealPhases.create unit' => ['dealPhases.create', 'requires_attention_after.unit', Phases::ATTENTION_UNITS],
            'dealPhases.create follow_up_actions[]' => ['dealPhases.create', 'follow_up_actions[]', Phases::FOLLOW_UP_ACTIONS],
            'dealPipelines.list filter.status[]' => ['dealPipelines.list', 'filter.status[]', Pipelines::STATUSES],
            'quotations.create discounts[].type' => ['quotations.create', 'discounts[].type', Quotations::DISCOUNT_TYPES],
            'quotations.create expiry.action_after_expiry' => ['quotations.create', 'expiry.action_after_expiry', Quotations::EXPIRY_ACTIONS],
            'quotations.send language' => ['quotations.send', 'language', Quotations::SEND_LANGUAGES],
            'quotations.send from.sender.type' => ['quotations.send', 'from.sender.type', Quotations::SENDER_TYPES],
            'quotations.send recipients.to[].customer.type' => ['quotations.send', 'recipients.to[].customer.type', Quotations::RECIPIENT_TYPES],
        ];
    }

    #[DataProvider('enums')]
    public function test_enum_matches_the_specification(string $endpoint, string $path, array $constant): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "{$endpoint} no longer declares an enum at {$path}.");

        sort($declared);
        sort($constant);

        $this->assertSame($declared, $constant, "{$path} on {$endpoint} has drifted from the specification.");
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function writeFields(): array
    {
        return [
            'deals.create' => ['deals.create', [...Deals::UPDATE_FIELDS, 'phase_id']],
            'deals.update' => ['deals.update', [...Deals::UPDATE_FIELDS, 'id']],
            'dealPhases.create' => ['dealPhases.create', Phases::CREATE_FIELDS],
            'dealPhases.update' => ['dealPhases.update', Phases::UPDATE_FIELDS],
            'quotations.create' => ['quotations.create', [...Quotations::UPDATE_FIELDS, 'deal_id']],
            'quotations.update' => ['quotations.update', [...Quotations::UPDATE_FIELDS, 'id']],
            'quotations.send' => ['quotations.send', Quotations::SEND_FIELDS],
        ];
    }

    #[DataProvider('writeFields')]
    public function test_write_fields_match_the_specification(string $endpoint, array $fields): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['properties'];

        sort($declared);
        sort($fields);

        $this->assertSame($declared, $fields, "{$endpoint} fields have drifted from the specification.");
    }

    public function test_quotation_statuses_match_the_response_enum(): void
    {
        $declared = self::spec()->endpoint('quotations.info')['response']['fields']['data.status'];

        $this->assertSame('string, enum: '.implode('|', Quotations::STATUSES), $declared);
    }

    public function test_quotations_still_name_expiry_only_in_the_response(): void
    {
        $request = self::spec()->endpoint('quotations.info')['request'];

        $this->assertContains(
            'expiry',
            $request['response_includes'],
            'quotations.info no longer documents includes=expiry. Re-check Quotations::$availableIncludes.'
        );

        if ($request['declares_includes']) {
            $this->markTestIncomplete(
                'quotations.info now declares an includes request property — the specification '
                .'contradiction is resolved; trim the note on Quotations::$supportsSideloading.'
            );
        }
    }
}
