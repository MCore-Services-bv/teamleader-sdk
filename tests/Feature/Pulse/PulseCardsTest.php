<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature\Pulse;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;
use McoreServices\TeamleaderSDK\Events\ConnectionAuthorized;
use McoreServices\TeamleaderSDK\Events\RateLimitWaited;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Events\TokenRefreshed;
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;
use McoreServices\TeamleaderSDK\Pulse\Entries;
use McoreServices\TeamleaderSDK\Pulse\Livewire\Callers;
use McoreServices\TeamleaderSDK\Pulse\Livewire\Connections;
use McoreServices\TeamleaderSDK\Pulse\Livewire\Requests;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderRequests;
use PHPUnit\Framework\Attributes\Group;

#[Group('pulse')]
final class PulseCardsTest extends PulseTestCase
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

    // -- registration -------------------------------------------------------------

    public function test_the_cards_are_registered_under_their_names(): void
    {
        // As on a dashboard: lazy, so each renders its placeholder first
        $html = Blade::render('<livewire:teamleader.requests cols="8" /><livewire:teamleader.connections cols="4" limit="20" /><livewire:teamleader.callers cols="full" />');

        $this->assertStringContainsString('wire:name="teamleader.requests"', $html);
        $this->assertStringContainsString('wire:name="teamleader.connections"', $html);
        $this->assertStringContainsString('wire:name="teamleader.callers"', $html);
    }

    public function test_the_cards_bring_their_own_styles(): void
    {
        Livewire::test(Requests::class, ['lazy' => false]);

        $this->assertStringContainsString('.teamleader-badge', Pulse::css());
    }

    // -- requests ---------------------------------------------------------------------

    public function test_requests_are_listed_per_connection_and_endpoint_with_their_failures(): void
    {
        $this->traffic();

        $requests = Livewire::test(Requests::class, ['lazy' => false])
            ->assertSee('invoices.draft')
            ->assertSee('HTTP 422')
            ->viewData('requests')
            ->keyBy(fn (object $row) => $row->connection.' '.$row->endpoint);

        // By count: two each for brand-a, one for brand-b, none that reached the API for app-leadsync
        $this->assertEqualsCanonicalizing(
            ['brand-a deals.list', 'brand-a invoices.draft', 'brand-b deals.list', 'app-leadsync deals.list'],
            $requests->keys()->all(),
        );
        $this->assertSame('app-leadsync deals.list', $requests->keys()->last());

        $draft = $requests['brand-a invoices.draft'];
        $this->assertSame(2.0, $draft->count);
        $this->assertSame(1, $draft->failures);
        $this->assertSame(['422' => 1], $draft->reasons);
        $this->assertSame(1.0, $draft->slow);
        $this->assertSame(1500, $draft->slowest);

        // Never reached the API: a failure without a request
        $this->assertSame(0.0, $requests['app-leadsync deals.list']->count);
        $this->assertSame(['not_connected' => 2], $requests['app-leadsync deals.list']->reasons);
    }

    public function test_requests_can_be_sorted_and_filtered_by_connection(): void
    {
        $this->traffic();

        $component = Livewire::test(Requests::class, ['lazy' => false])
            ->set('orderBy', 'failures');

        $this->assertSame('app-leadsync', $component->viewData('requests')->first()->connection);

        $component->set('connection', 'brand-b');

        $this->assertSame(['brand-b'], $component->viewData('requests')->pluck('connection')->unique()->values()->all());
        $this->assertSame(['app-leadsync', 'brand-a', 'brand-b'], $component->viewData('connections'));
    }

    public function test_sampled_counts_are_scaled_back_up(): void
    {
        Config::set('pulse.recorders.'.TeamleaderRequests::class.'.sample_rate', 0.5);

        Pulse::record(Entries::REQUEST, Entries::key(['default', 'deals.list']), 100)->count()->avg()->max();
        Pulse::ingest();

        Livewire::test(Requests::class, ['lazy' => false])
            ->assertSee('~2');
    }

    // -- connections ----------------------------------------------------------------

    public function test_connections_show_traffic_waits_and_tokens(): void
    {
        $this->traffic();

        $component = Livewire::test(Connections::class, ['lazy' => false]);
        $connections = $component->viewData('connections')->keyBy('connection');

        $this->assertSame(['brand-a', 'brand-b', 'app-leadsync'], $component->viewData('connections')->pluck('connection')->all());
        $this->assertSame(4.0, $connections['brand-a']->requests);
        $this->assertSame(1, $connections['brand-a']->failures);
        $this->assertSame(25.0, $connections['brand-a']->errorRate);
        $this->assertSame(2, $connections['brand-a']->waits);
        $this->assertSame(3000, $connections['brand-a']->waited);
        $this->assertSame(1, $connections['brand-a']->refreshed);
        $this->assertSame(100.0, $connections['app-leadsync']->errorRate);
    }

    public function test_the_error_rate_counts_failures_that_never_reached_the_api(): void
    {
        foreach (range(1, 10) as $i) {
            event(new ResponseReceived('POST', 'deals.list', 200, 100.0, connection: 'brand-a'));
        }

        foreach (range(1, 5) as $i) {
            event(new RequestFailed('POST', 'deals.list', null, 'Timed out', connection: 'brand-a', durationMs: 30000.0));
        }

        Pulse::ingest();

        $row = Livewire::test(Connections::class, ['lazy' => false])->viewData('connections')->first();

        // 5 of 15 attempts
        $this->assertEqualsWithDelta(33.33, $row->errorRate, 0.01);
    }

    public function test_connections_that_need_attention_are_listed_first(): void
    {
        $this->traffic();

        $component = Livewire::test(Connections::class, ['lazy' => false])
            ->assertSee('Needs reconnecting')
            ->assertSee('Not connected')
            ->assertSee('invalid_grant');

        $this->assertSame(['brand-b', 'app-leadsync'], $component->viewData('attention')->pluck('connection')->all());
        $this->assertSame(
            [Entries::STATE_REAUTHORIZE, Entries::STATE_NOT_CONNECTED],
            $component->viewData('attention')->pluck('state')->all(),
        );
    }

    public function test_a_connection_that_is_connected_again_no_longer_needs_attention(): void
    {
        $this->traffic();

        Carbon::setTestNow('2026-10-19 10:10:00');
        event(new ConnectionAuthorized('brand-b', 'account-b', 'Brand B'));
        Pulse::ingest();

        $component = Livewire::test(Connections::class, ['lazy' => false]);

        $this->assertSame(['app-leadsync'], $component->viewData('attention')->pluck('connection')->all());
    }

    public function test_the_number_of_connections_can_be_limited(): void
    {
        $this->traffic();

        Livewire::test(Connections::class, ['lazy' => false, 'limit' => 1])
            ->assertViewHas('total', 3)
            ->assertSee('Showing 1 of 3 connections');
    }

    // -- callers ----------------------------------------------------------------------

    public function test_callers_show_their_teamleader_time_share_and_calls(): void
    {
        $key = Entries::key(['request', 'GET /admin/quotations', 'App\Http\Controllers\QuotationsController@index']);

        foreach ([[8700, 9100, 14], [4300, 4900, 6]] as [$time, $duration, $calls]) {
            Pulse::record(Entries::CALLER_TIME, $key, $time)->count()->sum()->avg()->max();
            Pulse::record(Entries::CALLER_DURATION, $key, $duration)->sum()->max();
            Pulse::record(Entries::CALLER_CALLS, $key, $calls)->sum()->max();
        }

        Pulse::ingest();

        $caller = Livewire::test(Callers::class, ['lazy' => false])
            ->assertSee('/admin/quotations')
            ->assertSee('QuotationsController@index')
            ->viewData('callers')
            ->first();

        $this->assertSame('request', $caller->kind);
        $this->assertSame(2.0, $caller->count);
        $this->assertSame(6500.0, $caller->average);
        $this->assertSame(8700, $caller->slowest);
        $this->assertSame(10.0, $caller->calls);
        $this->assertSame(14, $caller->mostCalls);
        $this->assertEqualsWithDelta(92.86, $caller->share, 0.01);
    }

    public function test_empty_cards_render(): void
    {
        Livewire::test(Requests::class, ['lazy' => false])->assertSee('No results');
        Livewire::test(Connections::class, ['lazy' => false])->assertSee('No results');
        Livewire::test(Callers::class, ['lazy' => false])->assertSee('No results');
    }

    /**
     * Three connections: brand-a busy with one refusal and rate-limit waits,
     * brand-b whose refresh token was refused, app-leadsync never connected.
     */
    private function traffic(): void
    {
        foreach ([200, 300] as $ms) {
            event(new ResponseReceived('POST', 'deals.list', 200, $ms, connection: 'brand-a'));
        }

        event(new ResponseReceived('POST', 'invoices.draft', 200, 1500.0, connection: 'brand-a'));
        event(new ResponseReceived('POST', 'invoices.draft', 422, 90.0, connection: 'brand-a'));
        event(new RequestFailed('POST', 'invoices.draft', 422, 'Invalid', connection: 'brand-a'));
        event(new RateLimitWaited(1000, 90.0, 'deals.list', connection: 'brand-a'));
        event(new RateLimitWaited(2000, 95.0, 'deals.list', connection: 'brand-a'));
        event(new TokenRefreshed(3600, connection: 'brand-a'));

        event(new ResponseReceived('POST', 'deals.list', 200, 100.0, connection: 'brand-b'));
        Carbon::setTestNow('2026-10-19 10:01:00');
        event(new TokenRefreshFailed('invalid_grant', 400, true, connection: 'brand-b'));

        Carbon::setTestNow('2026-10-19 10:00:30');
        event(new RequestFailed('POST', 'deals.list', 401, 'No access token available.', connection: 'app-leadsync'));
        event(new RequestFailed('POST', 'deals.list', 401, 'No access token available.', connection: 'app-leadsync'));

        Carbon::setTestNow('2026-10-19 10:02:00');
        Pulse::ingest();
    }
}
