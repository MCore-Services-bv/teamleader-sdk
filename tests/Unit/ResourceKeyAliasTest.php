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
 * v2.2.6 renamed seven resource keys to camelCase, keeping the old keys as
 * deprecated aliases until v3.0.
 *
 * The SDK's own usage examples already used the camelCase names, so 36 of
 * them threw "Method or resource not found" — `paymentMethods()` failed while
 * `payment_methods()` worked.
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
    public function test_canonical_key_resolves(string $canonical, string $deprecated, string $class): void
    {
        $this->assertInstanceOf($class, $this->api->{$canonical}());
    }

    #[DataProvider('renamed')]
    public function test_deprecated_key_resolves_to_the_same_instance(string $canonical, string $deprecated, string $class): void
    {
        $deprecations = $this->captureDeprecations(fn () => $this->api->{$deprecated}());

        $this->assertSame($this->api->{$canonical}(), $this->withoutDeprecations(fn () => $this->api->{$deprecated}()));
        $this->assertSame($deprecated, $this->aliasOf($canonical));
        $this->assertLessThanOrEqual(1, count($deprecations), 'A deprecated key is reported at most once per process.');
    }

    public function test_the_alias_map_matches_the_renames(): void
    {
        $expected = [];

        foreach (self::renamed() as [$canonical, $deprecated]) {
            $expected[$deprecated] = $canonical;
        }

        ksort($expected);
        $actual = $this->api->getDeprecatedResourceAliases();
        ksort($actual);

        $this->assertSame($expected, $actual);
    }

    public function test_an_unknown_key_still_throws(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Method or resource 'events' not found");

        $this->api->events();
    }

    public function test_a_resource_registered_under_an_old_key_wins_over_the_alias(): void
    {
        $this->api->addResource('payment_terms', PaymentMethods::class);

        $this->assertInstanceOf(PaymentMethods::class, $this->api->payment_terms());
        $this->assertInstanceOf(PaymentTerms::class, $this->api->paymentTerms());
    }

    private function aliasOf(string $canonical): ?string
    {
        $key = array_search($canonical, $this->api->getDeprecatedResourceAliases(), true);

        return $key === false ? null : $key;
    }

    /**
     * @return list<string>
     */
    private function captureDeprecations(callable $callback): array
    {
        $messages = [];

        set_error_handler(function (int $level, string $message) use (&$messages) {
            $messages[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $messages;
    }

    private function withoutDeprecations(callable $callback): mixed
    {
        set_error_handler(fn () => true, E_USER_DEPRECATED);

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
