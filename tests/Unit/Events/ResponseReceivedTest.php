<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Events;

use Error;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use PHPUnit\Framework\TestCase;

/**
 * v3.4: the body of ResponseReceived is built when a listener first reads it,
 * so listeners that only count or time requests (the Pulse recorder) never
 * pay for sanitising it. To listeners that do read it, nothing changed.
 */
final class ResponseReceivedTest extends TestCase
{
    public function test_the_body_is_built_once_on_first_read(): void
    {
        $built = 0;

        $event = new ResponseReceived('POST', 'deals.list', 200, 12.5, bodyUsing: function () use (&$built) {
            $built++;

            return ['data' => [['id' => 'deal-1']]];
        });

        $this->assertSame(0, $built);
        $this->assertSame([['id' => 'deal-1']], $event->body['data']);
        $this->assertSame([['id' => 'deal-1']], $event->body['data']);
        $this->assertSame(1, $built);
    }

    public function test_a_body_can_still_be_passed_as_before(): void
    {
        $event = new ResponseReceived('POST', 'deals.list', 200, 12.5, ['data' => []], 'brand-a');

        $this->assertSame(['data' => []], $event->body);
        $this->assertSame('brand-a', $event->connection);
        $this->assertTrue(isset($event->body));
    }

    public function test_a_missing_body_is_null(): void
    {
        $event = new ResponseReceived('POST', 'deals.delete', 204, 12.5, bodyUsing: fn () => null);

        $this->assertNull($event->body);
        $this->assertFalse(isset($event->body));
    }

    public function test_it_survives_serialisation_for_queued_listeners(): void
    {
        $event = new ResponseReceived('POST', 'deals.list', 200, 12.5, connection: 'brand-a', bodyUsing: fn () => ['data' => ['x']]);

        $copy = unserialize(serialize($event));

        $this->assertInstanceOf(ResponseReceived::class, $copy);
        $this->assertSame(['data' => ['x']], $copy->body);
        $this->assertSame('brand-a', $copy->connection);
        $this->assertSame(12.5, $copy->durationMs);
        $this->assertTrue($copy->successful());
    }

    public function test_json_and_dumps_include_the_body(): void
    {
        $event = new ResponseReceived('POST', 'deals.list', 200, 12.5, connection: 'brand-a', bodyUsing: fn () => ['data' => ['x']]);

        $this->assertSame(
            '{"method":"POST","endpoint":"deals.list","statusCode":200,"durationMs":12.5,"body":{"data":["x"]},"connection":"brand-a"}',
            json_encode($event),
        );
        $this->assertSame(['data' => ['x']], $event->__debugInfo()['body']);
        $this->assertStringNotContainsString('Closure', print_r($event, true));
    }

    public function test_the_body_cannot_be_changed(): void
    {
        $event = new ResponseReceived('POST', 'deals.list', 200, 12.5, ['data' => []]);

        $this->expectException(Error::class);

        $event->body = ['data' => ['changed']];
    }

    public function test_the_other_properties_are_read_only(): void
    {
        $event = new ResponseReceived('POST', 'deals.list', 200, 12.5);

        $this->expectException(Error::class);

        $event->statusCode = 500;
    }
}
