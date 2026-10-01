<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Console\Support\FilterParser;
use McoreServices\TeamleaderSDK\Console\Support\OutputFormatter;

class ExportCommand extends TeamleaderCommand
{
    protected $signature = 'teamleader:export
                            {resource : The resource key, e.g. contacts}
                            {--filter=* : key=value, as for teamleader:list (repeatable)}
                            {--sort= : field, or field:desc}
                            {--include= : Comma-separated includes}
                            {--format=csv : csv or jsonl}
                            {--output= : The file to write (default: a timestamped file in storage/app/teamleader)}
                            {--fields= : CSV columns as dot paths, e.g. id,name,emails.0.email}
                            {--delimiter=, : CSV delimiter — ; for Excel with a Belgian or Dutch locale}
                            {--no-formula-escape : Do not prefix text starting with = + - @ (for files only code reads)}
                            '.self::CONNECTION_OPTION;

    protected $description = 'Export every record of a Teamleader resource to CSV or JSON Lines';

    public function handle(): int
    {
        return $this->guarded(function (): int {
            $format = (string) $this->option('format');

            if (! in_array($format, ['csv', 'jsonl'], true)) {
                throw new InvalidArgumentException("Unknown --format={$format}. Use csv or jsonl.");
            }

            $resource = (string) $this->argument('resource');
            $options = [];

            if ($sort = $this->option('sort')) {
                [$options['sort'], $options['sort_order']] = array_pad(explode(':', (string) $sort, 2), 2, 'asc');
            }

            if ($include = $this->option('include')) {
                $options['include'] = OutputFormatter::fields((string) $include);
            }

            $path = (string) ($this->option('output') ?: storage_path('app/teamleader/'.$resource.'-'.now()->format('Ymd-His').'.'.$format));
            $export = $this->sdk()->bulk()->export($resource, FilterParser::parse((array) $this->option('filter')), $options);

            $bar = $this->output->createProgressBar();
            $bar->setFormat(' %current% records [%bar%] %elapsed:6s%');
            $export->onProgress(function (int $done, ?int $total) use ($bar) {
                if ($total !== null && $bar->getMaxSteps() !== $total) {
                    $bar->setMaxSteps($total);
                    $bar->setFormat(' %current%/%max% records [%bar%] %percent:3s%% %elapsed:6s%');
                }

                $bar->setProgress($done);
            });

            $count = $format === 'csv'
                ? $export->toCsv($path, OutputFormatter::fields($this->option('fields')), (string) $this->option('delimiter'), ! $this->option('no-formula-escape'))
                : $export->toJsonLines($path);

            $bar->finish();
            $this->newLine(2);
            $this->info("{$count} records written to {$path}");

            return self::SUCCESS;
        });
    }
}
