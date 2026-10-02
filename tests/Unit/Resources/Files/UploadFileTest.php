<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Files;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Bulk\BulkValidationException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * files.upload only returns a temporary link; the bytes are POSTed to it in a
 * second request. uploadFile() does both, so a bulk upload needs no custom
 * code — the one write a full data migration could not do end to end.
 */
final class UploadFileTest extends ResourceTestCase
{
    private const DEAL = '0b9a4c8e-1f2d-4e3a-9b5c-6d7e8f901234';

    private Files $files;

    /** @var list<string> */
    private array $paths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = $this->resource(Files::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    private function localFile(string $name, string $contents = '%PDF-1.7 test'): string
    {
        $path = sys_get_temp_dir().'/'.uniqid('tl-upload-', true).'-'.$name;
        file_put_contents($path, $contents);

        return $this->paths[] = $path;
    }

    private function queueLink(string $location = 'https://files.teamleader.eu/upload/abc'): void
    {
        $this->api->queueResponse(['data' => ['location' => $location, 'expires_at' => '2026-10-01T12:00:00+00:00'], 'headers' => []]);
    }

    public function test_requests_the_link_then_sends_the_bytes_to_it(): void
    {
        $path = $this->localFile('offerte.pdf', '%PDF-1.7 the bytes');
        $this->queueLink();
        $this->api->queueResponse(['data' => ['id' => 'file-1', 'type' => 'file']]);

        $result = $this->files->uploadFile($path, 'deal', self::DEAL, 'Offertes');

        $this->assertSame(['files.upload', 'files.upload (contents)'], $this->api->endpoints());
        $this->assertSame('deal', $this->api->calls[0]['body']['subject']['type']);
        $this->assertSame('Offertes', $this->api->calls[0]['body']['folder']);
        $this->assertSame('https://files.teamleader.eu/upload/abc', $this->api->lastBody()['location']);
        $this->assertSame('%PDF-1.7 the bytes', $this->api->lastBody()['contents']);
        $this->assertSame('file-1', $result['data']['id']);
    }

    public function test_the_name_defaults_to_the_file_name_and_can_be_overridden(): void
    {
        $path = $this->localFile('scan.jpg');

        $this->queueLink();
        $this->files->uploadFile($path, 'deal', self::DEAL);
        $this->assertStringEndsWith('scan.jpg', $this->api->calls[0]['body']['name']);

        $this->api->reset();
        $this->queueLink();
        $this->files->uploadFile($path, 'deal', self::DEAL, null, 'Identiteitskaart.jpg');
        $this->assertSame('Identiteitskaart.jpg', $this->api->calls[0]['body']['name']);
    }

    public function test_an_unknown_type_is_refused_before_a_link_is_requested(): void
    {
        $path = $this->localFile('archive.rar');

        try {
            $this->files->uploadFile($path, 'deal', self::DEAL);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("Teamleader does not accept '.rar' files", $e->getMessage());
            $this->assertStringContainsString('pdf', $e->getMessage());
        }

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_the_type_check_can_be_skipped(): void
    {
        $path = $this->localFile('archive.rar');
        $this->queueLink();

        $this->files->uploadFile($path, 'deal', self::DEAL, checkType: false);

        $this->assertSame(['files.upload', 'files.upload (contents)'], $this->api->endpoints());
    }

    public function test_a_missing_file_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found or not readable');

        $this->files->uploadFile('/nowhere/offerte.pdf', 'deal', self::DEAL);
    }

    public function test_an_empty_file_is_refused_before_a_link_is_requested(): void
    {
        $path = $this->localFile('empty.pdf', '');

        try {
            $this->files->uploadFile($path, 'deal', self::DEAL);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('is empty', $e->getMessage());
        }

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_no_link_in_the_response_throws(): void
    {
        $path = $this->localFile('offerte.pdf');
        $this->api->queueResponse(['error' => true, 'message' => 'Subject not found', 'headers' => []]);

        $this->expectException(TeamleaderException::class);
        $this->expectExceptionMessage('files.upload returned no upload link: Subject not found');

        $this->files->uploadFile($path, 'deal', self::DEAL);
    }

    public function test_bulk_call_uploads_every_row(): void
    {
        $first = $this->localFile('a.pdf');
        $second = $this->localFile('b.png');
        $this->api->queueResponses([
            ['data' => ['location' => 'https://files.teamleader.eu/upload/1']], ['data' => ['id' => 'file-a']],
            ['data' => ['location' => 'https://files.teamleader.eu/upload/2']], ['data' => ['id' => 'file-b']],
        ]);

        $result = $this->api->bulk()->call('files', 'uploadFile', [
            'lead-1' => [$first, 'deal', self::DEAL, 'Documenten'],
            'lead-2' => [$second, 'deal', self::DEAL, 'Documenten'],
        ])->run();

        $this->assertSame(['lead-1', 'lead-2'], array_keys($result->succeeded()));
        $this->assertSame('file-b', $result->succeeded()['lead-2']['data']['id']);
        $this->assertSame(4, $this->api->callCount());
    }

    public function test_bulk_validation_refuses_an_unknown_type_before_anything_is_sent(): void
    {
        $good = $this->localFile('a.pdf');
        $bad = $this->localFile('b.exe');

        try {
            $this->api->bulk()->call('files', 'uploadFile', [[$good, 'deal', self::DEAL], [$bad, 'deal', self::DEAL]])->run();
            $this->fail('Expected a validation failure.');
        } catch (BulkValidationException $e) {
            $this->assertSame([1], array_keys($e->failures));
        }

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_mime_types_match_the_specification(): void
    {
        $contract = json_decode((string) file_get_contents(__DIR__.'/../../../Fixtures/specification/contract.json'), true);
        $field = $contract['endpoints']['files.info']['response']['fields']['data.mime_type'] ?? null;
        $this->assertIsString($field, 'files.info data.mime_type is missing from the contract fixture');

        $documented = explode('|', substr($field, strpos($field, 'enum: ') + 6));

        // application/octet-stream is Teamleader's catch-all and has no extension of its own
        $known = array_values(array_unique([...array_values(Files::MIME_TYPES), 'application/octet-stream']));
        sort($documented);
        sort($known);

        $this->assertSame($documented, $known);
    }
}
