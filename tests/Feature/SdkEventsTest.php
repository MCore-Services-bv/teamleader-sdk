<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use McoreServices\TeamleaderSDK\Events\RateLimitWaited;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Events\RequestSending;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Events\TokenRefreshed;
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;
use McoreServices\TeamleaderSDK\Listeners\LogApiTraffic;
use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\TeamleaderServiceProvider;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use Mockery;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Throwable;

/**
 * v3.0 (F2): the SDK fires events for requests, responses, failures,
 * rate-limit waits and token refreshes.
 *
 * These go through the real TeamleaderSDK::request() — only Guzzle's handler
 * is replaced — because that is where the events are fired. Listeners are
 * registered with Event::listen() rather than Event::fake(): the SDK skips
 * building an event nobody listens to, and a fake reports no listeners.
 */
final class SdkEventsTest extends TestCase
{
    /** @var list<object> */
    private array $fired = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([RequestSending::class, ResponseReceived::class, RequestFailed::class, RateLimitWaited::class, TokenRefreshed::class, TokenRefreshFailed::class] as $event) {
            Event::listen($event, function (object $e) {
                $this->fired[] = $e;
            });
        }

        // No retries, so a failure fires its events once
        config(['teamleader.api.retry_attempts' => 1, 'teamleader.error_handling.throw_exceptions' => false]);
    }

    // -- requests ---------------------------------------------------------------

    public function test_a_successful_request_fires_sending_then_received(): void
    {
        $sdk = $this->sdk([new Response(200, [], '{"data":[{"id":"abc"}]}')]);

        $sdk->request('POST', 'companies.list', ['filter' => ['term' => 'Acme']]);

        $this->assertSame([RequestSending::class, ResponseReceived::class], $this->firedClasses());

        [$sending, $received] = $this->fired;
        $this->assertSame('companies.list', $sending->endpoint);
        $this->assertSame(['filter' => ['term' => 'Acme']], $sending->body);
        $this->assertSame(200, $received->statusCode);
        $this->assertTrue($received->successful());
        $this->assertSame([['id' => 'abc']], $received->body['data']);
        $this->assertGreaterThanOrEqual(0.0, $received->durationMs);
    }

    public function test_sensitive_keys_are_redacted_from_the_body(): void
    {
        $sdk = $this->sdk([new Response(200, [], '{"data":{"access_token":"secret-value"}}')]);

        $sdk->request('POST', 'something.do', ['password' => 'hunter2', 'name' => 'Acme']);

        $this->assertNotSame('hunter2', $this->fired[0]->body['password']);
        $this->assertSame('Acme', $this->fired[0]->body['name']);
        $this->assertNotSame('secret-value', $this->fired[1]->body['data']['access_token']);
    }

    public function test_an_error_status_fires_received_then_failed(): void
    {
        $sdk = $this->sdk([new Response(422, [], '{"errors":[{"title":"name is required"}]}')]);

        $sdk->request('POST', 'companies.add', []);

        $this->assertSame([RequestSending::class, ResponseReceived::class, RequestFailed::class], $this->firedClasses());
        $this->assertFalse($this->fired[1]->successful());
        $this->assertSame(422, $this->fired[2]->statusCode);
        $this->assertSame('name is required', $this->fired[2]->message);
    }

    public function test_a_connection_failure_fires_failed_without_a_status(): void
    {
        $sdk = $this->sdk([new ConnectException('Connection refused', new Request('POST', 'companies.list'))]);

        $sdk->request('POST', 'companies.list', []);

        $this->assertSame([RequestSending::class, RequestFailed::class], $this->firedClasses());
        $this->assertNull($this->fired[1]->statusCode);
        $this->assertInstanceOf(ConnectException::class, $this->fired[1]->exception);
    }

    public function test_no_access_token_fires_failed_and_sends_nothing(): void
    {
        $sdk = $this->sdk([]);
        $sdk->logout();

        $sdk->request('POST', 'companies.list', []);

        $this->assertSame([RequestFailed::class], $this->firedClasses());
        $this->assertSame(401, $this->fired[0]->statusCode);
    }

    public function test_a_request_does_not_touch_redis_when_rate_limiting_is_off(): void
    {
        // The test environment points Redis at a port where nothing listens
        // unless the redis group is running. Any Redis call would throw here.
        config(['teamleader.rate_limiting.enabled' => false, 'database.redis.default.port' => 1]);
        app('redis')->purge();

        $sdk = $this->sdk([new Response(200, ['X-RateLimit-Remaining' => ['150']], '{"data":[]}')]);

        $this->assertSame([], $sdk->request('POST', 'companies.list', [])['data']);
    }

    public function test_nothing_is_built_without_listeners(): void
    {
        Event::forget(RequestSending::class);
        $sdk = $this->sdk([new Response(200, [], '{"data":[]}')]);

        $sdk->request('POST', 'companies.list', []);

        $this->assertSame([ResponseReceived::class], $this->firedClasses());
    }

    // -- rate limit -------------------------------------------------------------

    public function test_a_throttled_request_fires_rate_limit_waited(): void
    {
        $sdk = $this->sdk([new Response(200, [], '{"data":[]}')], $this->limiter(canProceed: true, delayMs: 5));

        $sdk->request('POST', 'companies.list', []);

        $waited = $this->firedOf(RateLimitWaited::class);
        $this->assertCount(1, $waited);
        $this->assertSame(5, $waited[0]->waitedMs);
        $this->assertSame(85.0, $waited[0]->usagePercentage);
        $this->assertFalse($waited[0]->gaveUp);
    }

    public function test_giving_up_on_the_window_fires_waited_and_failed(): void
    {
        config(['teamleader.rate_limiting.max_wait_ms' => 0]);
        $sdk = $this->sdk([], $this->limiter(canProceed: false, delayMs: 1000));

        try {
            $sdk->request('POST', 'companies.list', []);
            $this->fail('Expected a RateLimitExceededException.');
        } catch (RateLimitExceededException) {
        }

        $this->assertSame([RateLimitWaited::class, RequestFailed::class], $this->firedClasses());
        $this->assertTrue($this->fired[0]->gaveUp);
        $this->assertInstanceOf(RateLimitExceededException::class, $this->fired[1]->exception);
    }

    // -- tokens -----------------------------------------------------------------

    public function test_a_successful_refresh_fires_token_refreshed_without_token_values(): void
    {
        $service = $this->tokenServiceAnswering(new Response(200, [], json_encode([
            'access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires_in' => 3600, 'token_type' => 'Bearer',
        ])));

        $this->assertSame('new-access', $service->refreshTokenIfNeeded());

        $refreshed = $this->firedOf(TokenRefreshed::class);
        $this->assertCount(1, $refreshed);
        $this->assertSame(3600, $refreshed[0]->expiresIn);
        $this->assertStringNotContainsString('new-', serialize($refreshed[0]));
    }

    public function test_a_refused_refresh_token_requires_reauthorization(): void
    {
        $service = $this->tokenServiceAnswering(new Response(400, [], '{"error":"invalid_grant"}'));

        $this->assertNull($service->refreshTokenIfNeeded());

        $failed = $this->firedOf(TokenRefreshFailed::class);
        $this->assertCount(1, $failed);
        $this->assertSame(400, $failed[0]->statusCode);
        $this->assertTrue($failed[0]->reauthorizationRequired);
    }

    // -- logging listener -------------------------------------------------------

    public function test_log_requests_and_log_responses_register_the_listener(): void
    {
        $this->assertFalse($this->listensWithTrafficLogger(RequestSending::class));

        config(['teamleader.logging.log_requests' => true, 'teamleader.logging.log_responses' => true]);
        $this->app->register(TeamleaderServiceProvider::class, true);

        $this->assertTrue($this->listensWithTrafficLogger(RequestSending::class));
        $this->assertTrue($this->listensWithTrafficLogger(ResponseReceived::class));
    }

    public function test_the_traffic_logger_writes_to_the_configured_channel(): void
    {
        config(['teamleader.logging.channel' => 'teamleader-test', 'logging.channels.teamleader-test' => ['driver' => 'null']]);

        $channel = Mockery::mock(LoggerInterface::class);
        $channel->shouldReceive('debug')->once()->with('Teamleader request: POST companies.list', ['body' => ['a' => 1]]);
        Log::shouldReceive('channel')->once()->with('teamleader-test')->andReturn($channel);

        (new LogApiTraffic)->requestSending(new RequestSending('POST', 'companies.list', ['a' => 1]));
    }

    // -- helpers ----------------------------------------------------------------

    /**
     * @param  list<Response|Throwable>  $responses
     */
    private function sdk(array $responses, ?ApiRateLimiterService $limiter = null): TeamleaderSDK
    {
        $sdk = new TeamleaderSDK(null, $limiter);
        $sdk->setAccessToken('test-token');

        (new ReflectionProperty($sdk, 'client'))->setValue($sdk, new Client([
            'handler' => HandlerStack::create(new MockHandler($responses)),
            'http_errors' => false,
        ]));

        if ($limiter !== null) {
            config(['teamleader.rate_limiting.enabled' => true]);
        }

        return $sdk;
    }

    private function limiter(bool $canProceed, int $delayMs): ApiRateLimiterService
    {
        return new class($canProceed, $delayMs) extends ApiRateLimiterService
        {
            public function __construct(private bool $canProceed, private int $delayMs)
            {
                parent::__construct();
            }

            public function checkAndThrottle(): array
            {
                return [
                    'can_proceed' => $this->canProceed,
                    'delay_applied' => $this->delayMs,
                    'usage_percentage' => 85.0,
                    'throttle_level' => 'moderate',
                    'reason' => 'test',
                    'reset_time' => null,
                ];
            }

            public function recordRequest(): void {}

            public function updateFromResponseHeaders(array $headers): void {}

            public function getStatistics(): array
            {
                return [];
            }
        };
    }

    private function tokenServiceAnswering(Response $response): TokenService
    {
        $service = new TokenService;
        $service->storeTokens(['access_token' => 'old-access', 'refresh_token' => 'old-refresh', 'expires_in' => 60]);
        $this->fired = [];

        (new ReflectionProperty($service, 'httpClient'))->setValue($service, new Client([
            'handler' => HandlerStack::create(new MockHandler([$response])),
        ]));

        return $service;
    }

    private function listensWithTrafficLogger(string $event): bool
    {
        foreach (Event::getRawListeners()[$event] ?? [] as $listener) {
            if (is_array($listener) && ($listener[0] ?? null) === LogApiTraffic::class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<class-string>
     */
    private function firedClasses(): array
    {
        return array_map(fn (object $e) => $e::class, $this->fired);
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return list<T>
     */
    private function firedOf(string $class): array
    {
        return array_values(array_filter($this->fired, fn (object $e) => $e instanceof $class));
    }
}
