<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

/**
 * Shared behaviour of the resources on the `projects-v2/` API path: projects,
 * project groups, tasks and materials.
 *
 * The four share their assign / unassign endpoints, their money objects
 * (`{amount, currency}` with the same 23 currencies), their duration objects
 * (`{value, unit}`), their assignee lists and their colour palette. Until
 * v2.2.9 each resource carried its own copy of those checks, and the copies
 * had drifted: tasks checked currencies and materials did not, groups checked
 * dates and tasks used a different date check, and projects checked nothing on
 * update at all.
 */
abstract class ProjectsV2Resource extends Resource
{
    use ValidatesWritePayload;

    /** `assignee.type` on assign / unassign, and `assignees[].type` on create */
    public const ASSIGNEE_TYPES = ['team', 'user'];

    /** `currency` on every money object */
    public const CURRENCIES = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK', 'EUR', 'GBP', 'INR', 'ISK',
        'JPY', 'MAD', 'MXN', 'NOK', 'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

    /** `color` on projects and project groups */
    public const COLORS = [
        '#00B2B2', '#008A8C', '#992600', '#ED9E00', '#D157D3', '#A400B2',
        '#0071F2', '#004DA6', '#64788F', '#C0C0C4', '#82828C', '#1A1C20',
    ];

    /** `unit` on duration objects: time_estimated, time_budget, initial_time_tracked */
    public const TIME_UNITS = ['hours', 'minutes', 'seconds'];

    /** `billing_method.update_strategy` on projects.update and projectGroups.update */
    public const UPDATE_STRATEGIES = ['none', 'cascade'];

    // -- assignees ------------------------------------------------------------

    /**
     * Assign a user or team
     *
     * @param  string  $id  UUID of the project, group, task or material
     * @param  string  $assigneeType  user or team
     * @param  string  $assigneeId  UUID of the user or team
     *
     * @throws InvalidArgumentException When the assignee type is not user or team
     */
    public function assign(string $id, string $assigneeType, string $assigneeId): array
    {
        $endpoint = $this->getBasePath().'.assign';
        $this->assertEnum($assigneeType, self::ASSIGNEE_TYPES, 'assignee.type', $endpoint);

        return $this->api->request('POST', $this->getBasePath().'.assign', [
            'id' => $id,
            'assignee' => ['type' => $assigneeType, 'id' => $assigneeId],
        ]);
    }

    /**
     * Unassign a user or team
     *
     * @param  string  $id  UUID of the project, group, task or material
     * @param  string  $assigneeType  user or team
     * @param  string  $assigneeId  UUID of the user or team
     *
     * @throws InvalidArgumentException When the assignee type is not user or team
     */
    public function unassign(string $id, string $assigneeType, string $assigneeId): array
    {
        $endpoint = $this->getBasePath().'.unassign';
        $this->assertEnum($assigneeType, self::ASSIGNEE_TYPES, 'assignee.type', $endpoint);

        return $this->api->request('POST', $this->getBasePath().'.unassign', [
            'id' => $id,
            'assignee' => ['type' => $assigneeType, 'id' => $assigneeId],
        ]);
    }

    public function assignUser(string $id, string $userId): array
    {
        return $this->assign($id, 'user', $userId);
    }

    public function assignTeam(string $id, string $teamId): array
    {
        return $this->assign($id, 'team', $teamId);
    }

    public function unassignUser(string $id, string $userId): array
    {
        return $this->unassign($id, 'user', $userId);
    }

    public function unassignTeam(string $id, string $teamId): array
    {
        return $this->unassign($id, 'team', $teamId);
    }

    /**
     * @return list<string>
     */
    public function getAvailableAssigneeTypes(): array
    {
        return self::ASSIGNEE_TYPES;
    }

    // -- list helpers -----------------------------------------------------------

