<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Pulse\Livewire\Concerns;

use Illuminate\Support\Facades\Config;

/**
 * Shared by the SDK's Pulse cards: their stylesheet, and reading a recorder's
 * configuration with Pulse's defaults filled in.
 *
 * @internal
 */
trait StylesTeamleaderCards
{
    /**
     * Pulse inlines this once on the dashboard. Pulse's own stylesheet only
     * holds the classes its own cards use, so the few the SDK's cards add
     * (state badges, bars) are defined here.
     */
    protected function css(): string
    {
        return __DIR__.'/../../../../resources/css/pulse.css';
    }

    /**
     * @param  class-string  $recorder
     * @return array{enabled: bool, sample_rate: float, threshold: int|array<string, int>, ignore: array<int, string>, connections: array{ignore: array<int, string>, groups: array<string, string>}}
     */
    protected function recorderConfig(string $recorder): array
    {
        $config = Config::get('pulse.recorders.'.$recorder);
        $config = is_array($config) ? $config : [];

        return [
            'enabled' => (bool) ($config['enabled'] ?? false),
            'sample_rate' => (float) ($config['sample_rate'] ?? 1),
            'threshold' => $config['threshold'] ?? 1000,
            'ignore' => (array) ($config['ignore'] ?? []),
            'connections' => [
                'ignore' => (array) ($config['connections']['ignore'] ?? []),
                'groups' => (array) ($config['connections']['groups'] ?? []),
            ],
        ];
    }

    /**
     * A count recorded at a sample rate below 1, scaled back up.
     *
     * Takes whatever the database returns: Pulse stores aggregates as
     * DECIMAL, which MySQL and PostgreSQL return as strings ("3.00") and
     * SQLite as numbers.
     */
    protected function scaled(mixed $count, float $sampleRate): float
    {
        $count = is_numeric($count) ? (float) $count : 0.0;

        return $sampleRate > 0 && $sampleRate < 1 ? $count / $sampleRate : $count;
    }
}
