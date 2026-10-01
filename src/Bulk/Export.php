<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

use Closure;
use Illuminate\Support\LazyCollection;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Support\Cursor;
use RuntimeException;
use Throwable;

/**
 * Every record of one resource, read page by page, written to a file or
 * handed to a callback — memory stays flat whatever the account's size.
 *
 *     Teamleader::bulk()->export('contacts', ['tags' => ['customer']])
 *         ->toCsv(storage_path('contacts.csv'), ['id', 'first_name', 'last_name', 'emails.0.email']);
 *
 * Paginated resources are read through cursor() — so filters, sorting and
 * includes are validated exactly as for list() — and endpoints that return
 * everything at once through a single list() call.
 */
final class Export
{
    /** @var (Closure(int, ?int): void)|null */
    private ?Closure $progress = null;

    private ?Cursor $cursor = null;

    public function __construct(
        private readonly Resource $resource,
        private readonly array $filters = [],
        private readonly array $options = [],
    ) {}

    /**
     * Called after each record with the count so far and, where the endpoint
     * reports one, the total.
     *
     * @param  Closure(int $exported, ?int $total): void  $callback
     */
    public function onProgress(Closure $callback): self
    {
        $this->progress = $callback;

        return $this;
    }

    /**
     * The records, lazily. Nothing is requested until iterated.
     *
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function records(): LazyCollection
    {
        if ($this->resource->getCapabilities()['supports_pagination']) {
            $this->cursor = $this->resource->cursor($this->filters, $this->options);

            return LazyCollection::make(fn () => yield from $this->cursor);
        }

        $resource = $this->resource;
        $filters = $this->filters;
        $options = $this->options;

        return LazyCollection::make(function () use ($resource, $filters, $options) {
            $response = $resource->list($filters, $options);

            if (! empty($response['error'])) {
                throw new RuntimeException('Export failed: '.($response['message'] ?? 'unknown error'));
            }

            yield from array_values((array) ($response['data'] ?? []));
        });
    }

    /**
     * Hand each record to a callback.
     *
     * @param  callable(array<string, mixed> $record, int $index): mixed  $callback
     * @return int The number of records
     */
    public function each(callable $callback): int
    {
        $count = 0;

        foreach ($this->records() as $record) {
            $callback($record, $count);
            $this->reportProgress(++$count);
        }

        return $count;
    }

    /**
     * Write the records as CSV, one row per record.
     *
     * Columns are dot paths into a record — `emails.0.email`,
     * `address.city`. Without columns, the first record's fields are used,
     * flattened the same way; records with fields the first one lacked lose
     * them, so name the columns for anything you depend on.
     *
     * Arrays are written as JSON, booleans as true/false, null as an empty
     * cell. Text starting with = + - @ is prefixed with a quote so a
     * spreadsheet does not run it as a formula (pass `escapeFormulas: false`
     * for a file only code will read).
     *
     * The file is written next to its destination and moved into place when
     * complete, so a failed export never leaves a partial file behind.
     *
     * @param  list<string>|null  $columns
     * @return int The number of records written
     */
    public function toCsv(string $path, ?array $columns = null, string $delimiter = ',', bool $escapeFormulas = true): int
    {
        return $this->write($path, function ($handle) use ($columns, $delimiter, $escapeFormulas): int {
            $count = 0;

            foreach ($this->records() as $record) {
                if ($columns === null) {
                    $columns = array_keys(self::flatten($record));
                }

                if ($count === 0) {
                    fputcsv($handle, $columns, $delimiter, '"', '');
                }

                $row = array_map(fn (string $column) => $this->cell(data_get($record, $column), $escapeFormulas), $columns);
                fputcsv($handle, $row, $delimiter, '"', '');

                $this->reportProgress(++$count);
            }

            if ($count === 0 && $columns !== null) {
                fputcsv($handle, $columns, $delimiter, '"', '');
            }

            return $count;
        });
    }

    /**
     * Write the records as JSON Lines — one JSON object per line, complete
     * and unflattened. The format to choose when the file is read back by code.
     *
     * @return int The number of records written
     */
    public function toJsonLines(string $path): int
    {
        return $this->write($path, function ($handle): int {
            $count = 0;

            foreach ($this->records() as $record) {
                fwrite($handle, json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
                $this->reportProgress(++$count);
            }

            return $count;
        });
    }

    /**
     * The total number of records, where the endpoint reports one. Available
     * once iteration has started.
     */
    public function total(): ?int
    {
        return $this->cursor?->total();
    }

    /**
     * Nested arrays as dot paths: ['address' => ['city' => 'Gent']] → ['address.city' => 'Gent'].
     * Lists stay whole (`tags` → one JSON cell), except lists of objects, whose
     * first entry is flattened (`emails.0.email`).
     *
     * @return array<string, mixed>
     */
    public static function flatten(array $record, string $prefix = ''): array
    {
        $flat = [];

        foreach ($record as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value) && $value !== [] && ! array_is_list($value)) {
                $flat += self::flatten($value, $path);
            } elseif (is_array($value) && $value !== [] && is_array($value[0])) {
                $flat += self::flatten($value[0], "{$path}.0");
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    private function cell(mixed $value, bool $escapeFormulas): string
    {
        $cell = match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };

        if ($escapeFormulas && $cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($cell)) {
            return "'".$cell;
        }

        return $cell;
    }

    /**
     * @param  Closure(resource): int  $writer
     */
    private function write(string $path, Closure $writer): int
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create directory {$directory}.");
        }

        $temporary = $path.'.'.bin2hex(random_bytes(4)).'.part';
        $handle = fopen($temporary, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Cannot write to {$temporary}.");
        }

        try {
            $count = $writer($handle);
        } catch (Throwable $e) {
            fclose($handle);
            @unlink($temporary);

            throw $e;
        }

        fclose($handle);

        if (! rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException("Cannot move the export to {$path}.");
        }

        return $count;
    }

    private function reportProgress(int $count): void
    {
        if ($this->progress !== null) {
            ($this->progress)($count, $this->cursor?->total());
        }
    }
}
