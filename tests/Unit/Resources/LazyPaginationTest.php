<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use BadMethodCallException;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\CRM\Companies;
use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Resources\General\EmailTracking;
use McoreServices\TeamleaderSDK\Resources\General\Notes;
use McoreServices\TeamleaderSDK\Resources\General\UserSchedules;
use McoreServices\TeamleaderSDK\Resources\Planning\UserAvailability;
use McoreServices\TeamleaderSDK\Support\ResourceCatalog;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * v3.0 (F3): lazy() on every paginated resource, driven by the catalog, so a
 * resource added later is covered without touching this file.
 */
final class LazyPaginationTest extends ResourceTestCase
{
    /**
     * Declared paginated, but list() is not how it is read — it answers through
     * daily() and total(). lazy() therefore fails in list(), with its message.
     */
    private const WITHOUT_LIST = [UserAvailability::class];

    private const UUID = '0b9a4c8e-1f2d-4e3a-9b5c-6d7e8f901234';

    /**
     * Endpoints that only list records of one subject or one set of users. They
     * refuse a bare list() — correctly — so the test passes what they require.
     */
    private const REQUIRED_FILTERS = [
        Notes::class => ['subject' => ['type' => 'company', 'id' => self::UUID]],
        EmailTracking::class => ['subject' => ['type' => 'company', 'id' => self::UUID]],
        Files::class => ['subject' => ['type' => 'company', 'id' => self::UUID]],
        UserSchedules::class => ['user_ids' => [self::UUID], 'from' => '2026-10-05', 'until' => '2026-10-09'],
    ];

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function paginated(): array
    {
        return self::resourcesWhere(true);
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function notPaginated(): array
    {
        return self::resourcesWhere(false);
    }

    #[DataProvider('paginated')]
    public function test_lazy_pages_through_list_until_a_short_page(string $class, string $endpoint): void
    {
        $this->api->queueResponses([
            ['data' => [['id' => 'a'], ['id' => 'b']], 'headers' => []],
            ['data' => [['id' => 'c']], 'headers' => []],
        ]);

        $records = $this->resource($class)->lazy(self::REQUIRED_FILTERS[$class] ?? [], ['page_size' => 2])->all();

        $this->assertSame(['a', 'b', 'c'], array_column($records, 'id'), "{$endpoint}: every record once, in order.");
        $this->assertSame(2, $this->api->callCount(), "{$endpoint}: one request per page, none after the short one.");
        $this->assertSame([$endpoint, $endpoint], $this->api->endpoints());

        $this->assertSame(['size' => 2, 'number' => 1], $this->pageOf($this->api->calls[0]['body']), "{$endpoint}: page 1");
        $this->assertSame(['size' => 2, 'number' => 2], $this->pageOf($this->api->calls[1]['body']), "{$endpoint}: page 2");
    }

    #[DataProvider('notPaginated')]
    public function test_lazy_refuses_an_endpoint_without_pages(string $class, string $endpoint): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("{$endpoint} is not paginated");

        $this->resource($class)->lazy();
    }

    public function test_required_filters_are_still_enforced(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('notes.list requires a subject');

        $this->resource(Notes::class)->lazy()->first();
    }

    public function test_the_resource_without_list_fails_with_its_own_message(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Use daily() or total()');

        $this->resource(UserAvailability::class)->lazy()->all();
    }

    public function test_nothing_is_requested_until_iterated(): void
    {
        $lazy = $this->resource(Companies::class)->lazy();

        $this->assertInstanceOf(LazyCollection::class, $lazy);
        $this->assertSame(0, $this->api->callCount());
    }

    public function test_the_default_page_size_is_100(): void
    {
        $this->resource(Companies::class)->lazy()->all();

        $this->assertSame(['size' => 100, 'number' => 1], $this->pageOf($this->api->lastBody()));
    }

    public function test_filters_and_options_apply_to_every_page(): void
    {
        $this->api->queueResponses([
            ['data' => [['id' => 'a']], 'headers' => []],
            ['data' => [], 'headers' => []],
        ]);

        $this->resource(Deals::class)->lazy(['status' => 'open'], ['page_size' => 1, 'sort' => 'created_at'])->all();

        foreach ($this->api->calls as $call) {
            $this->assertSame(['open'], $call['body']['filter']['status']);
            $this->assertSame('created_at', $call['body']['sort'][0]['field']);
        }
    }

    public function test_fluent_includes_apply_to_every_page_not_just_the_first(): void
    {
        $this->api->queueResponses([
            ['data' => [['id' => 'a']], 'headers' => []],
            ['data' => [], 'headers' => []],
        ]);

        $this->resource(Deals::class)->withCustomFields()->lazy([], ['page_size' => 1])->all();

        $this->assertSame('custom_fields', $this->api->calls[0]['body']['includes']);
        $this->assertSame('custom_fields', $this->api->calls[1]['body']['includes']);
    }

    public function test_page_number_starts_the_cursor_later(): void
    {
        $this->resource(Companies::class)->lazy([], ['page_size' => 10, 'page_number' => 4])->all();

        $this->assertSame(['size' => 10, 'number' => 4], $this->pageOf($this->api->lastBody()));
    }

    public function test_invalid_filters_throw_on_iteration(): void
    {
        $lazy = $this->resource(Companies::class)->lazy(['name' => 'Acme']);

        $this->expectException(InvalidArgumentException::class);

        $lazy->first();
    }

    // -- helpers ----------------------------------------------------------------

    /**
     * @return array<string, array{class-string, string}>
     */
    private static function resourcesWhere(bool $paginated): array
    {
        $rows = [];

        foreach ((new ResourceCatalog)->resources() as $key => $resource) {
            if ($resource['supports']['pagination'] !== $paginated) {
                continue;
            }

            if ($paginated && in_array($resource['class'], self::WITHOUT_LIST, true)) {
                continue;
            }

            $rows[$key] = [$resource['class'], $resource['base_path'].'.list'];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pageOf(array $body): ?array
    {
        return isset($body['page']) ? ['size' => $body['page']['size'] ?? null, 'number' => $body['page']['number'] ?? null] : null;
    }
}
