<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Support\Spec;

/**
 * Compares what the SDK declares against what the specification declares.
 *
 * Every finding has a stable id — `{resource}:{check}:{subject}` — so the
 * baseline can track it across runs. Severity decides what the report leads
 * with, not whether a finding is gated: SpecParityTest gates on every finding
 * that is not in the baseline.
 *
 *   error    The SDK sends or advertises something the API does not accept.
 *            These are silent failures at runtime: the API answers 200.
 *   warning  A capability flag, include or coverage gap that needs a decision.
 *   info     Something the auditor could not resolve mechanically.
 *
 * Checks, by id:
 *
 *   endpoint.unknown     SDK calls an endpoint the specification does not have
 *   endpoint.deprecated  SDK calls an endpoint the specification deprecates
 *   endpoint.unwrapped   Specification endpoint under the resource's prefix
 *                        that no resource calls
 *   endpoint.unmapped    Specification prefix no resource is registered for
 *   endpoint.dynamic     request() endpoint the auditor could not resolve
 *   filter.phantom       Advertised filter the list endpoint does not declare
 *   filter.missing       Declared filter the resource does not advertise
 *   filter.flag          $supportsFiltering disagrees with the specification
 *   sort.phantom         Advertised sort field not in the declared enum
 *   sort.missing         Declared sort field the resource does not advertise
 *   sort.flag            $supportsSorting disagrees with the specification
 *   pagination.flag      $supportsPagination disagrees with the specification
 *   include.phantom      Advertised include not named by list or info
 *   include.missing      Include named by list or info but not advertised
 *   include.flag         $supportsSideloading disagrees with the specification
 *   include.shape        $availableIncludes is a keyed map, not a list
 *   example.resource     Usage example calls a resource key that is not registered
 *   example.method       Usage example calls a method the resource does not have
 *
 * The include vocabulary is derived (the specification types `includes` as a
 * free-form string and only names values in its example and description), so
 * include findings are warnings to verify, never errors.
 */
final class SpecAuditor
{
    public const SEVERITIES = ['error', 'warning', 'info'];

    public function __construct(
        private readonly SpecContract $spec,
        private readonly SdkInventory $sdk,
    ) {}

    public function specificationVersion(): string
    {
        return $this->spec->version();
    }

    /**
     * @return array<string, array<string, mixed>> Keyed by finding id, sorted
     */
    public function findings(): array
    {
        $findings = [];
        $resources = $this->sdk->resources();

        $referenced = [];
        foreach ($resources as $resource) {
            foreach ($resource['endpoints'] as $endpoint) {
                $referenced[$endpoint] = true;
            }
        }

        foreach ($resources as $resource) {
            $this->auditEndpoints($resource, $referenced, $findings);
            $this->auditList($resource, $findings);
            $this->auditIncludes($resource, $findings);
            $this->auditExamples($resource, $findings);
        }

        $this->auditUnmapped($resources, $findings);

        ksort($findings);

        return $findings;
    }

    // -- endpoints ----------------------------------------------------------

    private function auditEndpoints(array $resource, array $referenced, array &$findings): void
    {
        foreach ($resource['endpoints'] as $endpoint) {
            if (! $this->spec->has($endpoint)) {
                $this->add($findings, $resource, 'endpoint.unknown', $endpoint, 'error',
                    "Calls `{$endpoint}`, which is not in the specification.");

                continue;
            }

            if ($this->spec->isDeprecated($endpoint)) {
                $this->add($findings, $resource, 'endpoint.deprecated', $endpoint, 'warning',
                    "Calls `{$endpoint}`, which the specification marks as deprecated.");
            }
        }

        foreach ($this->spec->endpointsFor($resource['base_path']) as $endpoint) {
            if (! isset($referenced[$endpoint])) {
                $deprecated = $this->spec->isDeprecated($endpoint) ? ' (deprecated)' : '';
                $this->add($findings, $resource, 'endpoint.unwrapped', $endpoint, 'warning',
                    "`{$endpoint}`{$deprecated} is in the specification but no resource calls it.");
            }
        }

        foreach ($resource['dynamic_endpoints'] as $expression) {
            $this->add($findings, $resource, 'endpoint.dynamic', $expression, 'info',
                "request() endpoint `{$expression}` could not be resolved mechanically — check it by hand.");
        }
    }

