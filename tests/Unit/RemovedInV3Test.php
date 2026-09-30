<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit;

use McoreServices\TeamleaderSDK\Resources\CRM\Companies;
use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Resources\Deals\LostReasons;
use McoreServices\TeamleaderSDK\Resources\Deals\Quotations;
use McoreServices\TeamleaderSDK\Resources\General\Users;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Creditnotes;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Invoices;
use McoreServices\TeamleaderSDK\Resources\Planning\PlannableItems;
use McoreServices\TeamleaderSDK\Resources\Products\Products;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * v3.0 (B1): the methods deprecated during the 2.2.x audit are gone.
 *
 * Each row is listed in docs/project/upgrading.md under "From 2.3 to 3.0".
 * If one comes back — a merge from 2.x, say — this fails.
 */
final class RemovedInV3Test extends TestCase
{
    /**
     * @return array<string, array{class-string, string}>
     */
    public static function removed(): array
    {
        return [
            'users getWeekSchedule' => [Users::class, 'getWeekSchedule'],
            'plannableItems active' => [PlannableItems::class, 'active'],
            'invoices draft' => [Invoices::class, 'draft'],
            'lostReasons search' => [LostReasons::class, 'search'],
            'companies byName' => [Companies::class, 'byName'],
            'quotations byStatus' => [Quotations::class, 'byStatus'],
            'creditNotes paid' => [Creditnotes::class, 'paid'],
            'creditNotes unpaid' => [Creditnotes::class, 'unpaid'],
            'products withCustomFields' => [Products::class, 'withCustomFields'],
            'deals withCustomer' => [Deals::class, 'withCustomer'],
            'deals withResponsibleUser' => [Deals::class, 'withResponsibleUser'],
            'deals withDepartment' => [Deals::class, 'withDepartment'],
            'deals withCurrentPhase' => [Deals::class, 'withCurrentPhase'],
            'deals withSource' => [Deals::class, 'withSource'],
            'deals withAll' => [Deals::class, 'withAll'],
            'sdk getDeprecatedResourceAliases' => [TeamleaderSDK::class, 'getDeprecatedResourceAliases'],
        ];
    }

    #[DataProvider('removed')]
    public function test_method_is_removed(string $class, string $method): void
    {
        $this->assertFalse(method_exists($class, $method), "{$class}::{$method}() was removed in v3.0.");
    }

    public function test_the_unused_constants_classes_are_removed(): void
    {
        $this->assertFalse(class_exists('McoreServices\\TeamleaderSDK\\Constants\\TeamleaderConstants'));
        $this->assertFalse(class_exists('McoreServices\\TeamleaderSDK\\Constants\\ErrorMessages'));
    }

    public function test_the_replacements_still_exist(): void
    {
        $this->assertTrue(method_exists(Invoices::class, 'listDrafts'));
        $this->assertTrue(method_exists(LostReasons::class, 'byIds'));
        $this->assertTrue(method_exists(Companies::class, 'search'));
        $this->assertTrue(method_exists(Deals::class, 'withCustomFields'));
    }
}