    /**
     * Reject list() options the endpoint cannot honour.
     *
     * An unsupported `sort` or `include` used to be dropped without a word, so
     * the call returned results in the default order, or without the data asked
     * for, and looked successful.
     *
     * @param  list<string>  $allowed
     *
     * @throws InvalidArgumentException
     */
    protected function rejectUnknownOptions(array $options, array $allowed, string $endpoint): void
    {
        $unknown = array_values(array_diff(array_keys($options), $allowed));

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                "{$endpoint} does not support the option".(count($unknown) > 1 ? 's' : '').': '
                .implode(', ', $unknown).'. Supported: '.($allowed === [] ? 'none' : implode(', ', $allowed)).'.'
            );
        }
    }

    /**
     * `page` from the page_size / page_number options, or null when neither is set.
     *
     * @return array{size: int, number: int}|null
     */
    protected function pageFromOptions(array $options): ?array
    {
        if (! isset($options['page_size']) && ! isset($options['page_number'])) {
            return null;
        }

        return [
            'size' => (int) ($options['page_size'] ?? 20),
            'number' => (int) ($options['page_number'] ?? 1),
        ];
    }

    /**
     * Wrap a lone string in a list for filters the API types as arrays.
     *
     * @param  list<string>  $arrayKeys
     */
    protected function wrapArrayFilters(array $filters, array $arrayKeys): array
    {
        foreach ($arrayKeys as $key) {
            if (isset($filters[$key]) && ! is_array($filters[$key])) {
                $filters[$key] = [$filters[$key]];
            }
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }

    // -- write-payload checks ---------------------------------------------------

    /**
     * Each named field, when present and not null, must be {amount: number,
     * currency: one of CURRENCIES}.
     *
     * @param  list<string>  $fields
     *
     * @throws InvalidArgumentException
     */
    protected function assertMoney(array $data, array $fields, string $endpoint): void
    {
        foreach ($fields as $field) {
            if (! isset($data[$field])) {
                continue;
            }

            $money = $data[$field];

            if (! is_array($money) || ! array_key_exists('amount', $money) || ! array_key_exists('currency', $money)) {
                throw new InvalidArgumentException(
                    "{$field} on {$endpoint} must be ['amount' => number, 'currency' => code], or null to clear it."
                );
            }

            if (! is_numeric($money['amount'])) {
                throw new InvalidArgumentException("{$field}.amount on {$endpoint} must be a number.");
            }

            $this->assertEnum($money['currency'], self::CURRENCIES, "{$field}.currency", $endpoint);
        }
    }

    /**
     * Each named field, when present and not null, must be {value: number,
     * unit: hours|minutes|seconds}.
     *
     * @param  list<string>  $fields
     *
     * @throws InvalidArgumentException
     */
    protected function assertDuration(array $data, array $fields, string $endpoint): void
    {
        foreach ($fields as $field) {
            if (! isset($data[$field])) {
                continue;
            }

            $duration = $data[$field];

            if (! is_array($duration) || ! isset($duration['value'], $duration['unit'])) {
                throw new InvalidArgumentException(
                    "{$field} on {$endpoint} must be ['value' => number, 'unit' => hours|minutes|seconds]."
                );
            }

            $this->assertEnum($duration['unit'], self::TIME_UNITS, "{$field}.unit", $endpoint);
        }
    }

    /**
     * `assignees`, when present, must be a list of {type: team|user, id}.
     *
     * @throws InvalidArgumentException
     */
    protected function assertAssignees(array $data, string $endpoint): void
    {
        if (! array_key_exists('assignees', $data)) {
            return;
        }

        if (! is_array($data['assignees']) || ! array_is_list($data['assignees'])) {
            throw new InvalidArgumentException("assignees on {$endpoint} must be a list of ['type' => team|user, 'id' => uuid].");
        }

        foreach ($data['assignees'] as $index => $assignee) {
            if (! is_array($assignee) || empty($assignee['id']) || ! isset($assignee['type'])) {
                throw new InvalidArgumentException("assignees[{$index}] on {$endpoint} needs both a type and an id.");
            }

            $this->assertEnum($assignee['type'], self::ASSIGNEE_TYPES, "assignees[{$index}].type", $endpoint);
        }
    }

    /**
     * Each named field, when present and not null, must be a Y-m-d date.
     *
     * @param  list<string>  $fields
     *
     * @throws InvalidArgumentException
     */
    protected function assertDates(array $data, array $fields, string $endpoint): void
    {
        foreach ($fields as $field) {
            if (! isset($data[$field])) {
                continue;
            }

            $value = $data[$field];
            $parsed = is_string($value) ? \DateTime::createFromFormat('Y-m-d', $value) : false;

            if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
                throw new InvalidArgumentException(
                    "{$field} on {$endpoint} must be a date in Y-m-d format, e.g. 2026-01-31."
                );
            }
        }
    }

    /**
     * Update endpoints take `billing_method` as {value, update_strategy}. A plain
     * string is accepted and sent with update_strategy `none`, which changes
     * only this record and leaves its children as they are.
     *
     * @param  list<string>  $methods
     *
     * @throws InvalidArgumentException
     */
    protected function normaliseBillingMethodUpdate(array $data, array $methods, string $endpoint): array
    {
        if (! isset($data['billing_method'])) {
            return $data;
        }

        if (is_string($data['billing_method'])) {
            $data['billing_method'] = ['value' => $data['billing_method'], 'update_strategy' => 'none'];
        }

        $billing = $data['billing_method'];

        if (! is_array($billing) || ! isset($billing['value'])) {
            throw new InvalidArgumentException(
                "billing_method on {$endpoint} must be a method name or ['value' => ..., 'update_strategy' => none|cascade]."
            );
        }

        $this->rejectUnknownFields($billing, ['value', 'update_strategy'], "{$endpoint} billing_method");
        $this->assertEnum($billing['value'], $methods, 'billing_method.value', $endpoint);
        $this->assertEnum($billing['update_strategy'] ?? 'none', self::UPDATE_STRATEGIES, 'billing_method.update_strategy', $endpoint);

        $data['billing_method']['update_strategy'] = $billing['update_strategy'] ?? 'none';

        return $data;
    }
}
