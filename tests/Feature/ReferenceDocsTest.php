<?php

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use McoreServices\TeamleaderSDK\Tests\Support\Docs\ReferenceGenerator;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SdkInventory;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * The committed API reference in docs/reference matches the code.
 *
 * Every reference page is generated from a resource class. A resource that
 * gains a filter, a constant or a method without its page being regenerated
 * would leave the published documentation describing an older SDK; this test
 * fails instead. Fix it with `composer docs:build` and commit the result.
 */
#[Group('docs')]
class ReferenceDocsTest extends TestCase
{
    private ReferenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ReferenceGenerator(new SdkInventory, new SpecContract);
    }

    public function test_committed_reference_matches_the_code(): void
    {
        $diff = $this->generator->diff(ReferenceGenerator::defaultDocsPath());

        $this->assertSame(
            [],
            $diff,
            "docs/ is out of date — run `composer docs:build` and commit the result:\n"
            .implode("\n", array_map(fn ($path, $state) => "  {$state}: docs/{$path}", array_keys($diff), $diff))
        );
    }

    public function test_every_registered_resource_has_a_page(): void
    {
        $pages = $this->generator->pages();

        // One page per resource class, plus the index
        $this->assertCount(count((new SdkInventory)->resources()) + 1, $pages);
    }

    public function test_generation_is_deterministic(): void
    {
        $this->assertSame($this->generator->pages(), $this->generator->pages());
    }

    /**
     * PHP 8.5 reports a `self` return type through reflection differently
     * from 8.2 – 8.4. Before v2.3.2 that changed six pages on 8.5 only, so
     * docs:check passed locally and failed in the 8.5 CI jobs.
     */
    public function test_self_return_types_render_the_same_on_every_php_version(): void
    {
        $pages = $this->generator->pages();

        $this->assertStringContainsString('withSuppliers(): self', $pages['reference/products/products.md']);
        $this->assertStringNotContainsString('): Products', $pages['reference/products/products.md']);
    }

    public function test_every_link_in_the_reference_index_resolves(): void
    {
        $pages = $this->generator->pages();

        preg_match_all('/\]\(([a-z0-9\-\/]+\.md)\)/', $pages['reference/README.md'], $links);

        foreach (array_unique($links[1]) as $link) {
            $this->assertArrayHasKey('reference/'.$link, $pages, "Index links to a missing page: {$link}");
        }
    }
}
