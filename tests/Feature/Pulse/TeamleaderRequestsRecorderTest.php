<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature\Pulse;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Facades\Pulse;
use McoreServices\TeamleaderSDK\Events\ConnectionAuthorized;
use McoreServices\TeamleaderSDK\Events\RateLimitWaited;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Events\TokenRefreshed;
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;
use McoreServices\TeamleaderSDK\Exceptions\ConnectionNeedsReauthorizationException;
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;
use McoreServices\TeamleaderSDK\Pulse\Entries;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderRequests;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;

#[Group('pulse')]
final class TeamleaderRequestsRecorderTest extends PulseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-19 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // -- requests -------------------------------------------------------------

    public function test_a_response_is_recorded_per_connection_and_endpoint(): void
    {
        event(new ResponseReceived('POST', 'deals.list', 200, 120.4, connection: 'brand-a'));
        event(new ResponseReceived('POST', 'deals.list', 200, 279.6, connection: 'brand-a'));
        event(new ResponseReceived('POST', 'deals.list', 200, 50.0, connection: 'brand-b'));

        $key = Entries::key(['brand-a', 'deals.list']);
        $aggregates = $this->aggregates(Entries::REQUEST);

        $this->assertSame(2.0, $aggregates[$key.'|count']);
        $this->assertSame(200.0, $aggregates[$key.'|avg']);
        $this->assertSame(280.0, $aggregates[$key.'|max']);
        $this->assertSame(1.0, $aggregates[Entries::key(['brand-b', 'deals.list']).'|count']);

        $perConnection = $this->aggregates(Entries::CONNECTION_REQUEST);
        $this->assertSame(2.0, $perConnection['brand-a|count']);
        $this->assertSame(1.0, $perConnection['brand-b|count']);
    }

    public function test_only_requests_over_the_threshold_are_slow(): void
    {
        Config::set('pulse.recorders.'.TeamleaderRequests::class.'.threshold', 500);

        event(new ResponseReceived('POST', 'invoices.list', 200, 499.0));
        event(new ResponseReceived('POST', 'invoices.list', 200, 1500.0));

        $slow = $this->entries(Entries::SLOW_REQUEST);

        $this->assertCount(1, $slow);
        $this->assertSame(1500, (int) $slow[0]->value);
        $this->assertSame(Entries::key(['default', 'invoices.list']), $slow[0]->key);
    }

    public function test_a_threshold_can_be_set_per_endpoint(): void
    {
        Config::set('pulse.recorders.'.TeamleaderRequests::class.'.threshold', [
            '/^files\./' => 5000,
            'default' => 500,
        ]);

        event(new ResponseReceived('POST', 'files.upload', 200, 3000.0));
        event(new ResponseReceived('POST', 'deals.info', 200, 600.0));

        $this->assertSame([Entries::key(['default', 'deals.info'])], $this->entries(Entries::SLOW_REQUEST)->pluck('key')->all());
    }

    public function test_the_body_is_never_read(): void
    {
        $read = false;

        event(new ResponseReceived('POST', 'companies.list', 200, 10.0, bodyUsing: function () use (&$read) {
            $read = true;

            return ['data' => []];
        }));

        $this->assertCount(1, $this->entries(Entries::REQUEST));
        $this->assertFalse($read);
    }

    public function test_sampling_applies_to_requests_but_never_to_failures(): void
    {
        Config::set('pulse.recorders.'.TeamleaderRequests::class.'.sample_rate', 0);

        event(new ResponseReceived('POST', 'deals.update', 422, 80.0));
        event(new RequestFailed('POST', 'deals.update', 422, 'Invalid'));

        $this->assertCount(0, $this->entries(Entries::REQUEST));
        $this->assertCount(1, $this->entries(Entries::FAILURE));
    }

    // -- failures -------------------------------------------------------------

    public function test_an_error_status_is_a_failure_with_its_status(): void
    {
        event(new ResponseReceived('POST', 'deals.update', 422, 80.0, connection: 'brand-a'));
        event(new RequestFailed('POST', 'deals.update', 422, 'Invalid', connection: 'brand-a'));

        $this->assertSame([Entries::key(['brand-a', 'deals.update', '422'])], $this->entries(Entries::FAILURE)->pluck('key')->all());
        $this->assertSame(['brand-a'], $this->entries(Entries::CONNECTION_FAILURE)->pluck('key')->all());
        $this->assertCount(1, $this->entries(Entries::REQUEST));
    }

    public function test_a_failure_without_an_answer_is_a_network_failure(): void
    {
        $exception = new ConnectException('Timed out', new Request('POST', 'deals.list'));

        event(new RequestFailed('POST', 'deals.list', null, 'Timed out', $exception));

        $this->assertSame([Entries::key(['default', 'deals.list', 'network'])], $this->entries(Entries::FAILURE)->pluck('key')->all());
        $this->assertCount(0, $this->entries(Entries::REQUEST));
    }

    public function test_a_401_before_sending_means_the_connection_has_no_tokens(): void
    {
        event(new RequestFailed('POST', 'deals.list', 401, 'No access token available. Please connect to Teamleader first.', connection: 'app-leadsync'));

        $this->assertSame([Entries::key(['app-leadsync', 'deals.list', 'not_connected'])], $this->entries(Entries::FAILURE)->pluck('key')->all());
        $this->assertSame(Entries::STATE_NOT_CONNECTED, $this->states()['app-leadsync']['state']);
    }

    public function test_a_401_from_the_api_is_an_ordinary_failure(): void
    {
        event(new ResponseReceived('POST', 'deals.list', 401, 30.0, connection: 'brand-a'));
        event(new RequestFailed('POST', 'deals.list', 401, 'Unauthorized', connection: 'brand-a'));

        $this->assertSame([Entries::key(['brand-a', 'deals.list', '401'])], $this->entries(Entries::FAILURE)->pluck('key')->all());
        $this->assertSame([], $this->states());
    }

    public function test_a_previous_success_on_the_same_endpoint_does_not_count_as_an_answer(): void
    {
        event(new ResponseReceived('POST', 'deals.list', 200, 30.0, connection: 'brand-a'));
        event(new RequestFailed('POST', 'deals.list', 401, 'No access token available.', connection: 'brand-a'));

        $this->assertSame([Entries::key(['brand-a', 'deals.list', 'not_connected'])], $this->entries(Entries::FAILURE)->pluck('key')->all());
    }

    public function test_a_connection_that_needs_reauthorization_is_flagged(): void
    {
        $exception = new ConnectionNeedsReauthorizationException('brand-a');

        event(new RequestFailed('POST', 'deals.list', 401, $exception->getMessage(), $exception, connection: 'brand-a'));

        $this->assertSame([Entries::key(['brand-a', 'deals.list', 'reauthorize'])], $this->entries(Entries::FAILURE)->pluck('key')->all());
        $this->assertSame(Entries::STATE_REAUTHORIZE, $this->states()['brand-a']['state']);
    }

    public function test_a_rate_limit_wait_that_ran_out_is_its_own_reason(): void
    {
        $exception = new RateLimitExceededException('Rate limit window did not clear', 5);

        event(new RateLimitWaited(5000, 99.5, 'deals.list', true, connection: 'brand-a'));
        event(new RequestFailed('POST', 'deals.list', null, $exception->getMessage(), $exception, connection: 'brand-a'));

        $this->assertSame([Entries::key(['brand-a', 'deals.list', 'rate_limit_wait'])], $this->entries(Entries::FAILURE)->pluck('key')->all());
        $this->assertSame(['brand-a'], $this->entries(Entries::RATE_LIMIT_GAVE_UP)->pluck('key')->all());
    }

    // -- rate limit -------------------------------------------------------------

    public function test_rate_limit_waits_are_summed_per_connection(): void
    {
        event(new RateLimitWaited(1000, 90.0, 'deals.list', connection: 'brand-a'));
        event(new RateLimitWaited(2500, 95.0, 'deals.info', connection: 'brand-a'));

        $aggregates = $this->aggregates(Entries::RATE_LIMIT_WAIT);

        $this->assertSame(2.0, $aggregates['brand-a|count']);
        $this->assertSame(3500.0, $aggregates['brand-a|sum']);
        $this->assertSame(2500.0, $aggregates['brand-a|max']);
        $this->assertCount(0, $this->entries(Entries::RATE_LIMIT_GAVE_UP));
    }

    // -- tokens and connection state ---------------------------------------------

    public function test_a_refused_refresh_token_flags_the_connection_until_it_is_connected_again(): void
    {
        event(new TokenRefreshFailed('invalid_grant', 400, true, connection: 'brand-a'));

        $this->assertSame(Entries::STATE_REAUTHORIZE, $this->states()['brand-a']['state']);
        $this->assertSame('invalid_grant', $this->states()['brand-a']['reason']);
        $this->assertCount(1, $this->entries(Entries::TOKEN_REFRESH_FAILED));

        Carbon::setTestNow('2026-10-19 10:05:00');
        event(new ConnectionAuthorized('brand-a', 'account-1', 'Brand A'));

        $this->assertSame(Entries::STATE_OK, $this->states()['brand-a']['state']);
    }

    public function test_a_failed_refresh_that_can_be_retried_does_not_flag_the_connection(): void
    {
        event(new TokenRefreshFailed('Connection timed out', null, false, connection: 'brand-a'));

        $this->assertSame([], $this->states());
        $this->assertCount(1, $this->entries(Entries::TOKEN_REFRESH_FAILED));
    }

    public function test_a_renewed_token_clears_the_flag(): void
    {
        event(new TokenRefreshFailed('invalid_grant', 400, true, connection: 'brand-a'));
        $this->states();

        Carbon::setTestNow('2026-10-19 10:01:00');
        event(new TokenRefreshed(3600, connection: 'brand-a'));

        $this->assertSame(Entries::STATE_OK, $this->states()['brand-a']['state']);
        $this->assertCount(1, $this->entries(Entries::TOKEN_REFRESHED));
    }

    public function test_a_successful_answer_clears_the_flag(): void
    {
        event(new RequestFailed('POST', 'deals.list', 401, 'No access token available.', connection: 'brand-a'));
        $this->assertSame(Entries::STATE_NOT_CONNECTED, $this->states()['brand-a']['state']);

        Carbon::setTestNow('2026-10-19 10:01:00');
        event(new ResponseReceived('POST', 'deals.list', 200, 30.0, connection: 'brand-a'));

        $this->assertSame(Entries::STATE_OK, $this->states()['brand-a']['state']);
    }

    public function test_failures_that_never_reached_the_api_are_counted_apart(): void
    {
        event(new ResponseReceived('POST', 'deals.update', 422, 80.0, connection: 'brand-a'));
        event(new RequestFailed('POST', 'deals.update', 422, 'Invalid', connection: 'brand-a'));
        event(new RequestFailed('POST', 'deals.list', null, 'Timed out', connection: 'brand-a', durationMs: 30000.0));

        $this->assertSame(2.0, $this->aggregates(Entries::CONNECTION_FAILURE)['brand-a|count']);
        $this->assertSame(1.0, $this->aggregates(Entries::CONNECTION_UNANSWERED)['brand-a|count']);
    }

    public function test_a_connection_that_keeps_failing_writes_its_state_once_a_minute(): void
    {
        foreach (range(1, 5) as $i) {
            event(new RequestFailed('POST', 'deals.list', 401, 'No access token available.', connection: 'app-invoicegen'));
        }

        $this->entries(Entries::FAILURE);

        $this->assertSame(1, Pulse::ignore(fn () => DB::table('pulse_values')->count()));
        $this->assertSame(5.0, $this->aggregates(Entries::FAILURE)[Entries::key(['app-invoicegen', 'deals.list', 'not_connected']).'|count']);
    }

    // -- configuration ------------------------------------------------------------

    public function test_connections_can_be_grouped(): void
    {
        Config::set('pulse.recorders.'.TeamleaderRequests::class.'.connections.groups', ['/^site-.+$/' => 'site-*']);

        event(new ResponseReceived('POST', 'contacts.list', 200, 10.0, connection: 'site-01M4K9S27GCXCR3FGQ46WFK6W5'));
        event(new ResponseReceived('POST', 'contacts.list', 200, 10.0, connection: 'site-01HX0000000000000000000000'));
        event(new ResponseReceived('POST', 'contacts.list', 200, 10.0, connection: 'mcore-billing'));

        $this->assertSame(
            ['mcore-billing' => 1, 'site-*' => 2],
            $this->entries(Entries::CONNECTION_REQUEST)->countBy('key')->sortKeys()->all(),
        );
    }

    public function test_a_connection_state_is_never_grouped(): void
    {
        Config::set('pulse.recorders.'.TeamleaderRequests::class.'.connections.groups', ['/^site-.+$/' => 'site-*']);

        event(new TokenRefreshFailed('invalid_grant', 400, true, connection: 'site-01M4K9S27GCXCR3FGQ46WFK6W5'));

        $this->assertSame(['site-01M4K9S27GCXCR3FGQ46WFK6W5'], array_keys($this->states()));
        $this->assertSame(['site-*'], $this->entries(Entries::TOKEN_REFRESH_FAILED)->pluck('key')->all());
    }

    public function test_connections_and_endpoints_can_be_ignored(): void
    {
        Config::set('pulse.recorders.'.TeamleaderRequests::class.'.ignore', ['/^webhooks\./']);
        Config::set('pulse.recorders.'.TeamleaderRequests::class.'.connections.ignore', ['/^sandbox$/']);

        event(new ResponseReceived('POST', 'webhooks.list', 200, 10.0));
        event(new ResponseReceived('POST', 'deals.list', 200, 10.0, connection: 'sandbox'));
        event(new TokenRefreshFailed('invalid_grant', 400, true, connection: 'sandbox'));
        event(new ResponseReceived('POST', 'deals.list', 200, 10.0));

        $this->assertSame([Entries::key(['default', 'deals.list'])], $this->entries(Entries::REQUEST)->pluck('key')->all());
        $this->assertArrayNotHasKey('sandbox', $this->states());
        $this->assertCount(0, $this->entries(Entries::TOKEN_REFRESH_FAILED));
    }

    // -- through the SDK -----------------------------------------------------------

    public function test_requests_through_the_sdk_are_recorded(): void
    {
        config(['teamleader.api.retry_attempts' => 1, 'teamleader.error_handling.throw_exceptions' => false]);

        $sdk = new TeamleaderSDK;
        $sdk->setAccessToken('test-token');

        (new ReflectionProperty($sdk, 'client'))->setValue($sdk, new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, [], '{"data":[{"id":"abc"}]}'),
                new Response(422, [], '{"errors":[{"title":"Invalid"}]}'),
            ])),
            'http_errors' => false,
        ]));

        $sdk->request('POST', 'companies.list', []);
        $sdk->request('POST', 'companies.add', ['name' => 'Acme']);

        $this->assertSame(
            [Entries::key(['default', 'companies.list']), Entries::key(['default', 'companies.add'])],
            $this->entries(Entries::REQUEST)->pluck('key')->all(),
        );
        $this->assertSame([Entries::key(['default', 'companies.add', '422'])], $this->entries(Entries::FAILURE)->pluck('key')->all());
    }
}
