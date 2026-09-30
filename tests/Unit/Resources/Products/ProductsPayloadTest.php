<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Products;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Products\Categories;
use McoreServices\TeamleaderSDK\Resources\Products\PriceLists;
use McoreServices\TeamleaderSDK\Resources\Products\Products;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the Products category, against specification 1.221.0.
 */
final class ProductsPayloadTest extends ResourceTestCase
{
    private function expectRejected(callable $call, ?string $message = null): void
    {
        $this->expectException(InvalidArgumentException::class);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }

        try {
            $call();
        } finally {
            $this->assertNoRequestMade();
        }
    }

    // -- list ------------------------------------------------------------------

    public function test_search(): void
    {
        $this->resource(Products::class)->search('cookie');

        $this->assertLastEndpoint('products.list');
        $this->assertLastBody(['filter' => ['term' => 'cookie'], 'page' => ['size' => 20, 'number' => 1]]);
    }

    /**
     * Filters were passed through unchecked until v2.2.15.
     */
    public function test_unknown_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(Products::class)->list(['category_id' => 'x']), 'category_id');
    }

    public function test_search_alias_maps_to_term(): void
    {
        $this->resource(Products::class)->list(['search' => 'cookie']);

        $this->assertLastBodyHas('filter.term', 'cookie');
        $this->assertLastBodyMissing('filter.search');
    }

    public function test_list_takes_no_includes(): void
    {
        $this->expectRejected(fn () => $this->resource(Products::class)->withSuppliers()->list(), 'info() only');
    }

    // -- info ------------------------------------------------------------------

    public function test_info_with_suppliers(): void
    {
        $this->resource(Products::class)->withSuppliers()->info('product-uuid');

        $this->assertLastBody(['id' => 'product-uuid', 'includes' => 'suppliers']);
    }

    /**
     * custom_fields is not an include; info returns them on every call.
     */
    public function test_custom_fields_is_not_an_include(): void
    {
        $this->expectRejected(fn () => $this->resource(Products::class)->info('product-uuid', 'custom_fields'), 'suppliers');
    }

    // -- write -----------------------------------------------------------------

    public function test_create_by_code(): void
    {
        $this->resource(Products::class)->create(['code' => 'COOK-001', 'selling_price' => ['amount' => 9.95, 'currency' => 'EUR']]);

        $this->assertLastEndpoint('products.add');
        $this->assertLastBody(['code' => 'COOK-001', 'selling_price' => ['amount' => 9.95, 'currency' => 'EUR']]);
    }

    public function test_create_needs_a_name_or_code(): void
    {
        $this->expectRejected(fn () => $this->resource(Products::class)->create(['description' => 'x']), 'name or code');
    }

    /**
     * The rules table was never applied before v2.2.15.
     */
    public function test_currency_is_checked(): void
    {
        $this->expectRejected(fn () => $this->resource(Products::class)->update('product-uuid', [
            'purchase_price' => ['amount' => 1, 'currency' => 'eur'],
        ]), 'purchase_price.currency');
    }

    public function test_unknown_fields_throw(): void
    {
        $this->expectRejected(fn () => $this->resource(Products::class)->create(['name' => 'n', 'price' => 1]), 'products.add does not accept: price');
    }

    public function test_price_list_prices(): void
    {
        $this->resource(Products::class)->update('product-uuid', [
            'price_list_prices' => [['price_list_id' => 'list-uuid', 'price' => ['amount' => 1, 'currency' => 'EUR']]],
        ]);

        $this->assertLastBodyHas('price_list_prices.0.price_list_id', 'list-uuid');
    }

    public function test_price_list_price_needs_a_price_list(): void
    {
        $this->expectRejected(fn () => $this->resource(Products::class)->update('product-uuid', [
            'price_list_prices' => [['price' => ['amount' => 1, 'currency' => 'EUR']]],
        ]), 'price_list_prices[0]');
    }

    public function test_stock_threshold_cannot_be_negative(): void
    {
        $this->expectRejected(fn () => $this->resource(Products::class)->update('product-uuid', [
            'configuration' => ['stock_threshold' => ['minimum' => -1, 'action' => 'notify']],
        ]), 'negative');
    }

    // -- categories / price lists ----------------------------------------------

    public function test_categories_reject_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(Categories::class)->list(['name' => 'Flowers']), 'name');
    }

    public function test_price_lists_wrap_a_string_id(): void
    {
        $this->resource(PriceLists::class)->list(['ids' => 'list-uuid']);

        $this->assertLastBody(['filter' => ['ids' => ['list-uuid']]]);
    }
}
