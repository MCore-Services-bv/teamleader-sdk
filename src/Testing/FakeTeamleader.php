<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Testing;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Testing\Fakes\Fake;
use McoreServices\TeamleaderSDK\Connections\ConnectionConfig;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tokens\ArrayTokenStore;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * A TeamleaderSDK that records requests instead of sending them — the
 * connection behind Teamleader::fake().
 *
 * Resources reach the API only through request(), sendFileContents() and
 * fetchFileContents(), and this class overrides those three. Everything else
 * is the real SDK: the resources build and validate their payloads exactly as
 * in production, so a test that sends something Teamleader would refuse
 * fails here too.
 *
 * Needs no credentials, tokens, Redis or network. Responses come from, in
 * order: queueResponse(), stubs on this connection (stub()), stubs given to
 * Teamleader::fake(), and finally an empty success (`['data' => []]`) — or an
 * exception once Teamleader::preventStrayRequests() is on.
 *
 * A stub is an array (returned as it is), a FakeResponse (a success, or a
 * refusal that throws or returns an error array as the real client would), a
 * closure receiving the body and the endpoint, or a ResponseSequence.
 */
class FakeTeamleader extends TeamleaderSDK implements Fake
{
    use AssertsTeamleaderRequests;

    /** The endpoint name a file upload's second step is recorded under */
    public const UPLOAD_CONTENTS = 'files.upload (contents)';

    /** The endpoint name a document download's second step is recorded under */
    public const DOWNLOAD_CONTENTS = 'download (contents)';

    /** The endpoint name handleCallback() is recorded under */
    public const OAUTH_CALLBACK = 'oauth.callback';

    /**
     * Every recorded call, in order.
     *
     * @var list<array{method: string, endpoint: string, body: array, connection: string}>
     */
    public array $calls = [];

    /** @var list<mixed> Consumed in order before any stub */
    protected array $queuedResponses = [];

    /** Returned when nothing else answers. Mirrors the real shape: `data` plus `headers` */
    protected array $defaultResponse = ['data' => [], 'headers' => []];

    /** @var array<string, mixed> endpoint or pattern => stub, for this connection */
    protected array $stubs = [];

    public function __construct(
        string $connection = 'default',
        array $stubs = [],
        private readonly ?FakeConnectionManager $manager = null,
    ) {
        $config = new ConnectionConfig(
            name: $connection,
            clientId: 'fake-client-id',
            clientSecret: 'fake-client-secret',
            redirectUri: 'http://localhost/teamleader/callback',
        );

        // The real constructor, with a token service that never reads the cache
        // or refreshes: every collaborator exists, and nothing touches the
        // database, the cache, Redis or the network
        $tokens = new class(new ArrayTokenStore, $connection, $config) extends TokenService
        {
            public function getValidAccessToken(): ?string
            {
                return 'fake-access-token';
            }

            public function hasValidTokens(): bool
            {
                return true;
            }

            public function refreshTokenIfNeeded(): ?string
            {
                return 'fake-access-token';
            }

            public function refreshIfDue(int $withinSeconds, bool $force = false): string
            {
                return self::NOT_DUE;
            }
        };

        parent::__construct(
            $tokens,
            null,
            new NullLogger,
            null,
            $config,
        );

        $this->stubs = $stubs;
    }

    // -- answering -------------------------------------------------------------

    /**
     * Stubs for this connection only, on top of those given to Teamleader::fake().
     *
     * @param  array<string, mixed>  $stubs  endpoint or pattern => stub
     */
    public function stub(array $stubs): static
    {
        $this->stubs = $stubs + $this->stubs;

        return $this;
    }

    public function request($method, $endpoint, $data = [])
    {
        $body = (array) $data;
        $this->record((string) $method, (string) $endpoint, $body);

        $answer = $this->answer((string) $endpoint, $body);

        if ($answer === null) {
            return $this->defaultResponse;
        }

        if ($answer instanceof FakeResponse) {
            $result = $answer->toResult();

            if ($answer->isError()) {
                // Throws, or not, exactly as the real client does
                $this->getErrorHandler()->handleApiError($result, (string) $endpoint);
            }

            return $result;
        }

        return (array) $answer;
    }

    public function sendFileContents(string $location, mixed $contents): array
    {
        $this->record('POST', self::UPLOAD_CONTENTS, [
            'location' => $location,
            'contents' => is_resource($contents) ? (string) stream_get_contents($contents) : (string) $contents,
        ]);

        $answer = $this->answer(self::UPLOAD_CONTENTS, $this->lastBody());

        if ($answer instanceof FakeResponse) {
            if ($answer->isError()) {
                // Like the real upload: a refusal always throws
                throw new TeamleaderException(
                    'Teamleader refused the file upload (HTTP '.$answer->getStatus().'): '.$answer->toResult()['message'],
                    $answer->getStatus(), null, [], $answer->getStatus(),
                );
            }

            return $answer->toResult();
        }

        return $answer === null ? $this->defaultResponse : (array) $answer;
    }

