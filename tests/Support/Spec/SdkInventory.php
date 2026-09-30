<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Support\Spec;

use McoreServices\TeamleaderSDK\TeamleaderSDK;
use ReflectionClass;
use ReflectionMethod;

/**
 * What the SDK declares, read without booting Laravel.
 *
 * Everything is taken from class declarations — property defaults via
 * reflection and endpoint strings via the resource's source — so no resource
 * is constructed, no config is read and no request is built. That keeps the
 * inventory usable from bin/spec-audit, which runs outside a Laravel app.
 */
final class SdkInventory
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $resources = null;

    /**
     * Resource key => class, exactly as registered on TeamleaderSDK.
     *
     * @return array<string, class-string>
     */
    public function registry(): array
    {
        $defaults = (new ReflectionClass(TeamleaderSDK::class))->getDefaultProperties();

        return $defaults['resources'] ?? [];
    }

    /**
     * Deprecated resource key => canonical key, as declared on TeamleaderSDK.
     *
     * @return array<string, string>
     */
    public function deprecatedAliases(): array
    {
        $defaults = (new ReflectionClass(TeamleaderSDK::class))->getDefaultProperties();

        return $defaults['deprecatedResourceAliases'] ?? [];
    }

    /**
     * Every registered resource, described.
     *
     * The same class can be registered under more than one key (an alias kept
     * for backwards compatibility); it is described once, under the first key.
     *
     * @return array<string, array<string, mixed>>
     */
    public function resources(): array
    {
        if ($this->resources !== null) {
            return $this->resources;
        }

        $described = [];
        $seen = [];

        foreach ($this->registry() as $key => $class) {
            if (isset($seen[$class])) {
                $described[$seen[$class]]['aliases'][] = $key;

                continue;
            }

            $seen[$class] = $key;
            $described[$key] = $this->describe($key, $class);
        }

        return $this->resources = $described;
    }

    /**
     * @param  class-string  $class
     * @return array<string, mixed>
     */
    private function describe(string $key, string $class): array
    {
        $reflection = new ReflectionClass($class);
        $defaults = $reflection->getDefaultProperties();
        $basePath = $this->basePath($reflection);
        $source = (string) file_get_contents((string) $reflection->getFileName());

        [$endpoints, $dynamic] = $this->referencedEndpoints($source, $basePath);

        return [
            'key' => $key,
            'aliases' => [],
            'class' => $class,
            'short_class' => $reflection->getShortName(),
            'category' => $this->category($class),
            'file' => $reflection->getFileName(),
            'base_path' => $basePath,
            'endpoints' => $endpoints,
            'dynamic_endpoints' => $dynamic,
            'supports' => [
                'pagination' => (bool) ($defaults['supportsPagination'] ?? false),
                'filtering' => (bool) ($defaults['supportsFiltering'] ?? false),
                'sorting' => (bool) ($defaults['supportsSorting'] ?? false),
                'sideloading' => (bool) ($defaults['supportsSideloading'] ?? false),
                'creation' => (bool) ($defaults['supportsCreation'] ?? false),
                'update' => (bool) ($defaults['supportsUpdate'] ?? false),
                'deletion' => (bool) ($defaults['supportsDeletion'] ?? false),
            ],
            'filters' => $this->names($defaults['commonFilters'] ?? []),
            'sort_fields' => $this->names($defaults['availableSortFields'] ?? []),
            'includes' => $defaults['availableIncludes'] ?? [],
            // Resources whose .info endpoint takes a different include set
            // from .list declare it separately; null means "same as list".
            'info_includes' => $defaults['infoIncludes'] ?? null,
            'includes_is_list' => array_is_list($defaults['availableIncludes'] ?? []),
            'usage_examples' => $defaults['usageExamples'] ?? [],
            'public_methods' => $this->publicMethods($reflection),
        ];
    }

    /**
     * getBasePath() is protected and takes no arguments, so it can be read from
     * an uninitialised instance without running the constructor.
     */
    private function basePath(ReflectionClass $reflection): string
    {
        $instance = $reflection->newInstanceWithoutConstructor();

        return (string) (new ReflectionMethod($instance, 'getBasePath'))->invoke($instance);
    }

    /**
     * `McoreServices\TeamleaderSDK\Resources\CRM\Companies` → `CRM`
     */
    private function category(string $class): string
    {
        $parts = explode('\\', $class);
        $index = array_search('Resources', $parts, true);

        return $index === false ? 'Other' : ($parts[$index + 1] ?? 'Other');
    }

    /**
     * Filters and sort fields are declared either as a flat list or as a
     * name => description map. Normalise to a sorted list of names.
     *
     * @return list<string>
     */
    private function names(array $declared): array
    {
        $names = array_is_list($declared) ? $declared : array_keys($declared);
        $names = array_values(array_unique(array_map('strval', $names)));
        sort($names);

        return $names;
    }

    /**
     * @return list<string>
     */
    private function publicMethods(ReflectionClass $reflection): array
    {
        $methods = array_map(
            fn (ReflectionMethod $method) => strtolower($method->getName()),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
        );

        return array_values(array_unique($methods));
    }

    /**
     * Endpoints named in the second argument of `->request(...)` calls.
     *
     * Resolves the three forms the SDK uses:
     *
     *   $this->getBasePath().'.list'        → {base}.list
     *   'projects-v2/tasks.assign'          → projects-v2/tasks.assign
     *   $endpoint  (assigned earlier)        → every literal assigned to it
     *
     * Anything else — interpolation, a computed action name — is returned as
     * dynamic so the report can name it for manual review rather than
     * silently dropping it.
     *
     * @return array{0: list<string>, 1: list<string>}
     */
    private function referencedEndpoints(string $source, string $basePath): array
    {
        $endpoints = [];
        $dynamic = [];

        foreach ($this->endpointArguments($source) as $expression) {
            $resolved = $this->resolve($expression, $basePath);

            if ($resolved === null && preg_match('/^\$(\w+)$/', $expression, $variable)) {
                $resolved = $this->resolveVariable($variable[1], $source, $basePath);
            }

            if ($resolved === null || $resolved === []) {
                $dynamic[] = $expression;

                continue;
            }

            array_push($endpoints, ...$resolved);
        }

        $endpoints = array_values(array_unique($endpoints));
        sort($endpoints);

        return [$endpoints, array_values(array_unique($dynamic))];
    }

    /**
     * The second argument of every `->request(` call, as source text.
     *
     * Scanned with a bracket counter rather than a regex because the argument
     * itself usually contains parentheses: `$this->getBasePath().'.list'`.
     *
     * @return list<string>
     */
    private function endpointArguments(string $source): array
    {
        $arguments = [];
        $offset = 0;

        while (($start = strpos($source, '->request(', $offset)) !== false) {
            $offset = $start + strlen('->request(');
            $parts = $this->splitArguments($source, $offset, 2);

            if (count($parts) >= 2) {
                $arguments[] = trim($parts[1]);
            }
        }

        return $arguments;
    }

    /**
     * Split the argument list starting at $offset (just inside the opening
     * parenthesis) into top-level arguments, stopping after $limit.
     *
     * @return list<string>
     */
    private function splitArguments(string $source, int $offset, int $limit): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $quote = null;
        $length = strlen($source);

        for ($i = $offset; $i < $length; $i++) {
            $char = $source[$i];

            if ($quote !== null) {
                $current .= $char;
                if ($char === '\\' && $i + 1 < $length) {
                    $current .= $source[++$i];
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
            } elseif ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
            } elseif ($char === ')' || $char === ']' || $char === '}') {
                if ($depth === 0) {
                    $parts[] = $current;

                    return $parts;
                }
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';

                if (count($parts) >= $limit) {
                    return $parts;
                }

                continue;
            }

            $current .= $char;
        }

        return $parts;
    }

    /**
     * @return list<string>|null
     */
    private function resolve(string $expression, string $basePath): ?array
    {
        if (preg_match("/^\\\$this->getBasePath\\(\\)\\s*\\.\\s*['\"](\\.[A-Za-z0-9_-]+)['\"]$/", $expression, $m)) {
            return [$basePath.$m[1]];
        }

        if (preg_match("/^['\"]([A-Za-z0-9_\\/-]+\\.[A-Za-z0-9_-]+)['\"]$/", $expression, $m)) {
            return [$m[1]];
        }

        return null;
    }

    /**
     * @return list<string>|null
     */
    private function resolveVariable(string $name, string $source, string $basePath): ?array
    {
        preg_match_all('/\$'.preg_quote($name, '/').'\s*=\s*([^;]+);/', $source, $assignments);

        $resolved = [];

        foreach ($assignments[1] as $expression) {
            $expression = trim($expression);

            // match/ternary assignments: collect every quoted endpoint inside.
            if (preg_match_all("/['\"]([A-Za-z0-9_\\/-]+\\.[A-Za-z][A-Za-z0-9_-]*)['\"]/", $expression, $literals)) {
                array_push($resolved, ...$literals[1]);
            }

            if (preg_match_all("/\\\$this->getBasePath\\(\\)\\s*\\.\\s*['\"](\\.[A-Za-z0-9_-]+)['\"]/", $expression, $suffixes)) {
                foreach ($suffixes[1] as $suffix) {
                    $resolved[] = $basePath.$suffix;
                }
            }
        }

        return $resolved === [] ? null : array_values(array_unique($resolved));
    }
}
