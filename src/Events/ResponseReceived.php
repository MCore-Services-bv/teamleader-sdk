<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Events;

use Closure;
use Error;
use JsonSerializable;

/**
 * The Teamleader API answered — with any status, success or not.
 *
 * An error status also fires RequestFailed, after this event. A request that
 * never got an answer (a connection failure or timeout) fires only
 * RequestFailed.
 *
 * The body is sanitised the first time a listener reads it, not when the
 * event is created. Redacting a 100-row listing takes tens of milliseconds,
 * and listeners that only count or time requests (such as the Pulse recorder)
 * never read it.
 *
 * @property-read array<mixed>|null $body The decoded response body, with sensitive
 *                                        keys redacted; null when it was not JSON
 */
final class ResponseReceived implements JsonSerializable
{
    /** @var array<mixed>|null */
    private ?array $resolvedBody;

    /** @var (Closure(): (array<mixed>|null))|null */
    private ?Closure $bodyResolver;

    /**
     * @param  string  $method  HTTP method, e.g. `POST`
     * @param  string  $endpoint  e.g. `companies.list`
     * @param  int  $statusCode  HTTP status
     * @param  float  $durationMs  From sending the request to reading the body
     * @param  array<mixed>|null  $body  The decoded response body, with sensitive
     *                                   keys redacted; null when it was not JSON
     * @param  string  $connection  The Teamleader connection, `default` for a single account
     * @param  (Closure(): (array<mixed>|null))|null  $bodyUsing  Builds the body on first
     *                                                            read; takes precedence over `$body`
     */
    public function __construct(
        public readonly string $method,
        public readonly string $endpoint,
        public readonly int $statusCode,
        public readonly float $durationMs,
        ?array $body = null,
        public readonly string $connection = 'default',
        ?Closure $bodyUsing = null,
    ) {
        $this->resolvedBody = $body;
        $this->bodyResolver = $bodyUsing;
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * `$event->body`, built on first read and kept.
     */
    public function __get(string $name): mixed
    {
        if ($name !== 'body') {
            trigger_error('Undefined property: '.self::class.'::$'.$name, E_USER_WARNING);

            return null;
        }

        if ($this->bodyResolver !== null) {
            $this->resolvedBody = ($this->bodyResolver)();
            $this->bodyResolver = null;
        }

        return $this->resolvedBody;
    }

    public function __isset(string $name): bool
    {
        return $name === 'body' && $this->__get('body') !== null;
    }

    /**
     * Read-only, like the other properties.
     */
    public function __set(string $name, mixed $value): never
    {
        throw new Error('Cannot modify readonly property '.self::class.'::$'.$name);
    }

    /**
     * The event as plain data, body included. Before v3.4 `body` was a
     * public property, so it appeared wherever an event was turned into an
     * array or JSON; these keep it that way.
     *
     * @return array{method: string, endpoint: string, statusCode: int, durationMs: float, body: array<mixed>|null, connection: string}
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'endpoint' => $this->endpoint,
            'statusCode' => $this->statusCode,
            'durationMs' => $this->durationMs,
            'body' => $this->__get('body'),
            'connection' => $this->connection,
        ];
    }

    /**
     * `json_encode($event)`, and log context (Monolog normalises objects
     * through JSON), include the body as before.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * `dump($event)` and `dd($event)` show the body, not the closure that
     * builds it.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    /**
     * A queued listener serialises the event: the body is built first, since
     * the closure that builds it cannot be serialised.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->method = $data['method'];
        $this->endpoint = $data['endpoint'];
        $this->statusCode = $data['statusCode'];
        $this->durationMs = $data['durationMs'];
        $this->connection = $data['connection'];
        $this->resolvedBody = $data['body'];
        $this->bodyResolver = null;
    }
}
