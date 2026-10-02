<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Downloads;

use Illuminate\Support\Facades\Storage;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Resources\Deals\Quotations;
use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Creditnotes;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Invoices;
use McoreServices\TeamleaderSDK\Testing\FakeResponse;
use McoreServices\TeamleaderSDK\Testing\FakeTeamleader;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * v3.1 (§B): download() returns a link that expires. downloadContents() and
 * downloadTo() request it and fetch it at once — the counterpart of
 * uploadFile(), and what re-hosting invoice PDFs for a customer portal needs.
 */
final class DownloadDocumentsTest extends ResourceTestCase
{
    private const LINK = 'https://files.teamleader.eu/download/abc';

    private function queueLink(): void
    {
        $this->api->queueResponse(['data' => ['location' => self::LINK, 'expires' => '2026-10-02T12:00:00+00:00'], 'headers' => []]);
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function resources(): array
    {
        return [
            'invoices' => [Invoices::class, 'invoices.download'],
            'credit notes' => [Creditnotes::class, 'creditNotes.download'],
            'quotations' => [Quotations::class, 'quotations.download'],
            'files' => [Files::class, 'files.download'],
        ];
    }

    #[DataProvider('resources')]
    public function test_download_contents_requests_the_link_and_fetches_it(string $class, string $endpoint): void
    {
        $this->queueLink();
        $this->api->stub([FakeTeamleader::DOWNLOAD_CONTENTS => '%PDF-1.7 the document']);

        $contents = $this->resource($class)->downloadContents('record-1');

        $this->assertSame('%PDF-1.7 the document', $contents);
        $this->assertSame([$endpoint, FakeTeamleader::DOWNLOAD_CONTENTS], $this->api->endpoints());
        $this->assertSame(self::LINK, $this->api->lastBody()['location']);
    }

    public function test_the_format_is_passed_to_the_download_endpoint(): void
    {
        $this->queueLink();

        $this->resource(Invoices::class)->downloadContents('invoice-1', 'ubl/e-fff');

        $this->assertSame(['id' => 'invoice-1', 'format' => 'ubl/e-fff'], $this->api->calls[0]['body']);
    }

    public function test_download_to_stores_the_document_on_a_disk(): void
    {
        Storage::fake('s3');
        $this->queueLink();
        $this->api->stub([FakeTeamleader::DOWNLOAD_CONTENTS => '%PDF-1.7 invoice 2026/0042']);

        $path = $this->resource(Invoices::class)->downloadTo('invoice-1', 's3', 'invoices/2026-0042.pdf');

        $this->assertSame('invoices/2026-0042.pdf', $path);
        Storage::disk('s3')->assertExists('invoices/2026-0042.pdf');
        $this->assertSame('%PDF-1.7 invoice 2026/0042', Storage::disk('s3')->get('invoices/2026-0042.pdf'));
    }

    public function test_no_link_in_the_response_throws(): void
    {
        $this->api->queueResponse(['error' => true, 'message' => 'Invoice not found', 'headers' => []]);

        $this->expectException(TeamleaderException::class);
        $this->expectExceptionMessage('invoices.download returned no link: Invoice not found');

        $this->resource(Invoices::class)->downloadContents('missing');
    }

    public function test_a_refused_download_throws_with_the_hosts_answer(): void
    {
        $this->queueLink();
        $this->api->stub([FakeTeamleader::DOWNLOAD_CONTENTS => FakeResponse::make()->status(403)->message('Link expired')]);

        $this->expectException(TeamleaderException::class);
        $this->expectExceptionMessage('Teamleader refused the download (HTTP 403): Link expired');

        $this->resource(Invoices::class)->downloadContents('invoice-1');
    }

    public function test_bulk_downloads_every_row_and_a_dry_run_writes_nothing(): void
    {
        Storage::fake('s3');
        $rows = ['inv-1' => ['invoice-1', 's3', 'invoices/1.pdf'], 'inv-2' => ['invoice-2', 's3', 'invoices/2.pdf']];

        $dry = $this->api->bulk()->call('invoices', 'downloadTo', $rows)->dryRun();

        $this->assertSame(['inv-1', 'inv-2'], array_keys($dry->succeeded()));
        Storage::disk('s3')->assertMissing('invoices/1.pdf');
        $this->assertSame(0, $this->api->callCount());

        // For real: each row requests its link, then fetches it. Stubbed by
        // endpoint, not queued: a queued response answers whatever request
        // comes next, the fetch included
        $this->api->stub([
            'invoices.download' => fn (array $body) => ['data' => ['location' => self::LINK.'/'.$body['id']]],
            FakeTeamleader::DOWNLOAD_CONTENTS => fn (array $body) => 'contents of '.$body['location'],
        ]);

        $result = $this->api->bulk()->call('invoices', 'downloadTo', $rows)->run();

        $this->assertSame(['inv-1', 'inv-2'], array_keys($result->succeeded()));
        $this->assertSame('invoices/2.pdf', $result->succeeded()['inv-2']['data']);
        $this->assertSame('contents of '.self::LINK.'/invoice-1', Storage::disk('s3')->get('invoices/1.pdf'));
        $this->assertSame('contents of '.self::LINK.'/invoice-2', Storage::disk('s3')->get('invoices/2.pdf'));
        $this->assertSame(
            ['invoices.download', FakeTeamleader::DOWNLOAD_CONTENTS, 'invoices.download', FakeTeamleader::DOWNLOAD_CONTENTS],
            $this->api->endpoints()
        );
    }
}
