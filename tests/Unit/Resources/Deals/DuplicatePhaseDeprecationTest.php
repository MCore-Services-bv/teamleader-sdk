<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Deals;

use McoreServices\TeamleaderSDK\Resources\Deals\Phases;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Specification 1.225.0 removed dealPhases.duplicate. The wrapper stays until
 * v4.0 so existing code keeps working, but says it is deprecated.
 *
 * The notice is raised once per process (a static flag in the method), so
 * both calls live in one test: nothing else in the suite calls duplicate().
 */
final class DuplicatePhaseDeprecationTest extends ResourceTestCase
{
    public function test_duplicate_still_sends_the_request_and_warns_once(): void
    {
        $notices = [];

        set_error_handler(function (int $errno, string $message) use (&$notices): bool {
            $notices[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $phases = $this->resource(Phases::class);

            $phases->duplicate('phase-source');
            $this->assertLastEndpoint('dealPhases.duplicate');
            $this->assertSame(['id' => 'phase-source'], $this->lastBody());

            $phases->duplicate('phase-other');
            $this->assertRequestCount(2);
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $notices, 'The deprecation notice is raised once per process.');
        $this->assertStringContainsString('dealPhases.duplicate is no longer in the Teamleader API specification', $notices[0]);
        $this->assertStringContainsString('dealPhases()->create()', $notices[0]);
    }

    public function test_the_duplicate_example_is_no_longer_advertised(): void
    {
        $examples = (new \ReflectionProperty(Phases::class, 'usageExamples'))->getValue($this->resource(Phases::class));

        $this->assertArrayNotHasKey('duplicate_phase', $examples);
    }
}