    private function auditUnmapped(array $resources, array &$findings): void
    {
        $prefixes = [];
        foreach ($resources as $resource) {
            $prefixes[$resource['base_path']] = true;
            foreach ($resource['endpoints'] as $endpoint) {
                $prefixes[SpecContract::prefixOf($endpoint)] = true;
            }
        }

        $unmapped = [];
        foreach ($this->spec->endpointNames() as $endpoint) {
            $prefix = SpecContract::prefixOf($endpoint);
            if (! isset($prefixes[$prefix])) {
                $unmapped[$prefix][] = $endpoint;
            }
        }

        foreach ($unmapped as $prefix => $endpoints) {
            $findings["_unmapped:endpoint.unmapped:{$prefix}"] = [
                'id' => "_unmapped:endpoint.unmapped:{$prefix}",
                'category' => 'Unmapped',
                'resource' => '_unmapped',
                'class' => null,
                'check' => 'endpoint.unmapped',
                'subject' => $prefix,
                'severity' => 'warning',
                'message' => "No resource is registered for `{$prefix}`: ".implode(', ', array_map(
                    fn ($e) => "`{$e}`".($this->spec->isDeprecated($e) ? ' (deprecated)' : ''),
                    $endpoints
                )).'.',
            ];
        }
    }

    // -- list: filters, sort, pagination ------------------------------------

    private function auditList(array $resource, array &$findings): void
    {
        $listEndpoint = $resource['base_path'].'.list';
        $list = $this->spec->endpoint($listEndpoint);

        if ($list === null || ! in_array($listEndpoint, $resource['endpoints'], true)) {
            return;
        }

        $request = $list['request'];
        $declaredFilters = $request['filter_keys'];
        $declaresFilter = in_array('filter', $request['properties'], true);

        // Some endpoints take their criteria as top-level request properties
        // rather than inside a `filter` object — levelTwoAreas.list takes
        // `country` and `language`. Resources expose those through list()'s
        // $filters argument and send them top-level, which is correct. Treat
        // them as declared, but only when the endpoint has no filter object,
        // so a real filter can never be satisfied by a same-named parameter.
        if (! $declaresFilter) {
            $parameters = array_values(array_diff(
                $request['properties'],
                ['filter', 'page', 'sort', 'includes']
            ));

            foreach ($parameters as $parameter) {
                $request['filters'][$parameter] = ['type' => 'parameter'];
            }

            $declaredFilters = $parameters;
            $declaresFilter = $parameters !== [];
        }

        // Nested filters are advertised in dot notation (`subject.type`). One is
        // valid when its root is declared as an object carrying that property,
        // and advertising any child counts as advertising the root.
        $advertisedRoots = [];

        foreach ($resource['filters'] as $filter) {
            [$root, $child] = array_pad(explode('.', $filter, 2), 2, null);
            $advertisedRoots[$root] = true;
            $declared = $request['filters'][$root] ?? null;

            if ($declared === null) {
                $this->add($findings, $resource, 'filter.phantom', $filter, 'error',
                    "Advertises filter `{$filter}`; `{$listEndpoint}` does not declare it, so the API ignores it and returns everything.");

                continue;
            }

            if ($child !== null && ! in_array($child, $declared['properties'] ?? [], true)) {
                $this->add($findings, $resource, 'filter.phantom', $filter, 'error', sprintf(
                    'Advertises filter `%s`; `%s` declares `%s` with properties %s only.',
                    $filter,
                    $listEndpoint,
                    $root,
                    implode(', ', $declared['properties'] ?? ['(none)'])
                ));
            }
        }

        foreach (array_diff($declaredFilters, array_keys($advertisedRoots)) as $filter) {
            $this->add($findings, $resource, 'filter.missing', $filter, 'warning',
                "`{$listEndpoint}` declares filter `{$filter}` ({$this->filterSummary($request['filters'][$filter])}); the resource does not advertise it.");
        }

        if ($resource['supports']['filtering'] !== $declaresFilter) {
            $this->add($findings, $resource, 'filter.flag', 'supportsFiltering', 'warning', sprintf(
                '$supportsFiltering is %s but `%s` %s a filter object.',
                var_export($resource['supports']['filtering'], true),
                $listEndpoint,
                $declaresFilter ? 'declares' : 'does not declare'
            ));
        }

        foreach (array_diff($resource['sort_fields'], $request['sort_fields']) as $field) {
            $this->add($findings, $resource, 'sort.phantom', $field, 'error',
                "Advertises sort field `{$field}`; `{$listEndpoint}` does not declare it.");
        }

        foreach (array_diff($request['sort_fields'], $resource['sort_fields']) as $field) {
            $this->add($findings, $resource, 'sort.missing', $field, 'warning',
                "`{$listEndpoint}` declares sort field `{$field}`; the resource does not advertise it.");
        }

        if ($resource['supports']['sorting'] !== $request['declares_sort']) {
            $this->add($findings, $resource, 'sort.flag', 'supportsSorting', 'warning', sprintf(
                '$supportsSorting is %s but `%s` %s sort.',
                var_export($resource['supports']['sorting'], true),
                $listEndpoint,
                $request['declares_sort'] ? 'declares' : 'does not declare'
            ));
        }

        if ($resource['supports']['pagination'] !== $request['declares_pagination']) {
            $this->add($findings, $resource, 'pagination.flag', 'supportsPagination', 'warning', sprintf(
                '$supportsPagination is %s but `%s` %s page.',
                var_export($resource['supports']['pagination'], true),
                $listEndpoint,
                $request['declares_pagination'] ? 'declares' : 'does not declare'
            ));
        }
    }

