<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use McoreServices\TeamleaderSDK\Console\Support\OutputFormatter;

class InfoCommand extends TeamleaderCommand
{
    protected $signature = 'teamleader:info
                            {resource : The resource key, e.g. deals}
                            {id : The record id}
                            {--include= : Comma-separated includes (info includes can differ from list)}
                            {--fields= : Comma-separated dot paths}
                            {--format=table : table, json or csv}
                            '.self::CONNECTION_OPTION;

    protected $description = 'Show one Teamleader record';

    public function handle(): int
    {
        return $this->guarded(function (): int {
            $format = (string) $this->option('format');
            OutputFormatter::assertFormat($format);

            $resource = $this->sdk()->resource((string) $this->argument('resource'));
            $includes = OutputFormatter::fields($this->option('include'));

            $response = $includes === null
                ? $resource->info((string) $this->argument('id'))
                : $resource->info((string) $this->argument('id'), $includes);

            $response = $this->ensureSuccessful($response);

            (new OutputFormatter($this))->record((array) ($response['data'] ?? []), $format, OutputFormatter::fields($this->option('fields')));

            return self::SUCCESS;
        });
    }
}
