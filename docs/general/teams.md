# Teams

Read team information in Teamleader Focus.

## Overview

The Teams resource provides read-only access to the teams defined in your Teamleader account. Teams are groups of users
and can be used to assign and filter work across deals, projects, planning, and other resources.

Teams cannot be created, updated, or deleted through the API. The `info()` method is not supported — use `list()` with
an `ids` filter to retrieve a specific team.

## Endpoint

`teams`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ❌ Not supported |
| Filtering   | ✅ Supported     |
| Sorting     | ✅ Supported     |
| Sideloading | ❌ Not supported |
| Creation    | ❌ Not supported |
| Update      | ❌ Not supported |
| Deletion    | ❌ Not supported |

> **Note on pagination:** All matching teams are returned in a single response. There is no `page_size` / `page_number`
> support on this resource.

> **Note on `info()`:** Calling `info()` on this resource throws a `BadMethodCallException`. Use `byIds()` to retrieve a
> specific team.

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns teams matching the given filters, with optional sorting.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All teams
$teams = Teamleader::teams()->list();

// Filter by name
$teams = Teamleader::teams()->list([
    'term' => 'Sales',
]);

// Filter by team leader
$teams = Teamleader::teams()->list([
    'team_lead_id' => 'user-uuid',
]);

// Sorted by name ascending
$teams = Teamleader::teams()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']],
]);
```

---

## Helper Methods

### `search(string $term)`

Shorthand for `list(['term' => $term])`.

```php
$teams = Teamleader::teams()->search('Design');
```

### `byIds(array $ids)`

Shorthand for `list(['ids' => $ids])`. Use this instead of `info()` to retrieve specific teams.

```php
$teams = Teamleader::teams()->byIds(['uuid-1', 'uuid-2']);
```

### `byTeamLead(string $teamLeadId)`

Shorthand for `list(['team_lead_id' => $teamLeadId])`.

```php
$teams = Teamleader::teams()->byTeamLead('user-uuid');
```

### `sortedByName(string $order = 'asc')`

Returns all teams sorted by name.

```php
$teams = Teamleader::teams()->sortedByName();        // asc
$teams = Teamleader::teams()->sortedByName('desc');  // desc
```

---

## Filters

### `ids`

Filter by an array of team UUIDs.

```php
$teams = Teamleader::teams()->list([
    'ids' => ['uuid-1', 'uuid-2'],
]);
```

### `term`

Search filter on team name.

```php
$teams = Teamleader::teams()->list([
    'term' => 'Support',
]);
```

### `team_lead_id`

Filter by the UUID of the team leader (a user).

```php
$teams = Teamleader::teams()->list([
    'team_lead_id' => 'user-uuid',
]);
```

---

## Sorting

The only available sort field is `name`.

| Field  | Description                      |
|--------|----------------------------------|
| `name` | Sort alphabetically by team name |

```php
$teams = Teamleader::teams()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']],
]);
```

String shorthand is also accepted and normalised to the array format:

```php
$teams = Teamleader::teams()->list([], [
    'sort' => 'name',
]);
```

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'        => 'team-uuid',
            'name'      => 'Sales',
            'team_lead' => [
                'type' => 'user',
                'id'   => 'user-uuid',
            ],
        ],
    ],
]
```

---

## Usage Examples

### Build a team select list

```php
$teams = Teamleader::teams()->sortedByName();

$options = array_column($teams['data'], 'name', 'id');
// ['uuid-1' => 'Design', 'uuid-2' => 'Sales', ...]
```

### Look up a single team

```php
// info() is not supported — use byIds() instead
$result = Teamleader::teams()->byIds(['team-uuid']);
$team   = $result['data'][0] ?? null;
```

### Find teams led by a specific user

```php
$teams = Teamleader::teams()->byTeamLead('user-uuid');

foreach ($teams['data'] as $team) {
    echo $team['name'] . "\n";
}
```

### Cache team data

Teams change infrequently. Cache them to avoid unnecessary API calls:

```php
$teams = Cache::remember('tl_teams', 3600, function () {
    return Teamleader::teams()->sortedByName();
});
```

---

## Error Handling

```php
use BadMethodCallException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// info() throws immediately — don't call it
try {
    $team = Teamleader::teams()->info('team-uuid'); // throws BadMethodCallException
} catch (BadMethodCallException $e) {
    // Use byIds() instead
    $result = Teamleader::teams()->byIds(['team-uuid']);
}

// Standard error handling for list()
try {
    $teams = Teamleader::teams()->list(['term' => 'Sales']);
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Users]] — Team leaders and members are users
- [[Departments]] — Departments organise users; teams group them differently
- [[Deals]] — Deals can be assigned to teams
- [[Filtering]] — Filter and sort reference