    private function filterSummary(array $filter): string
    {
        $summary = $filter['type'];

        if (isset($filter['items'])) {
            $summary .= " of {$filter['items']}";
        }
        if (! empty($filter['nullable']) || ! empty($filter['items_nullable'])) {
            $summary .= ', nullable';
        }
        if (isset($filter['enum'])) {
            $summary .= ', enum: '.implode('|', $filter['enum']);
        }
        if (isset($filter['items_enum'])) {
            $summary .= ', enum: '.implode('|', $filter['items_enum']);
        }

        return $summary;
    }

    // -- includes -------------------------------------------------------------

    private function auditIncludes(array $resource, array &$findings): void
    {
        if (! $resource['includes_is_list']) {
            $this->add($findings, $resource, 'include.shape', 'availableIncludes', 'error',
                '$availableIncludes is a keyed map; every resource must declare a flat list so getCapabilities() has one shape.');
        }

        $declares = false;
        $vocabulary = [];
        $perEndpoint = [];

        foreach (['.list', '.info'] as $suffix) {
            $endpoint = $resource['base_path'].$suffix;
            $contract = $this->spec->endpoint($endpoint);

            if ($contract === null || ! in_array($endpoint, $resource['endpoints'], true)) {
                continue;
            }

            // The vocabulary is the request's own declaration plus includes
            // named in response field descriptions ("only included with
            // `includes=expiry`"). The second source is the only one on
            // quotations.list/info, which declare no includes property.
            $named = array_values(array_unique(array_merge(
                $contract['request']['includes'],
                $contract['request']['response_includes'] ?? []
            )));

            if ($contract['request']['declares_includes'] || $named !== []) {
                $declares = true;
                $perEndpoint[$suffix] = $named;
                array_push($vocabulary, ...$named);
            }
        }

        $vocabulary = array_values(array_unique($vocabulary));
        $advertised = $resource['includes_is_list']
            ? array_map('strval', $resource['includes'])
            : array_map('strval', array_keys($resource['includes']));

        // A resource that declares $infoIncludes separately is checked per
        // endpoint: $availableIncludes against .list, $infoIncludes against .info.
        if (is_array($resource['info_includes'])) {
            $this->auditIncludeSet($findings, $resource, $advertised, $perEndpoint['.list'] ?? [], $resource['base_path'].'.list');
            $this->auditIncludeSet($findings, $resource, array_map('strval', $resource['info_includes']), $perEndpoint['.info'] ?? [], $resource['base_path'].'.info');

            $advertised = array_merge($advertised, $resource['info_includes']);
        }

        if ($resource['supports']['sideloading'] !== $declares) {
            $this->add($findings, $resource, 'include.flag', 'supportsSideloading', 'warning', $declares
                ? '$supportsSideloading is false but `.list` or `.info` declares includes.'
                : '$supportsSideloading is true but neither `.list` nor `.info` declares includes.');
        }

        if (! $declares) {
            foreach ($advertised as $include) {
                $this->add($findings, $resource, 'include.phantom', $include, 'warning',
                    "Advertises include `{$include}`, but no endpoint of this resource declares includes at all.");
            }

            return;
        }

        if (is_array($resource['info_includes'])) {
            return;
        }

        foreach (array_diff($advertised, $vocabulary) as $include) {
            $this->add($findings, $resource, 'include.phantom', $include, 'warning',
                "Advertises include `{$include}`; the specification names only: ".implode(', ', $vocabulary).'. Verify before removing.');
        }

        foreach (array_diff($vocabulary, $advertised) as $include) {
            $this->add($findings, $resource, 'include.missing', $include, 'warning',
                "The specification names include `{$include}`; the resource does not advertise it.");
        }
    }

