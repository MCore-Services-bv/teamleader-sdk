<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Pulse\Recorders;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\Concerns\Ignores;
use Laravel\Pulse\Recorders\Concerns\Sampling;
use Laravel\Pulse\Recorders\Concerns\Thresholds;
use McoreServices\TeamleaderSDK\Events\ConnectionAuthorized;
use McoreServices\TeamleaderSDK\Events\RateLimitWaited;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Events\TokenRefreshed;
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;
use McoreServices\TeamleaderSDK\Exceptions\ConnectionNeedsReauthorizationException;
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;
use McoreServices\TeamleaderSDK\Pulse\Entries;

/**
 * Records the SDK's traffic in Laravel Pulse: requests and their duration per
 * connection and endpoint, failures with their reason, rate-limit waits, token
 * renewals, and which connections need someone to connect them again.
 *
 * Only listens to the SDK's events, so it changes nothing about how requests
 * are sent. Bodies are never read or stored.
 *
 * Add it to `recorders` in config/pulse.php:
 *
 *     \McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderRequests::class => [
 *         'enabled' => env('PULSE_TEAMLEADER_REQUESTS_ENABLED', true),
 *         'sample_rate' => env('PULSE_TEAMLEADER_REQUESTS_SAMPLE_RATE', 1),
 *         'threshold' => env('PULSE_TEAMLEADER_REQUESTS_THRESHOLD', 1000),
 *         'ignore' => [],
 *         'connections' => [
 *             'ignore' => [],
 *             'groups' => [],
 *         ],
 *     ],
 *
 * `sample_rate` applies to successful requests only: failures, rate-limit
 * waits and token events are always recorded. `threshold` (ms, or an array of
 * endpoint patterns => ms with a `default`) marks a request as slow. `ignore`
 * holds patterns matched against the endpoint (`/^webhooks\./`).
 * `connections.ignore` holds patterns matched against the connection name;
 * `connections.groups` maps patterns to one name, so tenant connections such
 * as `site-01HX…` can show as one row: `['/^site-.+$/' => 'site-*']`.
 */
final class TeamleaderRequests
{
    use Ignores, Sampling, Thresholds;

    /**
     * @var list<class-string>
     */
    public array $listen = [
        ResponseReceived::class,
        RequestFailed::class,
        RateLimitWaited::class,
        TokenRefreshed::class,
        TokenRefreshFailed::class,
        ConnectionAuthorized::class,
    ];

    /**
     * The last answer seen, as "connection endpoint". An error status fires
     * ResponseReceived and then RequestFailed in the same call; a failure
     * without an answer just before it never reached the API.
     */
    private ?string $lastAnswer = null;

    /**
     * Connection states written in this process, so a connection that keeps
     * failing the same way writes its state once a minute, not per request.
     *
     * @var array<string, array{state: string, at: int}>
     */
    private array $statesWritten = [];

    public function __construct(
        private readonly Pulse $pulse,
    ) {}

    public function record(object $event): void
    {
        match (true) {
            $event instanceof ResponseReceived => $this->recordResponse($event),
            $event instanceof RequestFailed => $this->recordFailure($event),
            $event instanceof RateLimitWaited => $this->recordRateLimitWait($event),
            $event instanceof TokenRefreshed => $this->recordTokenRefreshed($event),
            $event instanceof TokenRefreshFailed => $this->recordTokenRefreshFailed($event),
            $event instanceof ConnectionAuthorized => $this->setState($event->connection, Entries::STATE_OK, 'connected'),
            default => null,
        };
    }

    private function recordResponse(ResponseReceived $event): void
    {
        $this->lastAnswer = $event->successful() ? null : $event->connection.' '.$event->endpoint;

        // An answer means the connection works, whatever was recorded before.
        // Written at most once a minute per connection and process.
        if ($event->successful()) {
            $this->setState($event->connection, Entries::STATE_OK, 'answered');
        }

        [$timestamp, $connection, $endpoint, $duration] = [
            CarbonImmutable::now()->getTimestamp(),
            $event->connection,
            $event->endpoint,
            (int) round($event->durationMs),
        ];

        $this->pulse->lazy(function () use ($timestamp, $connection, $endpoint, $duration) {
            if ($this->ignores($connection, $endpoint) || ! $this->shouldSample()) {
                return;
            }

            $group = $this->connectionGroup($connection);
            $key = Entries::key([$group, $endpoint]);

            $this->pulse->record(Entries::REQUEST, $key, $duration, $timestamp)->count()->avg()->max();
            $this->pulse->record(Entries::CONNECTION_REQUEST, $group, $duration, $timestamp)->count()->avg()->max();

            if (! $this->underThreshold($duration, $endpoint)) {
                $this->pulse->record(Entries::SLOW_REQUEST, $key, $duration, $timestamp)->count()->max();
            }
        });
    }

