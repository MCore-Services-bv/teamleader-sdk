<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit;

use McoreServices\TeamleaderSDK\Facades\Teamleader;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * v3.3.1: resources are reached through TeamleaderSDK::__call(), which static
 * analysis cannot see. Without `@method` annotations on the class itself,
 * PHPStan reports `Teamleader::connection('x')->users()` as an undefined
 * method, and everything chained after it — lazy() included — as mixed.
 *
 * Both the SDK and the facade must annotate every key in $resources, with
 * the class that key resolves to.
 */
#[Group('annotations')]
final class ResourceAccessorAnnotationsTest extends TestCase
{
    public function test_the_sdk_annotates_every_resource(): void
    {
        $this->assertAnnotatesEveryResource(TeamleaderSDK::class, static: false);
    }

    public function test_the_facade_annotates_every_resource(): void
    {
        $this->assertAnnotatesEveryResource(Teamleader::class, static: true);
    }

    public function test_the_sdk_annotates_nothing_that_is_not_a_resource(): void
    {
        $annotated = array_keys($this->annotatedMethods(TeamleaderSDK::class, static: false));

        $this->assertSame([], array_values(array_diff($annotated, array_keys($this->registry()))));
    }

    /**
     * @param  class-string  $class
     */
    private function assertAnnotatesEveryResource(string $class, bool $static): void
    {
        $annotated = $this->annotatedMethods($class, $static);
        $missing = [];
        $wrong = [];

        foreach ($this->registry() as $key => $resourceClass) {
            if (! isset($annotated[$key])) {
                $missing[] = $key;

                continue;
            }

            if ($annotated[$key] !== $resourceClass) {
                $wrong[] = "{$key}() is annotated as {$annotated[$key]}, but resolves to {$resourceClass}";
            }
        }

        $this->assertSame([], $missing, "{$class} has no @method annotation for: ".implode(', ', $missing));
        $this->assertSame([], $wrong, implode("\n", $wrong));
    }

    /**
     * @return array<string, class-string>
     */
    private function registry(): array
    {
        return (new ReflectionClass(TeamleaderSDK::class))->getDefaultProperties()['resources'];
    }

    /**
     * Every `@method [static] Type name()` in the class docblock, with the type
     * resolved to a fully qualified class name the way PHPStan resolves it.
     *
     * @param  class-string  $class
     * @return array<string, string>
     */
    private function annotatedMethods(string $class, bool $static): array
    {
        $reflection = new ReflectionClass($class);
        $doc = (string) $reflection->getDocComment();
        $imports = $this->imports((string) file_get_contents((string) $reflection->getFileName()));
        $pattern = $static
            ? '/@method\s+static\s+(\\\\?[\w\\\\]+)\s+(\w+)\(\)/'
            : '/@method\s+(?!static\s)(\\\\?[\w\\\\]+)\s+(\w+)\(\)/';

        preg_match_all($pattern, $doc, $matches, PREG_SET_ORDER);

        $methods = [];

        foreach ($matches as [, $type, $name]) {
            $methods[$name] = $this->resolve($type, $reflection->getNamespaceName(), $imports);
        }

        return $methods;
    }

    /**
     * @param  array<string, string>  $imports  alias => fully qualified name
     */
    private function resolve(string $type, string $namespace, array $imports): string
    {
        if (str_starts_with($type, '\\')) {
            return ltrim($type, '\\');
        }

        $first = explode('\\', $type)[0];

        if (isset($imports[$first])) {
            return $imports[$first].substr($type, strlen($first));
        }

        return $namespace.'\\'.$type;
    }

    /**
     * @return array<string, string>
     */
    private function imports(string $source): array
    {
        preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?;/m', $source, $matches, PREG_SET_ORDER);

        $imports = [];

        foreach ($matches as $match) {
            $alias = $match[2] ?? '';
            $imports[$alias !== '' ? $alias : substr((string) strrchr('\\'.$match[1], '\\'), 1)] = $match[1];
        }

        return $imports;
    }
}
