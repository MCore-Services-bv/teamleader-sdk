<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit;

use Exception;
use McoreServices\TeamleaderSDK\Resources\Calendar\Events;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Creditnotes;
use McoreServices\TeamleaderSDK\Resources\Invoicing\PaymentMethods;
use McoreServices\TeamleaderSDK\Resources\Invoicing\PaymentTerms;
use McoreServices\TeamleaderSDK\Resources\Planning\PlannableItems;
use McoreServices\TeamleaderSDK\Resources\Planning\UserAvailability;
use McoreServices\TeamleaderSDK\Resources\Projects\ExternalParties;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * v2.2.6 renamed seven resource keys to camelCase and kept the old keys as
 * deprecated aliases. v3.0 removed the aliases.
 *
 * An old key now throws, and the message names its replacement, so an
 * upgrade that missed one fails with the fix in the error.
 */
final class ResourceKeyAliasTest extends ResourceTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: class-string}>
     */
    public static function renamed(): array
    {
        return [
            'calendarEvents' => ['calendarEvents', 'calenderEvents', Events::class],
            'creditNotes' => ['creditNotes', 'creditnotes', Creditnotes::class],
            'paymentMethods' => ['paymentMethods', 'payment_methods', PaymentMethods::class],
            'paymentTerms' => ['paymentTerms', 'payment_terms', PaymentTerms::class],
            'externalParties' => ['externalParties', 'external_parties', ExternalParties::class],
            'plannableItems' => ['plannableItems', 'plannable_items', PlannableItems::class],
            'userAvailability' => ['userAvailability', 'user_availability', UserAvailability::class],
        ];
    }

    #[DataProvider('renamed')]
    public function test_canonical_key_resolves(string $canonical, string $removed, string $class): void
    {
        $this->assertInstanceOf($class, $this->api->{$canonical}());
    }

    #[DataProvider('renamed')]
    public function test_removed_key_throws_and_names_the_replacement(string $canonical, string $removed, string $class): void
    {
        try {
            $this->api->{$removed}();
            $this->fail("{$removed}() still resolves.");
        } catch (Exception $e) {
            $this->assertStringContainsString("Method or resource '{$removed}' not found", $e->getMessage());
            $this->assertStringContainsString("Use {$canonical}().", $e->getMessage());
        }
    }

    public function test_an_unknown_key_still_throws_without_a_hint(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/^Method or resource 'events' not found$/");

        $this->api->events();
    }

    public function test_a_resource_registered_under_an_old_key_is_used(): void
    {
        $this->api->addResource('payment_terms', PaymentMethods::class);

        $this->assertInstanceOf(PaymentMethods::class, $this->api->payment_terms());
        $this->assertInstanceOf(PaymentTerms::class, $this->api->paymentTerms());
    }
}
