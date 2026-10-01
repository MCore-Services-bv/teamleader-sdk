<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Bulk\BulkOperation;
use McoreServices\TeamleaderSDK\Bulk\BulkProgress;
use McoreServices\TeamleaderSDK\Bulk\BulkResult;
use McoreServices\TeamleaderSDK\Bulk\BulkValidationException;
use McoreServices\TeamleaderSDK\Console\Support\OutputFormatter;
use McoreServices\TeamleaderSDK\Console\Support\RowReader;
use McoreServices\TeamleaderSDK\Console\Support\WriteGuard;

/**
 * Create, update or call a method for every row of a file — the bulk
 * operations, from the terminal.
 *
 * Every row is validated before any is sent. --dry-run prints exactly what
 * would be sent and sends nothing; writing needs --write. When a run does
 * not complete, a results file is written that --resume picks up.
 */
class ImportCommand extends TeamleaderCommand
{
    protected $signature = 'teamleader:import
                            {resource : The resource key, e.g. companies}
                            {file : A .csv, .jsonl or .json file}
                            {--update : Update records: each row needs an id}
                            {--method= : Call this method per row instead; the row\'s values, in column order, are its arguments}
                            {--dry-run : Validate and show what would be sent, sending nothing}
                            {--write : Send it}
                            {--force : Do not ask for confirmation (required in production)}
                            {--continue-on-error : Keep going after a row the API refuses}
                            {--unique-by= : A column: only the first row per value is sent}
                            {--resume= : A results file from an earlier run: skip the rows that succeeded}
                            {--queue : Dispatch to the queue instead of running here}
                            {--chunk=50 : Rows per queued job}
                            {--delimiter=, : CSV delimiter}
                            {--numeric= : CSV columns to send as numbers, e.g. estimated_value.amount}
                            '.self::CONNECTION_OPTION;

    protected $description = 'Create or update Teamleader records from a CSV or JSON Lines file — dry-run first';

    public function handle(): int
    {
        return $this->guarded(function (): int {
            $resource = (string) $this->argument('resource');
            $rows = RowReader::read(
                (string) $this->argument('file'),
                (string) $this->option('delimiter'),
                OutputFormatter::fields($this->option('numeric')) ?? []
            );

            if ($rows === []) {
                $this->warn('The file has no rows. Nothing to do.');

                return self::SUCCESS;
            }

            [$operation, $describe] = $this->operation($resource, $rows);

            if ($column = $this->option('unique-by')) {
                $operation->uniqueBy(fn ($row) => is_array($row) ? data_get($row, (string) $column) : null);
            }

            if ($file = $this->option('resume')) {
                $operation->resumeFrom($this->readResume((string) $file));
            }

            $operation->continueOnError((bool) $this->option('continue-on-error'));

            if ($this->option('dry-run')) {
                return $this->dryRun($operation);
            }

            try {
                // Validation first, locally, before the question is asked
                $preview = (clone $operation)->dryRun();
            } catch (BulkValidationException $e) {
                return $this->invalid($e);
            }

            if ($preview->hasFailures()) {
                return $this->invalid(new BulkValidationException($resource, $preview->failed(), count($rows)));
            }

            $toSend = count($preview->succeeded());

            if (! (new WriteGuard($this))->allows($describe, $toSend)) {
                return self::FAILURE;
            }

            if ($this->option('queue')) {
                $batch = $operation->validateFirst(false)->dispatch((int) $this->option('chunk'));
                $this->info("{$toSend} rows queued as batch {$batch->id}.");
                $this->line("Progress: Bus::findBatch('{$batch->id}'), or Teamleader::bulk()->result('{$batch->id}').");

                return self::SUCCESS;
            }

            $bar = $this->output->createProgressBar(count($rows));
            $operation->onProgress(fn (BulkProgress $p) => $bar->setProgress($p->processed));

            $result = $operation->validateFirst(false)->run();

            $bar->finish();
            $this->newLine(2);

            return $this->report($result);
        });
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array{0: BulkOperation, 1: string}
     */
    private function operation(string $resource, array $rows): array
    {
        $bulk = $this->sdk()->bulk();
        $method = $this->option('method');

        if ($method && $this->option('update')) {
            throw new InvalidArgumentException('Use --update or --method, not both.');
        }

        if ($method) {
            $arguments = array_map(fn ($row) => is_array($row) && ! array_is_list($row) ? array_values($row) : $row, $rows);

            return [$bulk->call($resource, (string) $method, $arguments), "{$resource}()->{$method}() per row"];
        }

        if ($this->option('update')) {
            return [$bulk->update($resource, $rows), "update {$resource}"];
        }

        return [$bulk->create($resource, $rows), "create {$resource}"];
    }

    private function dryRun(BulkOperation $operation): int
    {
        $result = $operation->dryRun();
        $counts = $result->counts();

        $this->info("Dry run — nothing was sent. {$counts['succeeded']} rows valid, {$counts['failed']} invalid, {$counts['skipped']} skipped.");

        $shown = 0;

        foreach ($result->requests() as $line => $requests) {
            foreach ($requests as $request) {
                if ($shown < 3 || $this->getOutput()->isVerbose()) {
                    $this->line("<comment>line {$line}</comment> {$request['method']} {$request['endpoint']}");
                    $this->line((string) json_encode($request['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                }

                $shown++;
            }
        }

        if ($shown > 3 && ! $this->getOutput()->isVerbose()) {
            $this->line('… '.($shown - 3).' more requests (-v shows all).');
        }

        foreach ($result->failed() as $line => $failure) {
            $this->error("line {$line}: {$failure->message()}");
        }

        foreach ($result->skipped() as $line => $reason) {
            $this->line("line {$line}: skipped — {$reason}");
        }

        return $result->hasFailures() ? self::FAILURE : self::SUCCESS;
    }

    private function invalid(BulkValidationException $e): int
    {
        $this->error(count($e->failures)." of {$e->rows} rows are invalid. Nothing was sent.");

        foreach (array_slice($e->failures, 0, 20, true) as $line => $failure) {
            $this->line("  line {$line}: {$failure->message()}");
        }

        if (count($e->failures) > 20) {
            $this->line('  … and '.(count($e->failures) - 20).' more. --dry-run lists them all.');
        }

        return self::FAILURE;
    }

    private function report(BulkResult $result): int
    {
        $counts = $result->counts();
        $this->info("{$counts['succeeded']} succeeded, {$counts['failed']} failed, {$counts['skipped']} skipped.");

        foreach (array_slice($result->failed(), 0, 20, true) as $line => $failure) {
            $status = $failure->statusCode() ? " (HTTP {$failure->statusCode()})" : '';
            $this->error("line {$line}: {$failure->message()}{$status}");
        }

        if ($result->isFinished()) {
            return self::SUCCESS;
        }

        $file = storage_path('app/teamleader/import-'.$result->resource.'-'.now()->format('Ymd-His').'.json');

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }

        file_put_contents($file, json_encode([
            'resource' => $result->resource,
            'operation' => $result->operation,
            'succeeded' => $result->succeededIndices(),
            'failed' => array_map(fn ($f) => ['message' => $f->message(), 'status' => $f->statusCode()], $result->failed()),
            'skipped' => $result->skipped(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->line("Results: {$file}");
        $this->line("Fix the failed lines and run the same command with --resume={$file} — the lines that succeeded are not sent again.");

        return self::FAILURE;
    }

    /**
     * @return list<int|string>
     */
    private function readResume(string $file): array
    {
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        if (! is_array($data) || ! isset($data['succeeded']) || ! is_array($data['succeeded'])) {
            throw new InvalidArgumentException("{$file} is not a results file from teamleader:import.");
        }

        return $data['succeeded'];
    }
}
