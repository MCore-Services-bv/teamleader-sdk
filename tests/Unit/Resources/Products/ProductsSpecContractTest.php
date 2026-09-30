<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Products;

use McoreServices\TeamleaderSDK\Resources\Products\Products;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of products.*, compared against the
 * specification fixture.
 *
 * products.add was missing its fields entirely from the fixture until the
 * v2.2.9 generator fix: they sit in a oneOf ("Add Product by Name" / "by
 * Code") beside the top-level properties.
 */
#[Group('spec-contract')]
final class ProductsSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    public function test_write_fields_match(): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('products.add')['request']['properties'], Products::ADD_FIELDS);
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('products.update')['request']['properties'], [...Products::UPDATE_FIELDS, 'id']);
    }

    public function test_enums_match(): void
    {
        foreach (['products.add', 'products.update'] as $endpoint) {
            $enums = self::spec()->endpoint($endpoint)['request']['enums'];

            $this->assertEqualsCanonicalizing($enums['selling_price.currency'], Products::CURRENCIES);
            $this->assertEqualsCanonicalizing($enums['purchase_price.currency'], Products::CURRENCIES);
            $this->assertEqualsCanonicalizing($enums['configuration.stock_threshold.action'], Products::STOCK_THRESHOLD_ACTIONS);
        }
    }

    public function test_includes_match(): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('products.info')['request']['includes'], Products::INFO_INCLUDES);
        $this->assertSame([], self::spec()->endpoint('products.list')['request']['includes']);
    }
}
