<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Support;

use InvalidArgumentException;
use JsonException;

/**
 * Rows for teamleader:import from CSV, JSON Lines or a JSON array — keyed by
 * line number, so every result and error points at the line in your file.
 *
 * CSV: the header names the fields, as dot paths (`address.city`,
 * `emails.0.email`). Cells are text, except `true`, `false`, `null`, JSON
 * objects and arrays (`["vip","b2b"]`), and the columns listed as numeric.
 * Empty cells are left out — on an update, an empty cell does not clear the
 * field. Numbers stay text by default because a postal code or a phone
 * number looks like one; for typed data, use JSON Lines.
 */
final class RowReader
{
    /**
     * @param  list<string>  $numericColumns
     * @return array<int, array<string, mixed>> line number => row
     */
    public static function read(string $path, string $delimiter = ',', array $numericColumns = []): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("Cannot read {$path}.");
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'csv', 'txt' => self::csv($path, $delimiter, $numericColumns),
            'jsonl', 'ndjson' => self::jsonLines($path),
            'json' => self::jsonArray($path),
            default => throw new InvalidArgumentException("Unknown file type for {$path}. Use .csv, .jsonl or .json."),
        };
    }

    /**
     * @param  list<string>  $numeric
     * @return array<int, array<string, mixed>>
     */
    private static function csv(string $path, string $delimiter, array $numeric): array
    {
        $handle = fopen($path, 'rb');
        $header = fgetcsv($handle, null, $delimiter, '"', '');

        if (! is_array($header) || $header === [null]) {
            fclose($handle);

            throw new InvalidArgumentException("{$path} has no header row.");
        }

        // A UTF-8 byte order mark, as Excel writes one
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn ($column) => trim((string) $column), $header);

        $rows = [];
        $line = 1;

        while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;

            if ($cells === [null]) {
                continue; // blank line
            }

            if (count($cells) !== count($header)) {
                fclose($handle);

                throw new InvalidArgumentException("Line {$line} of {$path} has ".count($cells).' cells; the header has '.count($header).'.');
            }

            $row = [];

            foreach ($header as $position => $column) {
                $cell = (string) $cells[$position];

                if ($cell === '' || $column === '') {
                    continue;
                }

                data_set($row, $column, self::value($cell, in_array($column, $numeric, true)));
            }

            $rows[$line] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private static function value(string $cell, bool $numeric): mixed
    {
        if ($numeric && is_numeric($cell)) {
            return str_contains($cell, '.') ? (float) $cell : (int) $cell;
        }

        if (in_array($cell, ['true', 'false', 'null'], true)) {
            return ['true' => true, 'false' => false, 'null' => null][$cell];
        }

        if (($cell[0] === '[' || $cell[0] === '{') && json_validate($cell)) {
            return json_decode($cell, true);
        }

        return $cell;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function jsonLines(string $path): array
    {
        $rows = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) as $offset => $text) {
            if (trim($text) === '') {
                continue;
            }

            try {
                $row = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new InvalidArgumentException('Line '.($offset + 1)." of {$path} is not valid JSON: {$e->getMessage()}");
            }

            $rows[$offset + 1] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, mixed>
     */
    private static function jsonArray(string $path): array
    {
        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException("{$path} is not valid JSON: {$e->getMessage()}");
        }

        if (! is_array($data) || ! array_is_list($data)) {
            throw new InvalidArgumentException("{$path} must hold a JSON array of rows.");
        }

        if ($data === []) {
            return [];
        }

        // 1-based, like line numbers
        return array_combine(range(1, count($data)), $data);
    }
}
