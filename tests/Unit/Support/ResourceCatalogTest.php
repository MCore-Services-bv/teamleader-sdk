<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Support;

use McoreServices\TeamleaderSDK\Resources\CRM\Companies;
use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Support\ResourceCatalog;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SdkInventory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * v3.0 (F1): the resource catalog lives in src/ so the CLI can use it at
 * runtime. Plain PHPUnit — no Laravel — because the catalog must work without
 * an application, as bin/spec-audit and bin/docs use it.
 */
final class ResourceCatalogTest extends TestCase
{
    public function test_the_registry_is_the_one_the_sdk_declares(): void
    {
        $declared = (new ReflectionClass(TeamleaderSDK::class))->getDefaultProperties()['resources'];

        $this->assertSame($declared, (new ResourceCatalog)->registry());
    }

    public function test_every_registered_resource_is_described(): void
    {
        $catalog = new ResourceCatalog;
        $classes = array_unique(array_values($catalog->registry()));

        $this->assertCount(count($classes), $catalog->resources());

        foreach ($catalog->resources() as $key => $resource) {
            $this->assertSame($key, $resource['key']);
            $this->assertNotSame('', $resource['base_path'], "{$key} has no base path.");
        }
    }

    public function test_a_resource_is_described_from_its_declarations(): void
    {
        $deals = (new ResourceCatalog)->resource('deals');

        $this->assertNotNull($deals);
        $this->assertSame(Deals::class, $deals['class']);
        $this->assertSame('Deals', $deals['category']);
        $this->assertSame('deals', $deals['base_path']);
        $this->assertContains('deals.list', $deals['endpoints']);
        $this->assertSame(['created_at', 'weighted_value'], $deals['sort_fields']);
        $this->assertTrue($deals['supports']['pagination']);
    }

    public function test_an_unknown_key_returns_null(): void
    {
        $this->assertNull((new ResourceCatalog)->resource('events'));
    }

    public function test_a_custom_registry_replaces_the_declared_one(): void
    {
        $catalog = new ResourceCatalog(['companies' => Companies::class, 'accounts' => Companies::class]);

        $this->assertSame(['companies'], array_keys($catalog->resources()));
        $this->assertSame(['accounts'], $catalog->resources()['companies']['aliases']);
        $this->assertSame('companies', $catalog->resource('accounts')['key']);
    }

    public function test_resources_are_grouped_by_category(): void
    {
        $grouped = (new ResourceCatalog)->byCategory();

        $this->assertArrayHasKey('CRM', $grouped);
        $this->assertArrayHasKey('companies', $grouped['CRM']);
        $this->assertSame(array_keys($grouped), $this->sorted(array_keys($grouped)));
        $this->assertSame(
            count((new ResourceCatalog)->resources()),
            array_sum(array_map('count', $grouped))
        );
    }

    public function test_the_test_inventory_is_the_catalog(): void
    {
        $this->assertInstanceOf(ResourceCatalog::class, new SdkInventory);
        $this->assertSame((new ResourceCatalog)->resources(), (new SdkInventory)->resources());
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
