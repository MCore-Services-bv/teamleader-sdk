<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature\Console;

use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * v3.0 (§6): the read-only CLI — resources, describe, list, info. Every
 * command goes through the resources' own methods, so their validation is
 * what the user sees.
 */
final class ReadCommandsTest extends ResourceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $api = $this->api;
        $this->app->instance(ConnectionManager::class, new class($api) extends ConnectionManager
        {
            /** @var list<string|null> */
            public array $asked = [];

            public function __construct(private readonly TeamleaderSDK $api) {}

            public function connection(?string $name = null): TeamleaderSDK
            {
                $this->asked[] = $name;

                return $this->api;
            }
        });
    }

    // -- resources / describe ---------------------------------------------------

    public function test_resources_lists_every_resource(): void
    {
        $this->artisan('teamleader:resources')
            ->expectsOutputToContain('companies')
            ->expectsOutputToContain('resources. `php artisan teamleader:describe')
            ->assertExitCode(0);
    }

    public function test_resources_filters_by_category(): void
    {
        $this->artisan('teamleader:resources', ['--category' => 'nope'])
            ->expectsOutputToContain("No category 'nope'")
            ->assertExitCode(2);
    }

    public function test_describe_shows_filters_sort_fields_and_methods(): void
    {
        $this->artisan('teamleader:describe', ['resource' => 'deals'])
            ->expectsOutputToContain('deals.list')
            ->expectsOutputToContain('weighted_value')
            ->expectsOutputToContain('lazy(')
            ->assertExitCode(0);
    }

    public function test_describe_suggests_the_closest_name_first(): void
    {
        $this->artisan('teamleader:describe', ['resource' => 'deal'])
            ->expectsOutputToContain('Did you mean: deals')
            ->assertExitCode(2);
    }

    public function test_describe_points_to_the_list_when_nothing_is_close(): void
    {
        $this->artisan('teamleader:describe', ['resource' => 'xyzzy'])
            ->expectsOutputToContain('teamleader:resources')
            ->assertExitCode(2);
    }

    // -- list -------------------------------------------------------------------

    public function test_list_passes_filters_sort_and_includes_through_the_resource(): void
    {
        $this->api->queueListResponse([['id' => 'd1', 'title' => 'Big deal']]);

        $this->artisan('teamleader:list', [
            'resource' => 'deals',
            '--filter' => ['status[]=open', 'phase_id=phase-1'],
            '--sort' => 'created_at:desc',
            '--include' => 'custom_fields',
            '--fields' => 'id,title',
        ])->expectsOutputToContain('Big deal')->assertExitCode(0);

        $body = $this->api->lastBody();
        $this->assertSame(['open'], $body['filter']['status']);
        $this->assertSame('phase-1', $body['filter']['phase_id']);
        $this->assertSame([['field' => 'created_at', 'order' => 'desc']], $body['sort']);
        $this->assertSame('custom_fields', $body['includes']);
        $this->assertSame(['size' => 20, 'number' => 1], $body['page']);
    }

    public function test_an_unknown_filter_shows_the_resources_own_message(): void
    {
        $this->artisan('teamleader:list', ['resource' => 'deals', '--filter' => ['colour=blue']])
            ->expectsOutputToContain('Unsupported filter key for deals.list: colour')
            ->assertExitCode(2);

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_all_pages_through_and_limit_stops_early(): void
    {
        $this->api->queueResponses([
            ['data' => [['id' => 'a'], ['id' => 'b']], 'headers' => []],
            ['data' => [['id' => 'c'], ['id' => 'd']], 'headers' => []],
        ]);

        $this->artisan('teamleader:list', ['resource' => 'companies', '--all' => true, '--page-size' => 2, '--limit' => 3, '--format' => 'json'])
            ->assertExitCode(0);

        $this->assertSame(2, $this->api->callCount());
    }

    public function test_csv_output(): void
    {
        $this->api->queueListResponse([['id' => 'c1', 'name' => 'Acme']]);

        $this->artisan('teamleader:list', ['resource' => 'companies', '--format' => 'csv', '--fields' => 'id,name'])
            ->expectsOutputToContain('c1,Acme')
            ->assertExitCode(0);
    }

    public function test_an_unknown_format_is_refused_before_any_request(): void
    {
        $this->artisan('teamleader:list', ['resource' => 'companies', '--format' => 'xml'])
            ->expectsOutputToContain('Unknown --format=xml')
            ->assertExitCode(2);

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_the_connection_option_is_used(): void
    {
        $this->artisan('teamleader:list', ['resource' => 'companies', '--connection' => 'antwerp'])->assertExitCode(0);

        $this->assertContains('antwerp', app(ConnectionManager::class)->asked);
    }

    // -- info -------------------------------------------------------------------

    public function test_info_shows_one_record_as_field_value_rows(): void
    {
        $this->api->queueResponse(['data' => ['id' => 'd1', 'title' => 'Big deal', 'lead' => ['customer' => ['type' => 'company']]], 'headers' => []]);

        $this->artisan('teamleader:info', ['resource' => 'deals', 'id' => 'd1'])
            ->expectsOutputToContain('lead.customer.type')
            ->assertExitCode(0);

        $this->assertSame('deals.info', $this->api->lastEndpoint());
        $this->assertSame('d1', $this->api->lastBody()['id']);
    }

    public function test_an_api_error_is_one_readable_line_whatever_throw_exceptions_says(): void
    {
        // The recording client returns failures as arrays, as throw_exceptions=false does
        $this->api->queueResponse(['error' => true, 'status_code' => 404, 'message' => 'Deal not found']);

        $this->artisan('teamleader:info', ['resource' => 'deals', 'id' => 'missing'])
            ->expectsOutputToContain('Deal not found (HTTP 404)')
            ->assertExitCode(1);
    }
}
