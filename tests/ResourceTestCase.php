<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests;

use Illuminate\Support\Arr;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Tests\Support\RecordingApiClient;

/**
 * Base class for resource tests that assert on the request a resource builds.
 *
 * Extend this instead of TestCase when the thing under test is a payload rather
 * than a service. A RecordingApiClient is wired up in setUp() and available as
 * $this->api; build the resource with $this->resource(Files::class).
 *
 *     final class FilesTest extends ResourceTestCase
 *     {
 *         public function test_for_product_filters_by_product_subject(): void
 *         {
 *             $this->resource(Files::class)->forProduct('product-uuid');
 *
 *             $this->assertLastEndpoint('files.list');
 *             $this->assertLastBodyHas('filter.subject.type', 'product');
 *         }
 *     }
 *
 * Body assertions take dot-notation paths, so nested structures can be checked
 * one key at a time rather than by comparing whole arrays — which keeps a test
 * about sorting from breaking when pagination defaults change.
 */
abstract class ResourceTestCase extends TestCase
{
    protected RecordingApiClient $api;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new RecordingApiClient;
    }

    /**
     * Build a resource bound to the recording client.
     *
     * @template T of Resource
     *
     * @param  class-string<T>  $resourceClass
     * @return T
     */
    protected function resource(string $resourceClass): Resource
    {
        return new $resourceClass($this->api);
    }

    /**
     * The body of the most recent recorded request.
     */
    protected function lastBody(): array
    {
        return $this->api->lastBody();
    }

    /**
     * Assert how many requests were made.
     */
    protected function assertRequestCount(int $expected): void
    {
        $this->assertCount(
            $expected,
            $this->api->calls,
            'Expected '.$expected.' request(s), got '.$this->api->callCount()
            .' ('.implode(', ', $this->api->endpoints()).')'
        );
    }

    /**
     * Assert no request was made — useful for client-side validation tests
     * where the point is that nothing reached the API.
     */
    protected function assertNoRequestMade(): void
    {
        $this->assertSame(
            [],
            $this->api->calls,
            'Expected no request, but these endpoints were called: '
            .implode(', ', $this->api->endpoints())
        );
    }

    /**
     * Assert the most recent request went to the given endpoint.
     */
    protected function assertLastEndpoint(string $expected): void
    {
        $this->assertSame($expected, $this->api->lastEndpoint());
    }

    /**
     * Assert the given endpoint was called at some point.
     */
    protected function assertEndpointCalled(string $expected): void
    {
        $this->assertContains(
            $expected,
            $this->api->endpoints(),
            "Endpoint {$expected} was never called."
        );
    }

    /**
     * Assert the most recent request body matches exactly.
     *
     * Prefer assertLastBodyHas() unless the whole payload is the point —
     * an exact match couples the test to every default the SDK applies.
     */
    protected function assertLastBody(array $expected): void
    {
        $this->assertSame($expected, $this->lastBody());
    }

    /**
     * Assert a dot-notation path exists in the most recent body, optionally
     * with a specific value.
     *
     * Pass no $value to assert presence only — useful when the value is a
     * generated id or a timestamp.
     */
    protected function assertLastBodyHas(string $path, mixed $value = null, bool $checkValue = true): void
    {
        $body = $this->lastBody();

        $this->assertTrue(
            Arr::has($body, $path),
            "Request body has no '{$path}'. Body was: ".json_encode($body)
        );

        if ($value !== null || $checkValue === false) {
            if ($value !== null) {
                $this->assertSame($value, Arr::get($body, $path));
            }
        }
    }

    /**
     * Assert a dot-notation path is absent from the most recent body.
     *
     * This is the assertion that catches the SDK's characteristic failure mode:
     * a key the API silently ignores, such as `include` where it wants
     * `includes`, or a filter that should have been rejected client-side.
     */
    protected function assertLastBodyMissing(string $path): void
    {
        $body = $this->lastBody();

        $this->assertFalse(
            Arr::has($body, $path),
            "Request body should not contain '{$path}', but it does. Body was: "
            .json_encode($body)
        );
    }

    /**
     * Assert the top-level keys of the most recent body, ignoring order.
     */
    protected function assertLastBodyKeys(array $expected): void
    {
        $actual = array_keys($this->lastBody());

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }
}
