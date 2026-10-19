<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Pulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use McoreServices\TeamleaderSDK\Pulse\Entries;
use McoreServices\TeamleaderSDK\Pulse\Livewire\Concerns\StylesTeamleaderCards;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderCallers;

/**
 * Where the Teamleader time goes: per route, queued job and Artisan command,
 * how long it spent on Teamleader, what share of its own time that was, and
 * how many calls it made.
 *
 *     <livewire:teamleader.callers cols="full" />
 */
#[Lazy]
class Callers extends Card
{
    use StylesTeamleaderCards;

    #[Url(as: 'teamleader-callers')]
    public string $orderBy = 'total';

    public function render(): Renderable
    {
        $config = $this->recorderConfig(TeamleaderCallers::class);

        [$callers, $time, $runAt] = $this->remember(
            fn () => $this->query($config['sample_rate']),
            $this->orderBy,
        );

        return View::make('teamleader::pulse.callers', [
            'time' => $time,
            'runAt' => $runAt,
            'config' => $config,
            'callers' => $callers,
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function query(float $sampleRate): Collection
    {
        $orderBy = match ($this->orderBy) {
            'average' => 'avg',
            'slowest' => 'max',
            'count' => 'count',
            default => 'sum',
        };

        $rows = [];

        // Share and calls are computed from other types: read wider for those
        $limit = in_array($this->orderBy, ['share', 'calls'], true) ? 1000 : 101;

        foreach ($this->aggregate(Entries::CALLER_TIME, ['count', 'sum', 'avg', 'max'], $orderBy, limit: $limit) as $row) {
            [$kind, $name, $via] = Entries::parts((string) $row->key) + [null, null, null];

            if (! is_string($kind) || ! is_string($name)) {
                continue;
            }

            $rows[$row->key] = (object) [
                'kind' => $kind,
                'name' => $name,
                'via' => is_string($via) ? $via : null,
                'count' => $this->scaled($row->count, $sampleRate),
                'total' => $this->scaled($row->sum, $sampleRate),
                'average' => (float) $row->avg,
                'slowest' => (int) $row->max,
                'share' => null,
                'calls' => null,
                'mostCalls' => null,
                'rawCount' => (int) $row->count,
                'rawTotal' => (float) $row->sum,
            ];
        }

        if ($rows === []) {
            return collect();
        }

        // The same keys, read for their duration and call counts. Read wider
        // than the rows above, so each of those is among the results.
        foreach ($this->aggregate(Entries::CALLER_DURATION, 'sum', limit: 2000) as $row) {
            if (isset($rows[$row->key]) && (float) $row->sum > 0) {
                $rows[$row->key]->share = min(100.0, $rows[$row->key]->rawTotal / (float) $row->sum * 100);
            }
        }

        foreach ($this->aggregate(Entries::CALLER_CALLS, ['sum', 'max'], limit: 2000) as $row) {
            if (isset($rows[$row->key])) {
                $runs = $rows[$row->key]->rawCount;
                $rows[$row->key]->calls = $runs > 0 ? (float) $row->sum / $runs : null;
                $rows[$row->key]->mostCalls = (int) $row->max;
            }
        }

        return collect($rows)
            ->when($this->orderBy === 'share', fn (Collection $rows) => $rows->sortByDesc(fn (object $row) => $row->share ?? -1))
            ->when($this->orderBy === 'calls', fn (Collection $rows) => $rows->sortByDesc(fn (object $row) => $row->calls ?? -1))
            ->values();
    }
}
