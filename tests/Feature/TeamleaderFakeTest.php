<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;
use McoreServices\TeamleaderSDK\Facades\Teamleader;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Testing\FakeConnectionManager;
use McoreServices\TeamleaderSDK\Testing\FakeTeamleader;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use OutOfBoundsException;
use PHPUnit\Framework\AssertionFailedError;
use RuntimeException;

/**
 * v3.1 (§A): Teamleader::fake() — an application's Teamleader code tested
 * without an account, a token or the network, with the resources' own
 * validation still in place.
 */
final class TeamleaderFakeTest extends TestCase
{
    private const COMPANY = '0b9a4c8e-1f2d-4e3a-9b5c-6d7e8f901234';

    private function deal(string $title = 'Big deal'): array
    {
        return ['lead' => ['customer' => ['type' => 'company', 'id' => self::COMPANY]], 'title' => $title];
    }

    // -- answering ----------------------------------------------------------------

    public function test_stubs_answer_by_endpoint_pattern_and_closure(): void
    {
        Teamleader::fake([
            'deals.create' => ['data' => ['id' => 'deal-1', 'type' => 'deal']],
            'deals.info' => fn (array $body) => ['data' => ['id' => $body['id'], 'title' => 'From closure']],
            'contacts.*' => ['data' => [['id' => 'contact-1']]],
        ]);

        $this->assertSame('deal-1', Teamleader::deals()->create($this->deal())['data']['id']);
        $this->assertSame('From closure', Teamleader::deals()->info('deal-9')['data']['title']);
        $this->assertSame('contact-1', Teamleader::contacts()->list()['data'][0]['id']);
    }

    public function test_an_unstubbed_endpoint_answers_an_empty_success(): void
    {
        Teamleader::fake();

        $this->assertSame([], Teamleader::companies()->list()['data']);
        Teamleader::assertSent('companies.list');
    }

    public function test_the_resources_still_validate_and_nothing_is_recorded(): void
    {
        Teamleader::fake();

        try {
            Teamleader::companies()->list(['colour' => 'blue']);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('colour', $e->getMessage());
        }

        Teamleader::assertNothingSent();
    }

    public function test_a_refusal_throws_the_typed_exception(): void
    {
        Teamleader::fake(['deals.info' => Teamleader::response()->status(404)->message('Deal not found')]);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Deal not found');

        Teamleader::deals()->info('missing');
    }

    public function test_a_refusal_is_an_error_array_with_exceptions_off(): void
    {
        config(['teamleader.error_handling.throw_exceptions' => false]);
        Teamleader::fake(['deals.update' => Teamleader::response()->status(422)->errors(['title is too long'])]);

        $result = Teamleader::deals()->update('deal-1', ['title' => 'x']);

        $this->assertTrue($result['error']);
        $this->assertSame(422, $result['status_code']);
        $this->assertSame(['title is too long'], $result['errors']);
    }

    public function test_a_sequence_answers_in_order_and_says_when_it_is_used_up(): void
    {
        Teamleader::fake([
            'deals.info' => Teamleader::sequence()
                ->push(['data' => ['id' => 'first']])
                ->push(['data' => ['id' => 'second']]),
        ]);

        $this->assertSame('first', Teamleader::deals()->info('a')['data']['id']);
        $this->assertSame('second', Teamleader::deals()->info('b')['data']['id']);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('deals.info is used up');

        Teamleader::deals()->info('c');
    }

    public function test_stray_requests_can_be_prevented(): void
    {
        Teamleader::fake(['companies.list' => ['data' => []]]);
        Teamleader::preventStrayRequests();

        Teamleader::companies()->list();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected Teamleader request to [contacts.list]');

        Teamleader::contacts()->list();
    }

    public function test_calling_fake_again_adds_stubs_and_keeps_what_was_sent(): void
    {
        Teamleader::fake(['companies.list' => ['data' => [['id' => 'c1']]]]);
        Teamleader::companies()->list();

        Teamleader::fake(['contacts.list' => ['data' => [['id' => 'p1']]]]);

        $this->assertSame('p1', Teamleader::contacts()->list()['data'][0]['id']);
        Teamleader::assertSentCount('*.list', 2);
    }

