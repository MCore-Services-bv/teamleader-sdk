<?php

namespace McoreServices\TeamleaderSDK\Traits;

use InvalidArgumentException;

/**
 * Client-side checks for create/update bodies, against values taken from the
 * API specification.
 *
 * The API answers 200 to a body key it does not recognise and drops it, so a
 * typo in a field name (`vat` for `vat_number`) looks like a successful update
 * that changed nothing. An invalid enum value is rejected by the API, but only
 * after a round-trip and with a less specific message. Both are cheaper to
 * catch here.
 *
 * Each resource keeps its own field lists and enums as constants, so the
 * spec-contract tests can compare them against the specification fixture.
 */
trait ValidatesWritePayload
{
    /**
     * Reject top-level keys the endpoint does not declare.
     *
     * @param  list<string>  $allowed
     *
     * @throws InvalidArgumentException
     */
    protected function rejectUnknownFields(array $data, array $allowed, string $endpoint): void
    {
        $unknown = array_values(array_diff(array_keys($data), $allowed));

        if ($unknown === []) {
            return;
        }

        sort($allowed);

        throw new InvalidArgumentException(
            "{$endpoint} does not accept: ".implode(', ', $unknown).'. '
            .'The API would ignore '.(count($unknown) === 1 ? 'it' : 'them').' and report success. '
            .'Accepted fields: '.implode(', ', $allowed).'.'
        );
    }

    /**
     * Reject a scalar value outside its enum. Null passes — it clears the field.
     *
     * @param  list<string>  $allowed
     *
     * @throws InvalidArgumentException
     */
    protected function assertEnum(mixed $value, array $allowed, string $path, string $endpoint): void
    {
        if ($value === null || in_array($value, $allowed, true)) {
            return;
        }

        throw new InvalidArgumentException(
            "Invalid {$path} for {$endpoint}: ".(is_scalar($value) ? var_export($value, true) : gettype($value))
            .'. Must be one of: '.implode(', ', $allowed).'.'
        );
    }

    /**
     * Check one key on every item of a list field, e.g. `emails[].type`.
     *
     * @param  list<string>  $allowed
     *
     * @throws InvalidArgumentException
     */
    protected function assertItemEnum(array $data, string $field, string $key, array $allowed, string $endpoint): void
    {
        if (! isset($data[$field]) || ! is_array($data[$field])) {
            return;
        }

        foreach ($data[$field] as $index => $item) {
            if (is_array($item) && array_key_exists($key, $item)) {
                $this->assertEnum($item[$key], $allowed, "{$field}[{$index}].{$key}", $endpoint);
            }
        }
    }

    /**
     * Normalise an include argument (string, comma-separated string or list)
     * and reject anything outside the endpoint's accepted set.
     *
     * @param  list<string>  $allowed
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    protected function assertIncludes(mixed $includes, array $allowed, string $endpoint): array
    {
        if ($includes === null || $includes === '' || $includes === []) {
            return [];
        }

        $requested = [];

        foreach (is_array($includes) ? $includes : [$includes] as $include) {
            foreach (explode(',', (string) $include) as $part) {
                if (trim($part) !== '') {
                    $requested[] = trim($part);
                }
            }
        }

        $requested = array_values(array_unique($requested));

        foreach ($requested as $include) {
            if (! in_array($include, $allowed, true)) {
                throw new InvalidArgumentException(
                    "Invalid include for {$endpoint}: {$include}. "
                    .($allowed === [] ? 'The endpoint accepts no includes.' : 'Accepts: '.implode(', ', $allowed).'.')
                );
            }
        }

        return $requested;
    }
}
