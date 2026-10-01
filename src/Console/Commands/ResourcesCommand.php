<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use McoreServices\TeamleaderSDK\Support\ResourceCatalog;

class ResourcesCommand extends TeamleaderCommand
{
    protected $signature = 'teamleader:resources
                            {--category= : Only one category, e.g. CRM, Deals, Invoicing}
                            {--json : Output as JSON}
                            '.self::CONNECTION_OPTION;

    protected $description = 'List every Teamleader resource and what it supports';

    public function handle(): int
    {
        return $this->guarded(function (): int {
            $catalog = new ResourceCatalog($this->sdk()->registeredResources());
            $category = $this->option('category');
            $rows = [];

            foreach ($catalog->byCategory() as $name => $resources) {
                if (is_string($category) && strcasecmp($category, $name) !== 0) {
                    continue;
                }

                foreach ($resources as $key => $resource) {
                    $supports = $resource['supports'];

                    $rows[] = [
                        'key' => $key,
                        'category' => $name,
                        'endpoint' => $resource['base_path'],
                        'list' => $supports['pagination'] ? 'paged' : 'all',
                        'filter' => $supports['filtering'],
                        'create' => $supports['creation'],
                        'update' => $supports['update'],
                        'delete' => $supports['deletion'],
                    ];
                }
            }

            if ($this->option('json')) {
                $this->line((string) json_encode($rows, JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            if ($rows === []) {
                $this->error("No category '{$category}'. Categories: ".implode(', ', array_keys($catalog->byCategory())).'.');

                return self::INVALID;
            }

            $tick = fn (bool $yes) => $yes ? '✓' : '';

            $this->table(
                ['Resource', 'Category', 'Endpoint', 'List', 'Filter', 'Create', 'Update', 'Delete'],
                array_map(fn (array $r) => [
                    $r['key'], $r['category'], $r['endpoint'], $r['list'],
                    $tick($r['filter']), $tick($r['create']), $tick($r['update']), $tick($r['delete']),
                ], $rows)
            );

            $this->line(count($rows).' resources. `php artisan teamleader:describe {resource}` for the details of one.');

            return self::SUCCESS;
        });
    }
}
