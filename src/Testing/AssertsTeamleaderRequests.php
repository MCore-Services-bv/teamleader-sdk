<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Testing;

use Closure;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * The assertions, over whatever recordedCalls() returns: one connection's
 * calls on a FakeTeamleader, every connection's on the FakeConnectionManager.
 *
 * Endpoints may be patterns: `deals.*`, `*.create`.
 */
trait AssertsTeamleaderRequests
{
    /**
     * @return list<array{method: string, endpoint: string, body: array, connection: string}>
     */
    abstract protected function recordedCalls(): array;

    /**
     * The recorded calls, optionally only those to an endpoint and passing a
     * check. The check receives the body and the whole call.
     *
     * @param  (Closure(array $body, array $call): bool)|null  $callback
     * @return list<array{method: string, endpoint: string, body: array, connection: string}>
     */
    public function recorded(?string $endpoint = null, ?Closure $callback = null): array
    {
        return array_values(array_filter(
            $this->recordedCalls(),
            fn (array $call) => ($endpoint === null || Str::is($endpoint, $call['endpoint']))
                && ($callback === null || $callback($call['body'], $call) === true)
        ));
    }

    /**
     * @param  (Closure(array $body, array $call): bool)|null  $callback
     */
    public function assertSent(string $endpoint, ?Closure $callback = null): static
    {
        PHPUnit::assertNotEmpty(
            $this->recorded($endpoint, $callback),
            $callback === null
                ? "Expected a request to [{$endpoint}]. {$this->sentSummary()}"
                : "Expected a request to [{$endpoint}] passing the given check. {$this->sentSummary($endpoint)}"
        );

        return $this;
    }

    /**
     * @param  (Closure(array $body, array $call): bool)|null  $callback
     */
    public function assertNotSent(string $endpoint, ?Closure $callback = null): static
    {
        $count = count($this->recorded($endpoint, $callback));

        PHPUnit::assertSame(0, $count, "Expected no request to [{$endpoint}], but {$count} were sent.");

        return $this;
    }

    public function assertSentCount(string $endpoint, int $expected): static
    {
        $count = count($this->recorded($endpoint));

        PHPUnit::assertSame(
            $expected,
            $count,
            "Expected {$expected} request(s) to [{$endpoint}], but {$count} were sent. {$this->sentSummary()}"
        );

        return $this;
    }

    public function assertNothingSent(): static
    {
        PHPUnit::assertSame([], $this->recordedCalls(), "Expected no Teamleader requests. {$this->sentSummary()}");

        return $this;
    }

    /** "Sent: deals.list, deals.create (x2)." — for failure messages */
    private function sentSummary(?string $endpoint = null): string
    {
        $calls = $endpoint === null ? $this->recordedCalls() : $this->recorded($endpoint);

        if ($calls === []) {
            return $endpoint === null ? 'Nothing was sent.' : "Nothing was sent to [{$endpoint}].";
        }

        if ($endpoint !== null) {
            $bodies = array_map(fn (array $call) => json_encode($call['body'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $calls);

            return 'Sent bodies: '.implode('; ', array_slice($bodies, 0, 5)).(count($bodies) > 5 ? '; …' : '').'.';
        }

        $counts = array_count_values(array_map(
            fn (array $call) => $call['connection'] === 'default' ? $call['endpoint'] : "{$call['connection']}:{$call['endpoint']}",
            $calls
        ));

        $parts = [];

        foreach ($counts as $name => $count) {
            $parts[] = $count > 1 ? "{$name} (x{$count})" : $name;
        }

        return 'Sent: '.implode(', ', $parts).'.';
    }
}
