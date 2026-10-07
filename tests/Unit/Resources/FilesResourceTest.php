<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Regression tests for the v2.1.2 Files fixes.
 *
 * Three separate defects are covered here:
 *
 * 1. files.list and files.upload accept different subject types. The SDK used
 *    one shared whitelist, which rejected `meeting`, `product` and `project`
 *    on list and wrongly accepted `temporary` there.
 * 2. The sort parameter was built as a string array (['-updated_at']) where the
 *    API expects objects ([['field' => ..., 'order' => ...]]). The API ignores
 *    the wrong shape without erroring, so sorting silently never worked.
 * 3. filter.subject is required by the API. list() with no filters built a body
 *    with no filter at all, which can only come back as a 400.
 *
 * Since specification 1.223.0 (v3.3.2) files.list also takes `ids`, which does
 * instead of a subject, and `term`, which searches the file name.
 */
final class FilesResourceTest extends ResourceTestCase
{
    private Files $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = $this->resource(Files::class);
    }

    // ---------------------------------------------------------------------
    // Subject types — list
    // ---------------------------------------------------------------------

    public function test_for_product_filters_by_product_subject(): void
    {
        $this->files->forProduct('product-uuid');

        $this->assertLastEndpoint('files.list');
        $this->assertLastBodyHas('filter.subject.type', 'product');
        $this->assertLastBodyHas('filter.subject.id', 'product-uuid');
    }

    public function test_for_meeting_filters_by_meeting_subject(): void
    {
        $this->files->forMeeting('meeting-uuid');

        $this->assertLastBodyHas('filter.subject.type', 'meeting');
    }

    public function test_for_project_uses_nextgen_project(): void
    {
        $this->files->forProject('project-uuid');

        $this->assertLastBodyHas('filter.subject.type', 'nextgenProject');
    }

    public function test_for_legacy_project_uses_bare_project(): void
    {
        $this->files->forLegacyProject('project-uuid');

        $this->assertLastBodyHas('filter.subject.type', 'project');
    }

    public function test_for_credit_note_filters_by_credit_note_subject(): void
    {
        $this->files->forCreditNote('credit-note-uuid');

        $this->assertLastBodyHas('filter.subject.type', 'creditNote');
    }

    public function test_temporary_is_rejected_as_a_list_subject_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('files.list accepts');

        try {
            $this->files->list(['subject' => ['type' => 'temporary', 'id' => 'file-uuid']]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_unknown_list_subject_type_throws_and_names_the_valid_types(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('product');

        $this->files->list(['subject' => ['type' => 'banana', 'id' => 'some-uuid']]);
    }

    public function test_for_subject_validates_before_building_a_request(): void
    {
        // forSubject() previously performed no validation of its own, so the
        // helpers were laxer than the raw list() call.
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->files->forSubject('banana', 'some-uuid');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    // ---------------------------------------------------------------------
    // Subject types — upload
    // ---------------------------------------------------------------------

    public function test_upload_accepts_temporary_without_a_subject_id(): void
    {
        $this->files->upload('attachment.pdf', 'temporary');

        $this->assertLastEndpoint('files.upload');
        $this->assertLastBodyHas('subject.type', 'temporary');
        $this->assertLastBodyMissing('subject.id');
    }

    public function test_upload_rejects_product(): void
    {
        // product is valid for files.list but not for files.upload.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('files.upload accepts');

        try {
            $this->files->upload('sheet.pdf', 'product', 'product-uuid');
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_upload_requires_a_subject_id_for_non_temporary_types(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject ID is required');

        $this->files->upload('document.pdf', 'company');
    }

    public function test_upload_includes_the_folder_when_given(): void
    {
        $this->files->upload('document.pdf', 'company', 'company-uuid', 'Documents');

        $this->assertLastBodyHas('folder', 'Documents');
        $this->assertLastBodyHas('subject.id', 'company-uuid');
    }

    // ---------------------------------------------------------------------
    // Sort shape
    // ---------------------------------------------------------------------

    public function test_sort_is_built_as_an_object_not_a_string(): void
    {
        $this->files->forCompany('company-uuid', [
            'sort' => 'updated_at',
            'sort_order' => 'desc',
        ]);

        $this->assertSame(
            [['field' => 'updated_at', 'order' => 'desc']],
            $this->lastBody()['sort'] ?? null,
            'sort must be an array of objects; a string array is silently ignored by the API'
        );
    }

    public function test_sort_order_defaults_to_desc(): void
    {
        $this->files->forCompany('company-uuid', ['sort' => 'updated_at']);

        $this->assertLastBodyHas('sort.0.order', 'desc');
    }

    public function test_no_sort_key_is_sent_when_no_sort_is_requested(): void
    {
        $this->files->forCompany('company-uuid');

        $this->assertLastBodyMissing('sort');
    }

    public function test_unknown_sort_field_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field');

        $this->files->forCompany('company-uuid', ['sort' => 'name']);
    }

    // ---------------------------------------------------------------------
    // Required subject filter
    // ---------------------------------------------------------------------

    public function test_list_without_a_subject_filter_throws_before_dispatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('subject filter is required');

        try {
            $this->files->list();
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_subject_filter_without_an_id_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('both type and id');

        $this->files->list(['subject' => ['type' => 'company']]);
    }

    // ---------------------------------------------------------------------
    // ids and term — specification 1.223.0
    // ---------------------------------------------------------------------

    public function test_ids_do_instead_of_a_subject(): void
    {
        $this->files->list(['ids' => ['file-1', 'file-2']]);

        $this->assertLastEndpoint('files.list');
        $this->assertSame(['ids' => ['file-1', 'file-2']], $this->lastBody()['filter']);
    }

    public function test_by_ids_sends_the_ids_filter(): void
    {
        $this->files->byIds(['file-1'], ['filters' => ['term' => 'offerte']]);

        $this->assertSame(['ids' => ['file-1'], 'term' => 'offerte'], $this->lastBody()['filter']);
    }

    public function test_ids_are_reindexed_and_a_single_id_is_wrapped(): void
    {
        $this->files->list(['ids' => [3 => 'file-1', 7 => 'file-2']]);
        $this->assertLastBodyHas('filter.ids', ['file-1', 'file-2']);

        $this->files->list(['ids' => 'file-3']);
        $this->assertLastBodyHas('filter.ids', ['file-3']);
    }

    public function test_subject_and_ids_can_be_combined(): void
    {
        $this->files->forDeal('deal-uuid', ['filters' => ['ids' => ['file-1']]]);

        $this->assertLastBodyHas('filter.subject.id', 'deal-uuid');
        $this->assertLastBodyHas('filter.ids', ['file-1']);
    }

    public function test_term_searches_within_a_subject(): void
    {
        $this->files->forDeal('deal-uuid', ['filters' => ['term' => 'offerte']]);

        $this->assertLastBodyHas('filter.subject.type', 'deal');
        $this->assertLastBodyHas('filter.term', 'offerte');
    }

    public function test_a_numeric_term_is_sent_as_a_string(): void
    {
        $this->files->forDeal('deal-uuid', ['filters' => ['term' => 2026]]);

        $this->assertLastBodyHas('filter.term', '2026');
    }

    public function test_an_empty_term_is_left_out(): void
    {
        $this->files->forDeal('deal-uuid', ['filters' => ['term' => '']]);

        $this->assertSame(['subject'], array_keys($this->lastBody()['filter']));
    }

    public function test_term_alone_is_not_enough(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('subject filter is required');

        try {
            $this->files->list(['term' => 'offerte']);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_empty_ids_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one file id');

        try {
            $this->files->byIds([]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_ids_must_be_non_empty_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty strings');

        try {
            $this->files->byIds(['file-1', 42]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_an_array_term_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('term filter for files.list must be a string');

        $this->files->byIds(['file-1'], ['filters' => ['term' => ['offerte']]]);
    }

    // ---------------------------------------------------------------------
    // Pagination
    // ---------------------------------------------------------------------

    public function test_pagination_defaults_are_applied(): void
    {
        $this->files->forCompany('company-uuid');

        $this->assertLastBodyHas('page.size', 20);
        $this->assertLastBodyHas('page.number', 1);
    }

    public function test_pagination_options_are_forwarded(): void
    {
        $this->files->forCompany('company-uuid', [
            'page_size' => 100,
            'page_number' => 3,
        ]);

        $this->assertLastBodyHas('page.size', 100);
        $this->assertLastBodyHas('page.number', 3);
    }

    // ---------------------------------------------------------------------
    // Single-record endpoints
    // ---------------------------------------------------------------------

    public function test_info_sends_only_the_id(): void
    {
        $this->files->info('file-uuid');

        $this->assertLastEndpoint('files.info');
        $this->assertLastBody(['id' => 'file-uuid']);
    }

    public function test_download_sends_only_the_id(): void
    {
        $this->files->download('file-uuid');

        $this->assertLastEndpoint('files.download');
        $this->assertLastBody(['id' => 'file-uuid']);
    }

    public function test_delete_sends_only_the_id(): void
    {
        $this->files->delete('file-uuid');

        $this->assertLastEndpoint('files.delete');
        $this->assertLastBody(['id' => 'file-uuid']);
    }

    public function test_queued_responses_are_returned_to_the_caller(): void
    {
        $this->api->queueListResponse([
            ['id' => 'file-uuid', 'name' => 'datasheet.pdf'],
        ]);

        $response = $this->files->forProduct('product-uuid');

        $this->assertSame('datasheet.pdf', $response['data'][0]['name']);
        $this->assertRequestCount(1);
    }
}
