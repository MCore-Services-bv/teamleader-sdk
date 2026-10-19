<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Pulse\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use McoreServices\TeamleaderSDK\Pulse\Entries;
use McoreServices\TeamleaderSDK\Pulse\Livewire\Concerns\StylesTeamleaderCards;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderRequests;

/**
 * One row per Teamleader connection: traffic, error rate, rate-limit waits and
 * token renewals, with the connections that need someone to connect them
 * again listed first.
 *
 *     <livewire:teamleader.connections cols="4" />
 *     <livewire:teamleader.connections cols="4" limit="25" />
 *
 * `limit` caps the rows (default 10, busiest first). A connection that needs
 * attention is always listed, whatever the limit.
 */
#[Lazy]
class Connections extends Card
{
    use StylesTeamleaderCards;

    public int|string $limit = 10;

    #[Url(as: 'teamleader-connections')]
    public string $orderBy = 'requests';

    public function render(): Renderable
    {
        $config = $this->recorderConfig(TeamleaderRequests::class);

        [$data, $time, $runAt] = $this->remember(
            fn () => $this->query($config['sample_rate']),
            $this->orderBy.':'.$this->limit,
        );

        return View::make('teamleader::pulse.connections', [
            'time' => $time,
            'runAt' => $runAt,
            'config' => $config,
            'attention' => $data['attention'],
            'connections' => $data['connections'],
            'total' => $data['total'],
        ]);
    }

    /**
     * @return array{attention: Collection<int, object>, connections: Collection<int, object>, total: int}
     */
    private function query(float $sampleRate): array
    {
        $rows = [];

        foreach ($this->aggregate(Entries::CONNECTION_REQUEST, ['count', 'avg'], limit: 1000) as $row) {
            $connection = $this->row($rows, $row->key);
            $connection->requests = $this->scaled($row->count, $sampleRate);
            $connection->average = $row->avg === null ? null : (float) $row->avg;
        }

        foreach ($this->aggregate(Entries::CONNECTION_FAILURE, 'count', limit: 1000) as $row) {
            $this->row($rows, $row->key)->failures = (int) $row->count;
        }

        foreach ($this->aggregate(Entries::CONNECTION_UNANSWERED, 'count', limit: 1000) as $row) {
            $this->row($rows, $row->key)->unanswered = (int) $row->count;
        }

        foreach ($this->aggregate(Entries::RATE_LIMIT_WAIT, ['count', 'sum', 'max'], limit: 1000) as $row) {
            $connection = $this->row($rows, $row->key);
            $connection->waits = (int) $row->count;
            $connection->waited = (int) $row->sum;
            $connection->longestWait = (int) $row->max;
        }

        foreach ($this->aggregate(Entries::RATE_LIMIT_GAVE_UP, 'count', limit: 1000) as $row) {
            $this->row($rows, $row->key)->gaveUp = (int) $row->count;
        }

        foreach ($this->aggregate(Entries::TOKEN_REFRESHED, 'count', limit: 1000) as $row) {
            $this->row($rows, $row->key)->refreshed = (int) $row->count;
        }

        foreach ($this->aggregate(Entries::TOKEN_REFRESH_FAILED, 'count', limit: 1000) as $row) {
            $this->row($rows, $row->key)->refreshFailed = (int) $row->count;
        }

        $connections = collect($rows)
            ->each(function (object $row) {
                // Answered failures are among the requests; failures that never
                // reached the API are not
                $all = $row->requests + $row->unanswered;
                $row->errorRate = $all > 0 ? min(100.0, $row->failures / $all * 100) : 0.0;
            })
            ->sortByDesc(fn (object $row) => match ($this->orderBy) {
                'failures' => $row->failures,
                'error_rate' => $row->errorRate,
                'waits' => $row->waited,
                'average' => $row->average ?? -1,
                default => $row->requests,
            })
            ->values();

        return [
            'attention' => $this->attention(),
            'connections' => $connections->take(max(1, (int) $this->limit)),
            'total' => $connections->count(),
        ];
    }

    /**
     * Connections whose last known state is not "ok", most recent first.
     * Read by name: a state is never grouped.
     *
     * @return Collection<int, object>
     */
    private function attention(): Collection
    {
        return $this->values(Entries::CONNECTION_STATE)
            ->map(function (object $value, string|int $connection) {
                $state = json_decode((string) $value->value, true);

                if (! is_array($state) || ($state['state'] ?? Entries::STATE_OK) === Entries::STATE_OK) {
                    return null;
                }

                return (object) [
                    'connection' => (string) $connection,
                    'state' => (string) $state['state'],
                    'reason' => (string) ($state['reason'] ?? ''),
                    'since' => CarbonImmutable::createFromTimestamp((int) ($state['at'] ?? $value->timestamp)),
                ];
            })
            ->filter()
            ->sortByDesc(fn (object $row) => $row->since->getTimestamp())
            ->values();
    }

    /**
     * @param  array<string, object>  $rows
     */
    private function row(array &$rows, ?string $connection): object
    {
        // A key Pulse no longer has (trimmed before the period ends) shows as "?"
        $connection ??= '?';

        return $rows[$connection] ??= (object) [
            'connection' => $connection,
            'requests' => 0.0,
            'average' => null,
            'failures' => 0,
            'unanswered' => 0,
            'errorRate' => 0.0,
            'waits' => 0,
            'waited' => 0,
            'longestWait' => 0,
            'gaveUp' => 0,
            'refreshed' => 0,
            'refreshFailed' => 0,
        ];
    }
}
