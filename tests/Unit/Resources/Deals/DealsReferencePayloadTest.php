<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Deals\LostReasons;
use McoreServices\TeamleaderSDK\Resources\Deals\Phases;
use McoreServices\TeamleaderSDK\Resources\Deals\Pipelines;
use McoreServices\TeamleaderSDK\Resources\Deals\Sources;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the Deals configuration resources: Phases, Pipelines,
 * Sources and LostReasons, against specification 1.221.0.
 */
final class DealsReferencePayloadTest extends ResourceTestCase
{
    // -- Phases --------------------------------------------------------------

    /**
     * Phases::delete() called parent::delete(), which Resource does not
     * define: every call was a fatal error until v2.2.5.
     */
    public function test_phase_delete_reaches_the_endpoint(): void
    {
        $this->resource(Phases::class)->delete('phase-uuid');

        $this->assertLastEndpoint('dealPhases.delete');
        $this->assertLastBody(['id' => 'phase-uuid']);
    }

    public function test_phase_delete_passes_the_new_phase(): void
    {
        $this->resource(Phases::class)->delete('phase-uuid', 'other-phase-uuid');

        $this->assertLastBody(['id' => 'phase-uuid', 'new_phase_id' => 'other-phase-uuid']);
    }

    public function test_phase_update_requires_attention_after(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('dealPhases.update requires requires_attention_after');

        try {
            $this->resource(Phases::class)->update('phase-uuid', ['name' => 'Negotiation']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_phase_update_rejects_pipeline_change(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('deal_pipeline_id');

        $this->resource(Phases::class)->update('phase-uuid', [
            'deal_pipeline_id' => 'pipeline-uuid',
            'requires_attention_after' => ['amount' => 7, 'unit' => 'days'],
        ]);
    }

    public function test_phase_follow_up_actions_are_checked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('follow_up_actions[]');

        $this->resource(Phases::class)->create([
            'name' => 'Qualified',
            'deal_pipeline_id' => 'pipeline-uuid',
            'requires_attention_after' => ['amount' => 1, 'unit' => 'weeks'],
            'follow_up_actions' => ['send_email'],
        ]);
    }

    public function test_phase_filters_are_whitelisted(): void
    {
        $this->resource(Phases::class)->list(['ids' => 'phase-uuid']);
        $this->assertLastBodyHas('filter.ids', ['phase-uuid']);

        $this->expectException(InvalidArgumentException::class);

        $this->resource(Phases::class)->list(['term' => 'won']);
    }

    // -- Pipelines -----------------------------------------------------------

    public function test_pipeline_term_filter(): void
    {
        $this->resource(Pipelines::class)->search('Sales');

        $this->assertLastEndpoint('dealPipelines.list');
        $this->assertLastBodyHas('filter.term', 'Sales');
        $this->assertLastBodyHas('includes', 'pagination');
    }

    public function test_pipeline_status_is_checked_and_wrapped(): void
    {
        $this->resource(Pipelines::class)->list(['status' => 'open']);
        $this->assertLastBodyHas('filter.status', ['open']);

        $this->expectException(InvalidArgumentException::class);

        $this->resource(Pipelines::class)->list(['status' => 'closed']);
    }

    public function test_pipeline_unknown_filter_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(Pipelines::class)->list(['name' => 'Sales']);
    }

    // -- Sources -------------------------------------------------------------

    public function test_source_search_uses_the_term_filter(): void
    {
        $this->resource(Sources::class)->search('website');

        $this->assertLastEndpoint('dealSources.list');
        $this->assertLastBodyHas('filter.term', 'website');
        $this->assertLastBodyHas('sort', [['field' => 'name', 'order' => 'asc']]);
    }

    public function test_source_descending_sort_throws_instead_of_being_rewritten(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("accepts only 'asc'");

        $this->resource(Sources::class)->list([], ['sort_order' => 'desc']);
    }

    public function test_source_all_walks_pages(): void
    {
        $this->api->queueResponse(['data' => array_fill(0, 100, ['id' => 'x', 'name' => 'x']), 'headers' => []]);
        $this->api->queueResponse(['data' => [['id' => 'y', 'name' => 'y']], 'headers' => []]);

        $all = $this->resource(Sources::class)->all();

        $this->assertCount(101, $all['data']);
        $this->assertRequestCount(2);
        $this->assertLastBodyHas('page', ['size' => 100, 'number' => 2]);
    }

    // -- Lost reasons ----------------------------------------------------------

    public function test_lost_reason_sort_is_name_ascending_only(): void
    {
        $this->resource(LostReasons::class)->list([], ['sort_field' => 'name']);
        $this->assertLastBodyHas('sort', [['field' => 'name', 'order' => 'asc']]);

        $this->expectException(InvalidArgumentException::class);

        $this->resource(LostReasons::class)->list([], ['sort' => [['field' => 'name', 'order' => 'desc']]]);
    }

    public function test_lost_reason_other_sort_field_throws_instead_of_being_rewritten(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field: created_at');

        $this->resource(LostReasons::class)->list([], ['sort' => 'created_at']);
    }

    public function test_lost_reason_unknown_filter_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource(LostReasons::class)->list(['term' => 'price']);
    }
}