    public function fetchFileContents(string $location): string
    {
        $this->record('GET', self::DOWNLOAD_CONTENTS, ['location' => $location]);

        $answer = $this->answer(self::DOWNLOAD_CONTENTS, $this->lastBody());

        if ($answer instanceof FakeResponse) {
            if ($answer->isError()) {
                throw new TeamleaderException(
                    'Teamleader refused the download (HTTP '.$answer->getStatus().'): '.$answer->toResult()['message'],
                    $answer->getStatus(), null, [], $answer->getStatus(),
                );
            }

            return (string) ($answer->toResult()['contents'] ?? '');
        }

        if (is_array($answer)) {
            return (string) ($answer['contents'] ?? '');
        }

        return $answer === null ? 'fake file contents' : (string) $answer;
    }

    /**
     * Completes the OAuth flow without Teamleader: records the code and state
     * and returns this connection — or false when the stub for
     * `oauth.callback` is a refusal. The state is not checked.
     */
    public function handleCallback(string $code, ?string $state = null): TeamleaderSDK|false
    {
        $this->record('POST', self::OAUTH_CALLBACK, ['code' => $code, 'state' => $state]);

        $answer = $this->answer(self::OAUTH_CALLBACK, $this->lastBody());

        return $answer instanceof FakeResponse && $answer->isError() ? false : $this;
    }

    public function isAuthenticated()
    {
        return true;
    }

    public function identifyAccountIfUnknown(): bool
    {
        return false;
    }

    public function getRateLimitStats(): array
    {
        return [
            'current_usage' => 0,
            'rate_limit' => 200,
            'usage_percentage' => 0.0,
            'remaining' => 200,
            'reset_time' => null,
            'seconds_until_reset' => 0,
            'throttle_level' => 'none',
        ];
    }

    /**
     * The next answer for an endpoint, or null when nothing answers it.
     *
     * @throws RuntimeException When stray requests are prevented and nothing answers
     */
    protected function answer(string $endpoint, array $body): mixed
    {
        if ($this->queuedResponses !== []) {
            return $this->unwrap(array_shift($this->queuedResponses), $endpoint, $body);
        }

        foreach ([$this->stubs, $this->manager?->stubs() ?? []] as $stubs) {
            $found = $this->findStub($stubs, $endpoint);

            if ($found !== null) {
                return $this->unwrap($found, $endpoint, $body);
            }
        }

        if ($this->manager?->preventsStrayRequests()) {
            throw new RuntimeException(
                "Unexpected Teamleader request to [{$endpoint}] on connection '{$this->connectionName()}'. "
                .'Stub it in Teamleader::fake([...]), or allow unstubbed requests.'
            );
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $stubs
     */
    private function findStub(array $stubs, string $endpoint): mixed
    {
        if (array_key_exists($endpoint, $stubs)) {
            return $stubs[$endpoint];
        }

        foreach ($stubs as $pattern => $stub) {
            if (Str::is((string) $pattern, $endpoint)) {
                return $stub;
            }
        }

        return null;
    }

    private function unwrap(mixed $stub, string $endpoint, array $body): mixed
    {
        while ($stub instanceof Closure || $stub instanceof ResponseSequence) {
            $stub = $stub instanceof Closure ? $stub($body, $endpoint) : $stub->next($endpoint);
        }

        return $stub;
    }

    private function record(string $method, string $endpoint, array $body): void
    {
        $call = ['method' => $method, 'endpoint' => $endpoint, 'body' => $body, 'connection' => $this->connectionName()];

        $this->calls[] = $call;
        $this->manager?->record($call);
    }

    protected function recordedCalls(): array
    {
        return $this->calls;
    }

    // -- queueing (in order, before any stub) -----------------------------------

    /**
     * Answer the next request with this, whatever its endpoint — including the
     * second step of an upload or a download. To answer one endpoint, stub it.
     */
    public function queueResponse(array|FakeResponse $response): static
    {
        $this->queuedResponses[] = $response;

        return $this;
    }

    /**
     * @param  list<array|FakeResponse>  $responses
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
     * @param  list<array>  $records
     */
    public function queueListResponse(array $records): static
    {
        return $this->queueResponse(['data' => $records, 'headers' => []]);
    }

    /** Change the response returned when nothing else answers */
    public function setDefaultResponse(array $response): static
    {
        $this->defaultResponse = $response;

        return $this;
    }

    // -- reading what was sent ----------------------------------------------------

    public function callCount(): int
    {
        return count($this->calls);
    }

    /**
     * @return array{method: string, endpoint: string, body: array, connection: string}|null
     */
    public function lastCall(): ?array
    {
        return $this->calls === [] ? null : $this->calls[array_key_last($this->calls)];
    }

    public function lastBody(): array
    {
        return $this->lastCall()['body'] ?? [];
    }

    public function lastEndpoint(): ?string
    {
        return $this->lastCall()['endpoint'] ?? null;
    }

    public function lastMethod(): ?string
    {
        return $this->lastCall()['method'] ?? null;
    }

    /**
     * Every endpoint called, in order.
     *
     * @return list<string>
     */
    public function endpoints(): array
    {
        return array_column($this->calls, 'endpoint');
    }

    /** Forget all recorded calls and queued responses; stubs stay */
    public function reset(): static
    {
        $this->calls = [];
        $this->queuedResponses = [];

        return $this;
    }
}
