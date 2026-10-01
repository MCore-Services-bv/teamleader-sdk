<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Support\ResourceCatalog;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * The resource's reference page, in the terminal — read from the same
 * catalog the generated API reference is built from.
 */
class DescribeCommand extends TeamleaderCommand
{
    protected $signature = 'teamleader:describe
                            {resource : The resource key, e.g. deals}
                            {--json : Output as JSON}
                            '.self::CONNECTION_OPTION;

    protected $description = 'Show what a Teamleader resource accepts: filters, sort fields, includes, methods';

    public function handle(): int
    {
        return $this->guarded(function (): int {
            $key = (string) $this->argument('resource');
            $catalog = new ResourceCatalog($this->sdk()->registeredResources());
            $resource = $catalog->resource($key);

            if ($resource === null) {
                $this->error("Unknown resource '{$key}'.".$this->suggest($key, array_keys($catalog->resources())));

                return self::INVALID;
            }

            $defaults = (new ReflectionClass($resource['class']))->getDefaultProperties();
            $described = [
                'resource' => $resource['key'],
                'class' => $resource['class'],
                'category' => $resource['category'],
                'endpoints' => $resource['endpoints'],
                'supports' => $resource['supports'],
                'filters' => $this->describedNames($defaults['commonFilters'] ?? []),
                'sort_fields' => $this->describedNames($defaults['availableSortFields'] ?? []),
                'includes' => array_values($this->names($resource['includes'])),
                'info_includes' => $resource['info_includes'] === null ? null : array_values($this->names($resource['info_includes'])),
                'methods' => $this->methods($resource['class']),
            ];

            if ($this->option('json')) {
                $this->line((string) json_encode($described, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;
            }

            $this->info("{$described['resource']} — {$described['class']}");
            $this->line('Endpoints: '.implode(', ', $described['endpoints']));
            $this->line('Supports:  '.implode(', ', array_keys(array_filter($described['supports']))));

            $this->section('Filters (list)', $described['filters']);
            $this->section('Sort fields (list)', $described['sort_fields']);
            $this->section('Includes (list)', array_fill_keys($described['includes'], ''));

            if ($described['info_includes'] !== null) {
                $this->section('Includes (info)', array_fill_keys($described['info_includes'], ''));
            }

            $this->newLine();
            $this->line('<comment>Methods</comment>');

            foreach ($described['methods'] as $signature) {
                $this->line("  {$signature}");
            }

            $this->newLine();
            $this->line("Try: php artisan teamleader:list {$described['resource']} --page-size=5");

            return self::SUCCESS;
        });
    }

    /**
     * The three closest keys, closest first: `deal` suggests `deals` before
     * `dealPhases`.
     *
     * @param  list<string>  $keys
     */
    private function suggest(string $key, array $keys): string
    {
        $distances = [];

        foreach ($keys as $candidate) {
            $distance = levenshtein(strtolower($candidate), strtolower($key));

            if ($distance <= 3 || str_contains(strtolower($candidate), strtolower($key))) {
                $distances[$candidate] = $distance;
            }
        }

        if ($distances === []) {
            return ' Run `php artisan teamleader:resources` for the list.';
        }

        asort($distances);

        return ' Did you mean: '.implode(', ', array_slice(array_keys($distances), 0, 3)).'?';
    }

    /**
     * @param  array<string, string>  $items
     */
    private function section(string $title, array $items): void
    {
        $this->newLine();
        $this->line("<comment>{$title}</comment>");

        if ($items === []) {
            $this->line('  none');

            return;
        }

        foreach ($items as $name => $description) {
            $this->line('  '.str_pad((string) $name, 30).($description !== '' ? " {$description}" : ''));
        }
    }

    /**
     * Declared either as a list or as name => description.
     *
     * @return array<string, string>
     */
    private function describedNames(array $declared): array
    {
        if (array_is_list($declared)) {
            return array_fill_keys(array_map('strval', $declared), '');
        }

        return array_map(fn ($d) => is_string($d) ? $d : '', $declared);
    }

    /** @return list<string> */
    private function names(array $declared): array
    {
        return array_is_list($declared) ? array_map('strval', $declared) : array_map('strval', array_keys($declared));
    }

    /**
     * The resource's own public methods and lazy()/cursor(), as signatures.
     *
     * @return list<string>
     */
    private function methods(string $class): array
    {
        $signatures = [];

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $own = $method->getDeclaringClass()->getName() !== Resource::class
                || in_array($method->getName(), ['lazy', 'cursor'], true);

            if (! $own || $method->isStatic() || str_starts_with($method->getName(), '__') || str_starts_with($method->getName(), 'get')) {
                continue;
            }

            $parameters = array_map(function (ReflectionParameter $p) {
                $type = $p->getType() ? $p->getType().' ' : '';

                return $type.($p->isVariadic() ? '...' : '').'$'.$p->getName().($p->isOptional() && ! $p->isVariadic() ? ' = …' : '');
            }, $method->getParameters());

            $signatures[] = $method->getName().'('.implode(', ', $parameters).')';
        }

        sort($signatures);

        return $signatures;
    }
}
