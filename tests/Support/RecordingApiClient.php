<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Support;

use McoreServices\TeamleaderSDK\TeamleaderSDK;

/**
 * A TeamleaderSDK that records requests instead of sending them.
 *
 * Resources are constructed with a TeamleaderSDK instance and call
 * $this->api->request(...) to reach the API. Substituting this class lets a test
 * assert on the payload a resource *builds* without any network, token or Redis
 * involvement — request() is overridden wholesale, so none of the token
 * resolution, rate limiting or error handling in the parent runs.
 *
 * This is the layer most of the SDK's historical bugs have lived in: a wrong
 * body key (`include` instead of `includes`), a wrong shape (a sort string array
 * instead of objects), or a filter that was silently dropped. The API answers
 * 200 to all of them, so only a payload assertion catches them.
 *
 * Usage:
 *
 *     $api = new RecordingApiClient;
 *     $files = new Files($api);
 *
 *     $files->forProduct('product-uuid');
 *
 *     $api->lastBody();      // the array that would have been sent
 *     $api->lastEndpoint();  // 'files.list'
 *
 * Responses default to an empty successful list. Queue specific ones when the
 * code under test reads what comes back:
 *
 *     $api->queueResponse(['data' => [['id' => 'abc']], 'headers' => []]);
 */
class RecordingApiClient extends TeamleaderSDK
{
    /**
     * Every recorded call, in order.
     *
     * Each entry is ['method' => string, 'endpoint' => string, 'body' => array].
     *
     * @var array<int, array{method: string, endpoint: string, body: array}>
     */
    public array $calls = [];

    /**
     * Responses to return, consumed in order. Falls back to
     * $defaultResponse once exhausted.
     *
     * @var array<int, array>
     */
    protected array $queuedResponses = [];

    /**
     * Returned when no response has been queued.
     *
     * Mirrors the real shape: `data` from the API, `headers` added by the SDK.
     */
    protected array $defaultResponse = [
        'data' => [],
        'headers' => [],
    ];

    /**
     * Record the call and return a canned response instead of dispatching it.
     *
     * Signature intentionally matches TeamleaderSDK::request() exactly,
     * including the lack of type declarations.
     *
     * @param  string  $method  HTTP method
     * @param  string  $endpoint  Endpoint path, e.g. 'files.list'
     * @param  array  $data  Request body
     */
    public function request($method, $endpoint, $data = [])
    {
        $this->calls[] = [
            'method' => $method,
            'endpoint' => $endpoint,
            'body' => $data,
        ];

        if ($this->queuedResponses !== []) {
            return array_shift($this->queuedResponses);
        }

        return $this->defaultResponse;
    }

    /**
     * Queue a single response to be returned by the next request().
     */
    public function queueResponse(array $response): static
    {
        $this->queuedResponses[] = $response;

        return $this;
    }

    /**
     * Queue several responses, returned in order.
     *
     * @param  array<int, array>  $responses
     */
    public function queueResponses(array $responses): static
    {
        foreach ($responses as $response) {
            $this->queueResponse($response);
        }

        return $this;
    }

    /**
     * Queue a list response containing the given records.
     *
     * @param  array<int, array>  $records
     */
    public function queueListResponse(array $records): static
    {
        return $this->queueResponse([
            'data' => $records,
            'headers' => [],
        ]);
    }

    /**
     * Change the response returned once the queue is empty.
     */
    public function setDefaultResponse(array $response): static
    {
        $this->defaultResponse = $response;

        return $this;
    }

    /**
     * How many requests were recorded.
     */
    public function callCount(): int
    {
        return count($this->calls);
    }

    /**
     * The most recent call, or null if nothing was recorded.
     *
     * @return array{method: string, endpoint: string, body: array}|null
     */
    public function lastCall(): ?array
    {
        return $this->calls === [] ? null : $this->calls[array_key_last($this->calls)];
    }

    /**
     * The body of the most recent call. Empty array if nothing was recorded.
     */
    public function lastBody(): array
    {
        return $this->lastCall()['body'] ?? [];
    }

    /**
     * The endpoint of the most recent call.
     */
    public function lastEndpoint(): ?string
    {
        return $this->lastCall()['endpoint'] ?? null;
    }

    /**
     * The HTTP method of the most recent call.
     */
    public function lastMethod(): ?string
    {
        return $this->lastCall()['method'] ?? null;
    }

    /**
     * Every endpoint called, in order.
     *
     * @return array<int, string>
     */
    public function endpoints(): array
    {
        return array_column($this->calls, 'endpoint');
    }

    /**
     * Forget all recorded calls and queued responses.
     */
    public function reset(): static
    {
        $this->calls = [];
        $this->queuedResponses = [];

        return $this;
    }
}