    /**
     * @param  list<string>  $advertised
     * @param  list<string>  $declared
     */
    private function auditIncludeSet(array &$findings, array $resource, array $advertised, array $declared, string $endpoint): void
    {
        foreach (array_diff($advertised, $declared) as $include) {
            $this->add($findings, $resource, 'include.phantom', $include, 'warning', $declared === []
                ? "Advertises include `{$include}` for `{$endpoint}`, which declares no includes."
                : "Advertises include `{$include}` for `{$endpoint}`; the specification names only: ".implode(', ', $declared).'. Verify before removing.');
        }

        foreach (array_diff($declared, $advertised) as $include) {
            $this->add($findings, $resource, 'include.missing', $include, 'warning',
                "`{$endpoint}` names include `{$include}`; the resource does not advertise it.");
        }
    }

    // -- usage examples -------------------------------------------------------

    private function auditExamples(array $resource, array &$findings): void
    {
        $registry = $this->sdk->registry();
        $inventory = $this->sdk->resources();

        foreach ($resource['usage_examples'] as $name => $example) {
            $code = is_array($example) ? (string) ($example['code'] ?? '') : (string) $example;

            foreach (UsageExampleParser::chains($code) as [$key, $methods]) {
                if (! isset($registry[$key])) {
                    $this->add($findings, $resource, 'example.resource', "{$name}:{$key}", 'error',
                        "Usage example `{$name}` calls `{$key}()`, which is not a registered resource.");

                    continue;
                }

                $target = $this->describedClass($registry[$key], $inventory);

                foreach ($methods as $method) {
                    if (! in_array(strtolower($method), $target['public_methods'] ?? [], true)) {
                        $this->add($findings, $resource, 'example.method', "{$name}:{$key}->{$method}", 'error',
                            "Usage example `{$name}` calls `{$key}()->{$method}()`, which does not exist on {$target['short_class']}.");
                    }
                }
            }
        }
    }

    private function describedClass(string $class, array $inventory): array
    {
        foreach ($inventory as $resource) {
            if ($resource['class'] === $class) {
                return $resource;
            }
        }

        return ['public_methods' => [], 'short_class' => $class];
    }

    // -- helpers --------------------------------------------------------------

    private function add(array &$findings, array $resource, string $check, string $subject, string $severity, string $message): void
    {
        $id = "{$resource['key']}:{$check}:{$subject}";

        $findings[$id] = [
            'id' => $id,
            'category' => $resource['category'],
            'resource' => $resource['key'],
            'class' => $resource['short_class'],
            'check' => $check,
            'subject' => $subject,
            'severity' => $severity,
            'message' => $message,
        ];
    }
}
