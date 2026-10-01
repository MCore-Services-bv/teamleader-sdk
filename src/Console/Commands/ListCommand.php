<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use McoreServices\TeamleaderSDK\Console\Support\FilterParser;
use McoreServices\TeamleaderSDK\Console\Support\OutputFormatter;

class ListCommand extends TeamleaderCommand
{
    protected $signature = 'teamleader:list
                            {resource : The resource key, e.g. deals}
                            {--filter=* : key=value, key[]=value for lists, key.sub=value for objects (repeatable)}
                            {--sort= : field, or field:desc}
                            {--include= : Comma-separated includes}
                            {--page-size=20 : Records per page}
                            {--page=1 : Page number}
                            {--all : Every page (stops at --limit when given)}
                            {--limit= : At most this many records}
                            {--fields= : Comma-separated dot paths, e.g. id,name,emails.0.email}
                            {--format=table : table, json or csv}
                            '.self::SUBJECT_OPTION.'
                            '.self::CONNECTION_OPTION;

    protected $description = 'List Teamleader records, with the resource\'s own filter, sort and include validation';

    public function handle(): int
    {
        return $this->guarded(function (): int {
            $format = (string) $this->option('format');
            OutputFormatter::assertFormat($format);

            $key = (string) $this->argument('resource');
            $resource = $this->sdk()->resource($key);
            $filters = $this->withSubject($key, FilterParser::parse((array) $this->option('filter')));
            $options = ['page_size' => (int) $this->option('page-size'), 'page_number' => (int) $this->option('page')];

            if ($sort = $this->option('sort')) {
                [$field, $order] = array_pad(explode(':', (string) $sort, 2), 2, 'asc');
                $options['sort'] = $field;
                $options['sort_order'] = $order;
            }

            if ($include = $this->option('include')) {
                $options['include'] = OutputFormatter::fields((string) $include);
            }

            $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
            $paginated = $resource->getCapabilities()['supports_pagination'];

            if (! $paginated) {
                // The endpoint returns everything at once and takes no page options
                unset($options['page_size'], $options['page_number']);
                $records = $this->ensureSuccessful($resource->list($filters, $options))['data'] ?? [];
            } elseif ($this->option('all')) {
                $lazy = $resource->lazy($filters, $options);
                $records = $limit === null ? $lazy : $lazy->take($limit);
            } else {
                $records = $this->ensureSuccessful($resource->list($filters, $options))['data'] ?? [];
            }

            if ($limit !== null && is_array($records)) {
                $records = array_slice($records, 0, $limit);
            }

            $count = (new OutputFormatter($this))->records($records, $format, OutputFormatter::fields($this->option('fields')));

            if ($format === 'table' && $paginated && ! $this->option('all') && $count === (int) $this->option('page-size')) {
                $this->line("Page {$this->option('page')}. Next: --page=".((int) $this->option('page') + 1).', or --all.');
            }

            return self::SUCCESS;
        });
    }
}
