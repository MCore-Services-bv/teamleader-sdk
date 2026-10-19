<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Pulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Recorders\Concerns\Thresholds;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use McoreServices\TeamleaderSDK\Pulse\Entries;
use McoreServices\TeamleaderSDK\Pulse\Livewire\Concerns\StylesTeamleaderCards;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderRequests;

/**
 * Teamleader API calls per connection and endpoint: how many, how many
 * failed and why, how many were slow, average and slowest.
 *
 *     <livewire:teamleader.requests cols="8" />
 */
#[Lazy]
class Requests extends Card
{
    use StylesTeamleaderCards, Thresholds;

    #[Url(as: 'teamleader-requests')]
    public string $orderBy = 'count';

    #[Url(as: 'teamleader-requests-connection')]
    public string $connection = '';

    public function render(): Renderable
    {
        $config = $this->recorderConfig(TeamleaderRequests::class);

        [$data, $time, $runAt] = $this->remember(
            fn () => $this->query($config['sample_rate']),
            $this->orderBy.':'.$this->connection,
        );

        return View::make('teamleader::pulse.requests', [
            'time' => $time,
            'runAt' => $runAt,
            'config' => $config,
            'requests' => $data['requests'],
            'connections' => $data['connections'],
        ]);
    }

    /**
     * @return array{requests: Collection<int, object>, connections: list<string>}
     */
    private function query(float $sampleRate): array
    {
        // Read ordered by the measure the card sorts on, so the top rows are
        // the right ones. Failures and slow requests are counted in their own
        // types, and a filter can only be applied after the query (the
        // connection is inside the key): those read more rows.
        $limit = $this->connection === '' && in_array($this->orderBy, ['count', 'average', 'slowest'], true) ? 101 : 1000;

        $orderBy = match ($this->orderBy) {
            'average' => 'avg',
            'slowest' => 'max',
            default => 'count',
        };

        $rows = [];

        foreach ($this->aggregate(Entries::REQUEST, ['count', 'avg', 'max'], $orderBy, limit: $limit) as $row) {
            [$connection, $endpoint] = Entries::parts((string) $row->key) + [null, null];

            if (! is_string($connection) || ! is_string($endpoint)) {
                continue;
            }

            $rows[$row->key] = $this->row($connection, $endpoint);
            $rows[$row->key]->count = $this->scaled($row->count, $sampleRate);
            $rows[$row->key]->average = $row->avg === null ? null : (float) $row->avg;
            $rows[$row->key]->slowest = $row->max === null ? null : (int) $row->max;
        }

        foreach ($this->aggregate(Entries::SLOW_REQUEST, 'count', limit: $limit) as $row) {
            if (isset($rows[$row->key])) {
                $rows[$row->key]->slow = $this->scaled($row->count, $sampleRate);
            }
        }

        // Failures are recorded without sampling, and include requests that
        // never reached the API: those have no row above yet
        foreach ($this->aggregate(Entries::FAILURE, 'count', limit: $limit) as $row) {
            [$connection, $endpoint, $reason] = Entries::parts((string) $row->key) + [null, null, null];

            if (! is_string($connection) || ! is_string($endpoint)) {
                continue;
            }

            $key = Entries::key([$connection, $endpoint]);
            $rows[$key] ??= $this->row($connection, $endpoint);
            $rows[$key]->failures += (int) $row->count;
            $rows[$key]->reasons[(string) $reason] = ($rows[$key]->reasons[(string) $reason] ?? 0) + (int) $row->count;
        }

        $all = collect($rows)->values();

        $connections = $all->pluck('connection')->unique()->sort()->values()->all();

        $requests = $all
            ->when($this->connection !== '', fn (Collection $rows) => $rows->where('connection', $this->connection))
            ->sortByDesc(fn (object $row) => match ($this->orderBy) {
                'slowest' => $row->slowest ?? -1,
                'average' => $row->average ?? -1,
                'failures' => $row->failures,
                'slow' => $row->slow,
                default => $row->count,
            })
            ->values()
            ->each(function (object $row) {
                arsort($row->reasons);
                $row->threshold = $this->threshold($row->endpoint, TeamleaderRequests::class);
            });

        return ['requests' => $requests, 'connections' => $connections];
    }

    private function row(string $connection, string $endpoint): object
    {
        return (object) [
            'connection' => $connection,
            'endpoint' => $endpoint,
            'count' => 0.0,
            'average' => null,
            'slowest' => null,
            'slow' => 0.0,
            'failures' => 0,
            'reasons' => [],
            'threshold' => 0,
        ];
    }
}
