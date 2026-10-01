<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Support;

use Illuminate\Console\Command;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Bulk\Export;

/**
 * Records as a table, JSON or CSV on the command's output.
 *
 * Fields are dot paths. Without --fields, a table shows the first record's
 * flattened scalar fields (at most eight); JSON shows records whole.
 */
final class OutputFormatter
{
    public const FORMATS = ['table', 'json', 'csv'];

    private const DEFAULT_TABLE_COLUMNS = 8;

    public function __construct(private readonly Command $command) {}

    public static function assertFormat(string $format): void
    {
        if (! in_array($format, self::FORMATS, true)) {
            throw new InvalidArgumentException("Unknown --format={$format}. Use: ".implode(', ', self::FORMATS).'.');
        }
    }

    /**
     * @param  iterable<array<string, mixed>>  $records
     * @param  list<string>|null  $fields
     */
    public function records(iterable $records, string $format, ?array $fields = null): int
    {
        self::assertFormat($format);

        $records = is_array($records) ? array_values($records) : iterator_to_array($records, false);

        if ($format === 'json') {
            $data = $fields === null ? $records : array_map(fn (array $r) => $this->pick($r, $fields), $records);
            $this->command->line((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return count($records);
        }

        $fields ??= $this->defaultFields($records);

        if ($format === 'csv') {
            $stream = fopen('php://temp', 'w+');
            fputcsv($stream, $fields, ',', '"', '');

            foreach ($records as $record) {
                fputcsv($stream, array_map(fn ($f) => $this->cell(data_get($record, $f)), $fields), ',', '"', '');
            }

            rewind($stream);
            $this->command->getOutput()->write((string) stream_get_contents($stream));
            fclose($stream);

            return count($records);
        }

        if ($records === []) {
            $this->command->line('No records.');

            return 0;
        }

        $this->command->table($fields, array_map(
            fn (array $record) => array_map(fn ($f) => $this->truncate($this->cell(data_get($record, $f))), $fields),
            $records
        ));

        return count($records);
    }

    /**
     * One record as field / value rows.
     *
     * @param  list<string>|null  $fields
     */
    public function record(array $record, string $format, ?array $fields = null): void
    {
        self::assertFormat($format);

        if ($format !== 'table') {
            $this->records([$record], $format, $fields);

            return;
        }

        $flat = $fields === null ? Export::flatten($record) : $this->pick($record, $fields);

        $this->command->table(['Field', 'Value'], array_map(
            fn ($key, $value) => [$key, $this->truncate($this->cell($value), 100)],
            array_keys($flat),
            array_values($flat)
        ));
    }

    /**
     * @return list<string>|null
     */
    public static function fields(?string $option): ?array
    {
        if ($option === null || trim($option) === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $option)), fn ($f) => $f !== ''));
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<string>
     */
    private function defaultFields(array $records): array
    {
        if ($records === []) {
            return ['id'];
        }

        $scalar = array_filter(Export::flatten($records[0]), fn ($v) => ! is_array($v));

        return array_slice(array_keys($scalar), 0, self::DEFAULT_TABLE_COLUMNS);
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function pick(array $record, array $fields): array
    {
        $picked = [];

        foreach ($fields as $field) {
            $picked[$field] = data_get($record, $field);
        }

        return $picked;
    }

    private function cell(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }

    private function truncate(string $value, int $length = 40): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length - 1).'…' : $value;
    }
}
