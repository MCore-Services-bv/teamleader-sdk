<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Bulk;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Bulk\Export;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * v3.0 (§5.1): bulk export — every record, page by page, to CSV, JSON Lines
 * or a callback.
 */
final class ExportTest extends ResourceTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/teamleader-export-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }

        @rmdir($this->directory);

        parent::tearDown();
    }

    public function test_csv_with_named_columns_and_dot_paths(): void
    {
        $this->pages([
            [$this->contact('c1', 'Ann', 'ann@example.test'), $this->contact('c2', 'Bob', null)],
        ]);

        $count = $this->api->bulk()->export('contacts')->toCsv($this->path('contacts.csv'), ['id', 'first_name', 'emails.0.email']);

        $this->assertSame(2, $count);
        $this->assertSame(
            "id,first_name,emails.0.email\nc1,Ann,ann@example.test\nc2,Bob,\n",
            file_get_contents($this->path('contacts.csv'))
        );
    }

    public function test_every_page_is_read(): void
    {
        $this->pages([
            [['id' => 'a'], ['id' => 'b']],
            [['id' => 'c']],
        ]);

        $count = $this->api->bulk()->export('companies', [], ['page_size' => 2])->toJsonLines($this->path('companies.jsonl'));

        $this->assertSame(3, $count);
        $this->assertSame(2, $this->api->callCount());
        $this->assertSame(['a', 'b', 'c'], array_map(
            fn (string $line) => json_decode($line, true)['id'],
            file($this->path('companies.jsonl'), FILE_IGNORE_NEW_LINES)
        ));
    }

    public function test_columns_default_to_the_first_records_fields_flattened(): void
    {
        $this->pages([[['id' => 'a', 'address' => ['city' => 'Gent'], 'tags' => ['vip', 'b2b'], 'active' => true]]]);

        $this->api->bulk()->export('companies')->toCsv($this->path('companies.csv'));

        $this->assertSame(
            "id,address.city,tags,active\na,Gent,\"[\"\"vip\"\",\"\"b2b\"\"]\",true\n",
            file_get_contents($this->path('companies.csv'))
        );
    }

    public function test_formulas_are_escaped_but_negative_numbers_are_not(): void
    {
        $this->pages([[['id' => 'a', 'name' => '=HYPERLINK("http://evil")', 'amount' => '-12.5']]]);

        $this->api->bulk()->export('companies')->toCsv($this->path('x.csv'), ['name', 'amount']);

        $this->assertStringContainsString("\"'=HYPERLINK(\"\"http://evil\"\")\",-12.5", file_get_contents($this->path('x.csv')));
    }

    public function test_an_empty_export_still_writes_the_header(): void
    {
        $this->pages([[]]);

        $this->assertSame(0, $this->api->bulk()->export('contacts')->toCsv($this->path('none.csv'), ['id']));
        $this->assertSame("id\n", file_get_contents($this->path('none.csv')));
    }

    public function test_a_failed_page_leaves_no_file_behind(): void
    {
        $this->api->queueResponses([
            ['data' => [['id' => 'a'], ['id' => 'b']], 'headers' => []],
            ['error' => true, 'status_code' => 500, 'message' => 'Server error'],
        ]);

        try {
            $this->api->bulk()->export('companies', [], ['page_size' => 2])->toCsv($this->path('broken.csv'), ['id']);
            $this->fail('A failed page must throw.');
        } catch (TeamleaderException) {
        }

        $this->assertSame([], glob($this->directory.'/*') ?: []);
    }

    public function test_each_hands_over_every_record_and_reports_progress(): void
    {
        $this->pages([[['id' => 'a'], ['id' => 'b']]]);
        $seen = [];
        $progress = [];

        $count = $this->api->bulk()->export('companies')
            ->onProgress(function (int $done, ?int $total) use (&$progress) {
                $progress[] = $done;
            })
            ->each(function (array $record) use (&$seen) {
                $seen[] = $record['id'];
            });

        $this->assertSame(2, $count);
        $this->assertSame(['a', 'b'], $seen);
        $this->assertSame([1, 2], $progress);
    }

    public function test_a_resource_without_pages_is_exported_in_one_call(): void
    {
        $this->api->queueResponse(['data' => [['id' => 'dep-1', 'name' => 'Sales']], 'headers' => []]);

        $this->assertSame(1, $this->api->bulk()->export('departments')->toJsonLines($this->path('departments.jsonl')));
        $this->assertSame(1, $this->api->callCount());
    }

    public function test_filters_are_validated_as_for_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->api->bulk()->export('companies', ['name' => 'Acme'])->toCsv($this->path('x.csv'), ['id']);
    }

    public function test_an_unknown_resource_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown Teamleader resource 'contact'");

        $this->api->bulk()->export('contact');
    }

    public function test_flatten(): void
    {
        $this->assertSame(
            ['id' => 'a', 'address.city' => 'Gent', 'emails.0.email' => 'x@y.z', 'tags' => ['vip'], 'empty' => []],
            Export::flatten(['id' => 'a', 'address' => ['city' => 'Gent'], 'emails' => [['email' => 'x@y.z']], 'tags' => ['vip'], 'empty' => []])
        );
    }

    // -- helpers ----------------------------------------------------------------

    /**
     * @param  list<list<array<string, mixed>>>  $pages
     */
    private function pages(array $pages): void
    {
        foreach ($pages as $records) {
            $this->api->queueResponse(['data' => $records, 'headers' => []]);
        }
    }

    private function contact(string $id, string $name, ?string $email): array
    {
        return ['id' => $id, 'first_name' => $name, 'emails' => $email === null ? [] : [['type' => 'primary', 'email' => $email]]];
    }

    private function path(string $file): string
    {
        return $this->directory.'/'.$file;
    }
}
