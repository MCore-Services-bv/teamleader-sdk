<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SdkInventory;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use ReflectionClass;

/**
 * Every method a resource calls on itself or its parent exists.
 *
 * PHP only notices an undefined method when the line runs, so a call to one
 * sits in the source as a fatal error waiting for the first user who takes
 * that path. Several have shipped: Deals::list() called an undefined
 * buildSort(), Pipelines::delete() and Phases::delete() called a
 * parent::delete() that Resource does not define, and ProjectTasks::list()
 * called an undefined buildFilters() whenever a filter was passed (fixed in
 * v2.2.9). This scan finds them without making a request.
 */
final class ResourceMethodCallsTest extends TestCase
{
    public function test_every_method_a_resource_calls_on_itself_exists(): void
    {
        $missing = [];

        foreach (array_unique((new SdkInventory)->registry()) as $class) {
            $reflection = new ReflectionClass($class);

            for ($current = $reflection; $current !== false && $current->getName() !== Resource::class; $current = $current->getParentClass()) {
                $source = $this->codeOnly((string) file_get_contents((string) $current->getFileName()));
                $file = basename((string) $current->getFileName());

                preg_match_all('/\$this->([A-Za-z_]\w*)\s*\(/', $source, $calls);

                foreach (array_unique($calls[1]) as $method) {
                    if (! method_exists($class, $method)) {
                        $missing[] = "{$class}::{$method}() (called in {$file})";
                    }
                }

                preg_match_all('/parent::([A-Za-z_]\w*)\s*\(/', $source, $parentCalls);
                $parent = $current->getParentClass();

                foreach (array_unique($parentCalls[1]) as $method) {
                    if ($method !== '__construct' && ($parent === false || ! $parent->hasMethod($method))) {
                        $missing[] = "parent::{$method}() (called in {$file})";
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'Resources call methods that do not exist.');
    }

    /**
     * The source with comments removed, so a docblock that mentions a call
     * ("this used to call parent::delete()") is not mistaken for one.
     */
    private function codeOnly(string $source): string
    {
        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }
}
