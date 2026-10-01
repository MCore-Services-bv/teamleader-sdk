<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Support;

use InvalidArgumentException;

/**
 * `--filter` options into the filters array list() takes.
 *
 *     status=active                  ['status' => 'active']
 *     tags[]=vip --filter=tags[]=b2b ['tags' => ['vip', 'b2b']]
 *     customer.type=company          ['customer' => ['type' => 'company']]
 *     active=true / x=null / n=12    true, null, 12
 *     term="123"                     '123' — quotes keep a value a string
 *
 * Keys are not checked here: list() rejects the ones its endpoint does not
 * accept, with the accepted list, so there is no second whitelist.
 */
final class FilterParser
{
    /**
     * @param  list<string>  $expressions
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException For an expression without `=`
     */
    public static function parse(array $expressions): array
    {
        $filters = [];

        foreach ($expressions as $expression) {
            $position = strpos($expression, '=');

            if ($position === false || $position === 0) {
                throw new InvalidArgumentException(
                    "Cannot read --filter={$expression}. Use key=value, key[]=value for a list, or key.sub=value."
                );
            }

            $key = substr($expression, 0, $position);
            $value = self::cast(substr($expression, $position + 1));
            $append = str_ends_with($key, '[]');
            $path = explode('.', $append ? substr($key, 0, -2) : $key);

            self::set($filters, $path, $value, $append);
        }

        return $filters;
    }

    public static function cast(string $value): mixed
    {
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            return substr($value, 1, -1);
        }

        return match (true) {
            $value === 'true' => true,
            $value === 'false' => false,
            $value === 'null' => null,
            (bool) preg_match('/^-?(0|[1-9]\d*)$/', $value) => (int) $value,
            (bool) preg_match('/^-?(0|[1-9]\d*)\.\d+$/', $value) => (float) $value,
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $path
     */
    private static function set(array &$filters, array $path, mixed $value, bool $append): void
    {
        $node = &$filters;

        foreach ($path as $segment) {
            if (! is_array($node)) {
                $node = [];
            }

            $node = &$node[$segment];
        }

        if ($append) {
            $node = is_array($node) ? [...$node, $value] : [$value];
        } else {
            $node = $value;
        }
    }
}
