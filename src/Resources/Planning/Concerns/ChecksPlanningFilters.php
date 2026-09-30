<?php

namespace McoreServices\TeamleaderSDK\Resources\Planning\Concerns;

use InvalidArgumentException;

/**
 * Filter checks shared by the planning resources: plannableItems.list,
 * reservations.list and userAvailability.daily/total.
 */
trait ChecksPlanningFilters
{
    /**
     * `assignees`: a list of {type: team|user, id}. On plannableItems.list and
     * reservations.list a null entry matches unassigned records.
     *
     * @return list<array{type: string, id: string}|null>
     *
     * @throws InvalidArgumentException
     */
    protected function checkedAssignees(mixed $assignees, string $endpoint, bool $allowNull): array
    {
        if (! is_array($assignees) || ! array_is_list($assignees)) {
            throw new InvalidArgumentException("assignees on {$endpoint} must be a list of ['type' => user|team, 'id' => uuid].");
        }

        foreach ($assignees as $index => $assignee) {
            if ($assignee === null && $allowNull) {
                continue;
            }

            if (! is_array($assignee) || empty($assignee['id']) || ! isset($assignee['type'])) {
                throw new InvalidArgumentException(
                    "assignees[{$index}] on {$endpoint} needs both a type and an id"
                    .($allowNull ? ', or null for unassigned.' : '.')
                );
            }

            if (! in_array($assignee['type'], ['team', 'user'], true)) {
                throw new InvalidArgumentException(
                    "Invalid assignees[{$index}].type for {$endpoint}: '{$assignee['type']}'. Must be one of: team, user."
                );
            }
        }

        return $assignees;
    }

    /**
     * Every value of a list filter must come from $allowed. A lone string is
     * wrapped in a list.
     *
     * @param  list<string>  $allowed
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    protected function checkedEnumList(mixed $values, array $allowed, string $key, string $endpoint): array
    {
        $values = is_array($values) ? array_values($values) : [$values];

        foreach ($values as $index => $value) {
            if (! in_array($value, $allowed, true)) {
                throw new InvalidArgumentException(
                    "Invalid filter.{$key}[{$index}] for {$endpoint}: ".(is_scalar($value) ? var_export($value, true) : gettype($value))
                    .'. Must be one of: '.implode(', ', $allowed).'.'
                );
            }
        }

        return $values;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function checkedDate(mixed $date, string $field): string
    {
        $parsed = is_string($date) ? \DateTime::createFromFormat('!Y-m-d', $date) : false;

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException("{$field} must be a date in YYYY-MM-DD format (e.g., 2024-01-12)");
        }

        return $date;
    }
}
