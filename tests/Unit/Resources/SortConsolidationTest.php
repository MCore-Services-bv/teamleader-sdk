<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Resources\General\CustomFields;
use McoreServices\TeamleaderSDK\Resources\TimeTracking\TimeTracking;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * v3.0 (B6): Deals, TimeTracking and CustomFields no longer carry their own
 * copies of the sort rule — they delegate to FilterTrait::normaliseSort().
 *
 * These tests pin the shared behaviour on all three, so a copy that creeps back
 * in and drifts is caught.
 */
final class SortConsolidationTest extends ResourceTestCase
{
    /**
     * @return array<string, array{class-string, string, string, string}>
     */
    public static function resources(): array
    {
        // resource class, endpoint, an accepted field, a rejected field
        return [
            'deals' => [Deals::class, 'deals.list', 'created_at', 'title'],
            'time tracking' => [TimeTracking::class, 'timeTracking.list', 'starts_on', 'created_at'],
            'custom fields' => [CustomFields::class, 'customFieldDefinitions.list', 'label', 'created_at'],
        ];
    }

    #[DataProvider('resources')]
    public function test_the_rejection_names_the_endpoint_and_the_accepted_fields(
        string $class,
        string $endpoint,
        string $accepted,
        string $rejected
    ): void {
        try {
            $this->resource($class)->list([], ['sort' => $rejected]);
            $this->fail('An unsupported sort field was accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("Invalid sort field: {$rejected}. {$endpoint} accepts: ", $e->getMessage());
            $this->assertStringContainsString($accepted, $e->getMessage());
        }

        $this->assertNoRequestMade();
    }

    #[DataProvider('resources')]
    public function test_a_field_order_map_is_accepted(
        string $class,
        string $endpoint,
        string $accepted,
        string $rejected
    ): void {
        $this->resource($class)->list([], ['sort' => [$accepted => 'DESC']]);

        $this->assertSame([['field' => $accepted, 'order' => 'desc']], $this->lastBody()['sort'] ?? null);
    }

    #[DataProvider('resources')]
    public function test_a_list_of_field_names_is_accepted(
        string $class,
        string $endpoint,
        string $accepted,
        string $rejected
    ): void {
        $this->resource($class)->list([], ['sort' => [$accepted], 'sort_order' => 'asc']);

        $this->assertSame([['field' => $accepted, 'order' => 'asc']], $this->lastBody()['sort'] ?? null);
    }

    #[DataProvider('resources')]
    public function test_a_sort_entry_without_a_field_is_rejected(
        string $class,
        string $endpoint,
        string $accepted,
        string $rejected
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sort field must be a non-empty string');

        try {
            $this->resource($class)->list([], ['sort' => [['order' => 'asc']]]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_deals_still_sorts_descending_by_default(): void
    {
        $this->resource(Deals::class)->list([], ['sort' => 'created_at']);

        $this->assertSame([['field' => 'created_at', 'order' => 'desc']], $this->lastBody()['sort'] ?? null);
    }

    public function test_no_resource_redefines_the_shared_sort_methods(): void
    {
        foreach ([Deals::class, TimeTracking::class, CustomFields::class] as $class) {
            foreach (['normaliseSort', 'validateSortField', 'normaliseSortOrder'] as $method) {
                $declaring = (new ReflectionMethod($class, $method))->getDeclaringClass()->getName();

                $this->assertNotSame($class, $declaring, "{$class} redefines {$method}().");
            }
        }
    }
}
