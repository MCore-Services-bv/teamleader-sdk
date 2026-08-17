<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Resources\Deals\Pipelines;
use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Resources\Products\UnitOfMeasure;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Regression tests for the v2.1.2 response-format documentation fix.
 *
 * Resource::getResponseFormat() asserted for every resource that a `list`
 * response carries `pagination`, `included` and `meta`. None of the three is
 * returned by default, and `included` appears nowhere in the Teamleader API
 * specification at all — sideloaded data is embedded inside each record in
 * `data`, not in a separate top-level block.
 *
 * Meanwhile `headers` — which the SDK itself adds to every successful response,
 * and which carries the rate-limit budget — was undocumented.
 *
 * This matters because getDocumentation() is the SDK's discoverability feature.
 * A consumer following it would write a pager keyed on $response['pagination'],
 * which is always absent, so the loop stops after page one and they silently see
 * only the first slice of every entity.
 */
final class ResourceResponseFormatTest extends ResourceTestCase
{
    // ---------------------------------------------------------------------
    // Keys that never existed
    // ---------------------------------------------------------------------

    public function test_list_no_longer_documents_a_pagination_key(): void
    {
        $formats = $this->resource(Deals::class)->getDocumentation()['response_formats'];

        $this->assertArrayNotHasKey('pagination', $formats['list']);
    }

    public function test_list_no_longer_documents_an_included_key(): void
    {
        // `included` does not exist anywhere in the API. Deals supports
        // sideloading, and still does not receive a top-level included block.
        $deals = $this->resource(Deals::class);

        $this->assertTrue($deals->getCapabilities()['supports_sideloading']);
        $this->assertArrayNotHasKey(
            'included',
            $deals->getDocumentation()['response_formats']['list']
        );
    }

    public function test_info_no_longer_documents_an_included_key(): void
    {
        $formats = $this->resource(Deals::class)->getDocumentation()['response_formats'];

        $this->assertArrayNotHasKey('included', $formats['info']);
    }

    // ---------------------------------------------------------------------
    // The key that was always there and never documented
    // ---------------------------------------------------------------------

    public function test_every_operation_documents_headers(): void
    {
        $formats = $this->resource(Deals::class)->getDocumentation()['response_formats'];

        foreach (['list', 'info', 'create', 'update', 'delete'] as $operation) {
            $this->assertArrayHasKey(
                'headers',
                $formats[$operation],
                "The {$operation} response format must document the headers key the SDK adds."
            );
        }
    }

    public function test_the_headers_description_names_the_rate_limit_headers(): void
    {
        $formats = $this->resource(Deals::class)->getDocumentation()['response_formats'];

        $this->assertStringContainsString('X-RateLimit-Remaining', $formats['list']['headers']);
    }

    // ---------------------------------------------------------------------
    // meta is capability-driven
    // ---------------------------------------------------------------------

    public function test_meta_is_absent_for_a_resource_that_does_not_request_it(): void
    {
        $formats = $this->resource(Deals::class)->getDocumentation()['response_formats'];

        $this->assertArrayNotHasKey('meta', $formats['list']);
    }

    public function test_meta_is_documented_for_a_resource_that_sends_includes_pagination(): void
    {
        // Pipelines sends includes=pagination, so the API does return meta.
        $formats = $this->resource(Pipelines::class)->getDocumentation()['response_formats'];

        $this->assertArrayHasKey('meta', $formats['list']);
    }

    // ---------------------------------------------------------------------
    // Pagination behaviour
    // ---------------------------------------------------------------------

    public function test_pagination_block_warns_about_the_missing_total(): void
    {
        $pagination = $this->resource(Deals::class)->getDocumentation()['pagination'];

        $this->assertTrue($pagination['supported']);
        $this->assertFalse($pagination['returns_metadata']);
        $this->assertStringContainsString('shorter than the requested page size', $pagination['note']);
    }

    public function test_an_unpaginated_resource_says_so(): void
    {
        $pagination = $this->resource(UnitOfMeasure::class)->getDocumentation()['pagination'];

        $this->assertFalse($pagination['supported']);
        $this->assertFalse($pagination['returns_metadata']);
        $this->assertStringContainsString('not paginated', $pagination['note']);
    }

    public function test_pagination_block_and_capability_flag_agree(): void
    {
        // The two cannot drift apart: the block is derived from the flag.
        foreach ([Deals::class, Files::class, UnitOfMeasure::class, Pipelines::class] as $class) {
            $docs = $this->resource($class)->getDocumentation();

            $this->assertSame(
                $docs['capabilities']['supports_pagination'],
                $docs['pagination']['supported'],
                class_basename($class).' reports pagination inconsistently.'
            );
        }
    }

    // ---------------------------------------------------------------------
    // Markdown output
    // ---------------------------------------------------------------------

    public function test_generated_markdown_includes_the_pagination_warning(): void
    {
        $markdown = $this->resource(Deals::class)->generateMarkdownDocs();

        $this->assertStringContainsString('## Pagination', $markdown);
        $this->assertStringContainsString('shorter than the requested page size', $markdown);
    }

    public function test_generated_markdown_includes_response_formats(): void
    {
        // generateMarkdownDocs() rendered capabilities, filters and examples but
        // never the response formats, so the one place a consumer would look for
        // the response shape did not show it at all.
        $markdown = $this->resource(Deals::class)->generateMarkdownDocs();

        $this->assertStringContainsString('## Response Formats', $markdown);
        $this->assertStringContainsString('`headers`', $markdown);
    }

    public function test_generated_markdown_does_not_mention_the_phantom_keys(): void
    {
        $markdown = $this->resource(Deals::class)->generateMarkdownDocs();

        $this->assertStringNotContainsString('`included`', $markdown);
        $this->assertStringNotContainsString('`pagination`:', $markdown);
    }

    public function test_generated_markdown_lists_sort_fields(): void
    {
        $markdown = $this->resource(Deals::class)->generateMarkdownDocs();

        $this->assertStringContainsString('## Sort Fields', $markdown);
        $this->assertStringContainsString('weighted_value', $markdown);
    }
}
