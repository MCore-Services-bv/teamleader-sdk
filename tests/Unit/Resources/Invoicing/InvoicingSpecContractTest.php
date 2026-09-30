<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Invoicing;

use McoreServices\TeamleaderSDK\Resources\Invoicing\Invoices;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Subscriptions;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of the Invoicing category, compared
 * against the specification fixture.
 */
#[Group('spec-contract')]
final class InvoicingSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<int|string>}>
     */
    public static function enums(): array
    {
        return [
            'invoices.draft invoice_content' => ['invoices.draft', 'invoice_content', Invoices::INVOICE_CONTENT],
            'invoices.updateBooked invoice_content' => ['invoices.updateBooked', 'invoice_content', Invoices::INVOICE_CONTENT],
            'subscriptions.create invoice_content' => ['subscriptions.create', 'invoice_content', Subscriptions::INVOICE_CONTENT],
            'subscriptions.create days_in_advance' => ['subscriptions.create', 'billing_cycle.days_in_advance', Subscriptions::DAYS_IN_ADVANCE],
            'subscriptions.create unit' => ['subscriptions.create', 'billing_cycle.periodicity.unit', array_keys(Subscriptions::PERIODS)],
            'subscriptions.create period' => ['subscriptions.create', 'billing_cycle.periodicity.period', array_values(array_unique(array_merge(...array_values(Subscriptions::PERIODS))))],
            'subscriptions.create payment_method' => ['subscriptions.create', 'invoice_generation.payment_method', Subscriptions::INVOICE_PAYMENT_METHODS],
            'subscriptions.create delivery type' => ['subscriptions.create', 'delivery_information.type', Subscriptions::DELIVERY_TYPES],
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
            'invoices.draft' => ['invoices.draft', Invoices::DRAFT_FIELDS],
            'invoices.update' => ['invoices.update', [...Invoices::UPDATE_FIELDS, 'id']],
            'invoices.updateBooked' => ['invoices.updateBooked', [...Invoices::UPDATE_BOOKED_FIELDS, 'id']],
            'subscriptions.create' => ['subscriptions.create', Subscriptions::WRITE_FIELDS],
            'subscriptions.update' => ['subscriptions.update', [...Subscriptions::WRITE_FIELDS, 'id']],
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

    public function test_subscription_required_fields_match(): void
    {
        $declared = self::spec()->endpoint('subscriptions.create')['request']['required'];
        $constant = Subscriptions::REQUIRED_ON_CREATE;

        sort($declared);
        sort($constant);

        $this->assertSame($declared, $constant);
    }

    public function test_invoice_includes_match(): void
    {
        foreach (['invoices.list', 'invoices.info'] as $endpoint) {
            $declared = self::spec()->endpoint($endpoint)['request']['includes'];
            $constant = Invoices::INCLUDES;

            sort($declared);
            sort($constant);

            $this->assertSame($declared, $constant, "{$endpoint} includes have drifted.");
        }
    }
}