    // -- assertions ---------------------------------------------------------------

    public function test_assert_sent_checks_the_body(): void
    {
        Teamleader::fake();

        Teamleader::deals()->create($this->deal('Big deal'));

        Teamleader::assertSent('deals.create', fn (array $body) => $body['title'] === 'Big deal');
        Teamleader::assertNotSent('deals.create', fn (array $body) => $body['title'] === 'Small deal');
        Teamleader::assertSentCount('deals.create', 1);
        Teamleader::assertNotSent('deals.delete');
    }

    public function test_a_failed_assertion_says_what_was_sent(): void
    {
        Teamleader::fake();
        Teamleader::companies()->list();
        Teamleader::companies()->list();

        try {
            Teamleader::assertSent('contacts.list');
            $this->fail('Expected the assertion to fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('Expected a request to [contacts.list]', $e->getMessage());
            $this->assertStringContainsString('Sent: companies.list (x2).', $e->getMessage());
        }
    }

    public function test_a_failed_body_check_shows_the_bodies_sent(): void
    {
        Teamleader::fake();
        Teamleader::deals()->create($this->deal('Big deal'));

        try {
            Teamleader::assertSent('deals.create', fn (array $body) => $body['title'] === 'Other');
            $this->fail('Expected the assertion to fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('"title":"Big deal"', $e->getMessage());
        }
    }

    public function test_asserting_without_a_fake_is_refused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Call Teamleader::fake()');

        Teamleader::assertNothingSent();
    }

    // -- connections ----------------------------------------------------------------

    public function test_every_connection_is_faked_and_asserted_on_its_own_or_together(): void
    {
        Teamleader::fake();

        Teamleader::companies()->list();
        Teamleader::connection('antwerp')->contacts()->list();

        Teamleader::assertSent('contacts.list');
        Teamleader::connection('antwerp')->assertSent('contacts.list');
        Teamleader::connection('default')->assertNotSent('contacts.list');

        $this->assertSame(['default', 'antwerp'], array_column(Teamleader::recorded(), 'connection'));
    }

    public function test_stubs_can_differ_per_connection(): void
    {
        Teamleader::fake(['users.me' => ['data' => ['id' => 'shared']]]);
        Teamleader::connection('ghent')->stub(['users.me' => ['data' => ['id' => 'ghent-user']]]);

        $this->assertSame('shared', Teamleader::users()->me()['data']['id']);
        $this->assertSame('ghent-user', Teamleader::connection('ghent')->users()->me()['data']['id']);
    }

    public function test_the_container_and_the_facade_resolve_the_fake(): void
    {
        $manager = Teamleader::fake();

        $this->assertInstanceOf(FakeConnectionManager::class, $manager);
        $this->assertInstanceOf(FakeTeamleader::class, app(TeamleaderSDK::class));
        $this->assertTrue(Teamleader::isAuthenticated());
        $this->assertTrue(Teamleader::isFake());
    }

    public function test_handle_callback_completes_without_teamleader(): void
    {
        Teamleader::fake();

        $connected = Teamleader::handleCallback('the-code', 'any-state');

        $this->assertSame('default', $connected->connectionName());
        Teamleader::assertSent('oauth.callback', fn (array $body) => $body['code'] === 'the-code');
    }

    // -- bulk -------------------------------------------------------------------------

    public function test_bulk_runs_through_the_fake_with_validation_first(): void
    {
        Teamleader::fake([
            'deals.create' => Teamleader::sequence()
                ->push(['data' => ['id' => 'd1']])
                ->push(Teamleader::response()->status(422)->errors(['title is too long'])),
        ]);

        $result = Teamleader::bulk()->create('deals', ['a' => $this->deal('A'), 'b' => $this->deal('B')])
            ->continueOnError()
            ->run();

        $this->assertSame(['a'], array_keys($result->succeeded()));
        $this->assertSame(['b'], array_keys($result->failed()));
        $this->assertInstanceOf(ValidationException::class, $result->failed()['b']->exception);
        Teamleader::assertSentCount('deals.create', 2);
    }
}
