<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Other;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Resources\Other\Accounts;
use McoreServices\TeamleaderSDK\Resources\Other\CloudPlatforms;
use McoreServices\TeamleaderSDK\Resources\Other\Migrate;
use McoreServices\TeamleaderSDK\Resources\Templates\MailTemplates;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the Files, Templates and Other categories, against
 * specification 1.221.0.
 */
final class OtherPayloadTest extends ResourceTestCase
{
    private const UUID = '2175597d-484e-4a1c-a781-cbc3d9f893ba';

    private function expectRejected(callable $call, ?string $message = null): void
    {
        $this->expectException(InvalidArgumentException::class);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }

        try {
            $call();
        } finally {
            $this->assertNoRequestMade();
        }
    }

    // -- files -----------------------------------------------------------------

    /**
     * files.list sorts updated_at descending only; `asc` was sent until v2.2.17.
     */
    public function test_files_sort_ascending_throws(): void
    {
        $this->expectRejected(
            fn () => $this->resource(Files::class)->forCompany('company-uuid', ['sort' => 'updated_at', 'sort_order' => 'asc']),
            'descending order only'
        );
    }

    /**
     * An array sort was a TypeError until v2.2.17.
     */
    public function test_files_sort_accepts_a_sort_object(): void
    {
        $this->resource(Files::class)->forCompany('company-uuid', ['sort' => ['field' => 'updated_at', 'order' => 'desc']]);

        $this->assertLastBodyHas('sort', [['field' => 'updated_at', 'order' => 'desc']]);
    }

    public function test_files_reject_filters_other_than_subject(): void
    {
        $this->expectRejected(fn () => $this->resource(Files::class)->list([
            'subject' => ['type' => 'deal', 'id' => 'deal-uuid'],
            'folder' => 'x',
        ]), 'folder');
    }

    // -- mail templates --------------------------------------------------------

    public function test_mail_templates_reject_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(MailTemplates::class)->list(['type' => 'invoice', 'language' => 'nl']), 'language');
    }

    public function test_mail_template_helpers_survive_an_error_response(): void
    {
        $this->api->queueResponse(['error' => true, 'status_code' => 500]);

        $this->assertNull($this->resource(MailTemplates::class)->findByName('Reminder', 'invoice'));
    }

    // -- accounts --------------------------------------------------------------

    /**
     * Until v2.2.17 an error response made isUsingProjectsV2() answer false,
     * reporting a projects-v2 account as legacy.
     */
    public function test_accounts_throw_on_an_error_response(): void
    {
        $this->api->queueResponse(['error' => true, 'status_code' => 401, 'message' => 'Unauthorized']);

        $this->expectException(TeamleaderException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->resource(Accounts::class)->isUsingProjectsV2();
    }

    public function test_accounts_read_the_version(): void
    {
        $this->api->queueResponse(['data' => ['status' => 'projects-v2']]);

        $this->assertTrue($this->resource(Accounts::class)->isUsingProjectsV2());
        $this->assertLastEndpoint('accounts.projects-v2-status');
    }

    // -- cloud platforms -------------------------------------------------------

    public function test_cloud_platform_url_for_a_deal(): void
    {
        $this->resource(CloudPlatforms::class)->dealUrl(self::UUID);

        $this->assertLastEndpoint('cloudPlatforms.url');
        $this->assertLastBody(['type' => 'deal', 'id' => self::UUID]);
    }

    public function test_cloud_platform_get_url_throws_without_a_url(): void
    {
        $this->api->queueResponse(['error' => true, 'status_code' => 404]);

        $this->expectException(TeamleaderException::class);

        $this->resource(CloudPlatforms::class)->getInvoiceUrl(self::UUID);
    }

    // -- migrate ---------------------------------------------------------------

    /**
     * "0" was treated as missing until v2.2.17.
     */
    public function test_migrate_accepts_a_zero_tax_rate(): void
    {
        $this->resource(Migrate::class)->taxRate(self::UUID, '0');

        $this->assertLastBody(['department_id' => self::UUID, 'tax_rate' => '0']);
    }

    public function test_migrate_batch_throws_on_a_missing_uuid(): void
    {
        $this->api->queueResponse(['error' => true]);

        $this->expectException(TeamleaderException::class);

        $this->resource(Migrate::class)->batchIds('contact', [1]);
    }
}