    private function recordFailure(RequestFailed $event): void
    {
        $answered = $this->lastAnswer === $event->connection.' '.$event->endpoint;
        $this->lastAnswer = null;

        $reason = $this->reason($event, $answered);

        if ($reason === Entries::STATE_REAUTHORIZE || $reason === Entries::STATE_NOT_CONNECTED) {
            $this->setState($event->connection, $reason, $event->message);
        }

        [$timestamp, $connection, $endpoint] = [CarbonImmutable::now()->getTimestamp(), $event->connection, $event->endpoint];

        $this->pulse->lazy(function () use ($timestamp, $connection, $endpoint, $reason, $answered) {
            if ($this->ignores($connection, $endpoint)) {
                return;
            }

            $group = $this->connectionGroup($connection);

            $this->pulse->record(Entries::FAILURE, Entries::key([$group, $endpoint, $reason]), null, $timestamp)->count();
            $this->pulse->record(Entries::CONNECTION_FAILURE, $group, null, $timestamp)->count();

            // Not among the requests counted from ResponseReceived: the card
            // adds these to them for the error rate
            if (! $answered) {
                $this->pulse->record(Entries::CONNECTION_UNANSWERED, $group, null, $timestamp)->count();
            }
        });
    }

    private function recordRateLimitWait(RateLimitWaited $event): void
    {
        [$timestamp, $connection, $endpoint, $waited, $gaveUp] = [
            CarbonImmutable::now()->getTimestamp(),
            $event->connection,
            $event->endpoint,
            $event->waitedMs,
            $event->gaveUp,
        ];

        $this->pulse->lazy(function () use ($timestamp, $connection, $endpoint, $waited, $gaveUp) {
            if ($this->ignores($connection, $endpoint)) {
                return;
            }

            $group = $this->connectionGroup($connection);

            if ($waited > 0) {
                $this->pulse->record(Entries::RATE_LIMIT_WAIT, $group, $waited, $timestamp)->count()->sum()->max();
            }

            if ($gaveUp) {
                $this->pulse->record(Entries::RATE_LIMIT_GAVE_UP, $group, null, $timestamp)->count();
            }
        });
    }

    private function recordTokenRefreshed(TokenRefreshed $event): void
    {
        $this->setState($event->connection, Entries::STATE_OK, 'token renewed');

        $this->recordConnectionCount(Entries::TOKEN_REFRESHED, $event->connection);
    }

    private function recordTokenRefreshFailed(TokenRefreshFailed $event): void
    {
        if ($event->reauthorizationRequired) {
            $this->setState($event->connection, Entries::STATE_REAUTHORIZE, $event->reason);
        }

        $this->recordConnectionCount(Entries::TOKEN_REFRESH_FAILED, $event->connection);
    }

    private function recordConnectionCount(string $type, string $connection): void
    {
        $timestamp = CarbonImmutable::now()->getTimestamp();

        $this->pulse->lazy(function () use ($type, $connection, $timestamp) {
            if ($this->ignoresConnection($connection)) {
                return;
            }

            $this->pulse->record($type, $this->connectionGroup($connection), null, $timestamp)->count();
        });
    }

    /**
     * Why a request failed, as it appears in the card: the HTTP status, or
     * what kept the request from reaching the API.
     */
    private function reason(RequestFailed $event, bool $answered): string
    {
        return match (true) {
            $event->exception instanceof ConnectionNeedsReauthorizationException => Entries::STATE_REAUTHORIZE,
            $event->exception instanceof RateLimitExceededException && $event->statusCode === null => 'rate_limit_wait',
            $answered && $event->statusCode !== null => (string) $event->statusCode,
            // 401 before anything was sent: no access token stored
            ! $answered && $event->statusCode === 401 => Entries::STATE_NOT_CONNECTED,
            $event->statusCode === null => 'network',
            default => (string) $event->statusCode,
        };
    }

    /**
     * Pulse values hold the connection's last known state. Written by name,
     * never grouped: the card has to say which connection needs attention.
     */
    private function setState(string $connection, string $state, string $reason): void
    {
        $now = CarbonImmutable::now()->getTimestamp();
        $previous = $this->statesWritten[$connection] ?? null;

        if ($previous !== null && $previous['state'] === $state && $now - $previous['at'] < 60) {
            return;
        }

        $this->statesWritten[$connection] = ['state' => $state, 'at' => $now];

        $this->pulse->lazy(function () use ($connection, $state, $reason, $now) {
            if ($this->ignoresConnection($connection)) {
                return;
            }

            $this->pulse->set(Entries::CONNECTION_STATE, $connection, json_encode([
                'state' => $state,
                'reason' => mb_substr($reason, 0, 255),
                'at' => $now,
            ], JSON_THROW_ON_ERROR), $now);
        });
    }

    private function ignores(string $connection, string $endpoint): bool
    {
        return $this->ignoresConnection($connection) || $this->shouldIgnore($endpoint);
    }

    private function ignoresConnection(string $connection): bool
    {
        foreach ($this->config('connections.ignore', []) as $pattern) {
            if (is_string($pattern) && preg_match($pattern, $connection) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * The name a connection is recorded under: its group's, when a
     * `connections.groups` pattern matches.
     */
    private function connectionGroup(string $connection): string
    {
        foreach ($this->config('connections.groups', []) as $pattern => $replacement) {
            $group = preg_replace((string) $pattern, (string) $replacement, $connection, count: $count);

            if ($count > 0 && $group !== null) {
                return $group;
            }
        }

        return $connection;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function config(string $key, array $default): array
    {
        $value = Config::get('pulse.recorders.'.self::class.'.'.$key, $default);

        return is_array($value) ? $value : $default;
    }
}
