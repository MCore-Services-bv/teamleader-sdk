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
