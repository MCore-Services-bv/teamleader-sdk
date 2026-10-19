<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Pulse;

use McoreServices\TeamleaderSDK\Pulse\Livewire\Concerns\StylesTeamleaderCards;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * v3.4.1: Pulse stores its aggregates as DECIMAL. MySQL and PostgreSQL return
 * them as strings ("3.00"), SQLite as numbers; the cards accept both. In 3.4.0
 * a string threw a TypeError, which Livewire answers with 419 "page expired".
 */
final class ScaledCountsTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, float, float}>
     */
    public static function counts(): iterable
    {
        yield 'MySQL decimal string' => ['3.00', 1.0, 3.0];
        yield 'PostgreSQL numeric string' => ['3', 1.0, 3.0];
        yield 'SQLite integer' => [3, 1.0, 3.0];
        yield 'float' => [3.0, 1.0, 3.0];
        yield 'sampled at half' => ['3.00', 0.5, 6.0];
        yield 'no rows' => [null, 1.0, 0.0];
    }

    #[DataProvider('counts')]
    public function test_counts_from_any_database_are_scaled(mixed $count, float $sampleRate, float $expected): void
    {
        $card = new class
        {
            use StylesTeamleaderCards;

            public function scale(mixed $count, float $sampleRate): float
            {
                return $this->scaled($count, $sampleRate);
            }
        };

        $this->assertSame($expected, $card->scale($count, $sampleRate));
    }
}
