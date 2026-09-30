<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Support\Spec;

/**
 * Extracts resource method chains from the code strings in $usageExamples.
 *
 *   $teamleader->deals()->with('lead.customer')->info('id')
 *   Teamleader::deals()->list()
 *
 * both yield ['deals', ['with', 'info']]. Arguments are skipped with a
 * bracket counter that respects quoted strings, so nested calls inside an
 * argument (`Carbon::now()->subDay()`) are not mistaken for chain links.
 */
final class UsageExampleParser
{
    /**
     * @return list<array{0: string, 1: list<string>}>
     */
    public static function chains(string $code): array
    {
        $chains = [];

        preg_match_all(
            '/(?:\$teamleader->|\$sdk->|Teamleader::)([a-zA-Z_]\w*)\(\)/',
            $code,
            $starts,
            PREG_OFFSET_CAPTURE
        );

        foreach ($starts[0] as $index => [$match, $offset]) {
            $key = $starts[1][$index][0];
            $position = $offset + strlen($match);
            $methods = [];

            while (preg_match('/\G\s*->\s*([a-zA-Z_]\w*)\s*\(/', $code, $link, 0, $position)) {
                $methods[] = $link[1];
                $position = self::skipArguments($code, $position + strlen($link[0]));

                if ($position === null) {
                    break;
                }
            }

            $chains[] = [$key, $methods];
        }

        return $chains;
    }

    /**
     * Return the offset just past the parenthesis that closes the argument
     * list opened immediately before $position, or null if it never closes.
     */
    private static function skipArguments(string $code, int $position): ?int
    {
        $depth = 1;
        $quote = null;
        $length = strlen($code);

        for ($i = $position; $i < $length; $i++) {
            $char = $code[$i];

            if ($quote !== null) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $quote = $char;
            } elseif ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth--;
                if ($depth === 0) {
                    return $i + 1;
                }
            }
        }

        return null;
    }
}
