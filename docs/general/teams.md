# Teams

Manage teams in Teamleader Focus. This resource provides read-only access to team information and membership.

## Endpoint

`teams`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of all teams with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Sorting options

**Example:**
```php
$teams = $teamleader->teams()->list(['term' => 'Designers']);
```

### `search()`

Search teams by name.

**Parameters:**
- `term` (string): Search term for team name

**Example:**
```php
$teams = $teamleader->teams()->search('Designers');
```

### `byIds()`

Get specific teams by their UUIDs.

**Parameters:**
- `ids` (array): Array of team UUIDs

**Example:**
```php
$teams = $teamleader->teams()->byIds(['team-uuid-1', 'team-uuid-2']);
```

### `byTeamLead()`

Get teams led by a specific user.

**Parameters:**
- `teamLeadId` (string): User UUID of the team leader

**Example:**
```php
$teams = $teamleader->teams()->byTeamLead('user-uuid-here');
```

### `sortedByName()`

Get teams sorted by name.

**Parameters:**
- `order` (string): Sort order ('asc' or 'desc'), defaults to 'asc'

**Example:**
```php
$teams = $teamleader->teams()->sortedByName('desc');
```

## Filtering

### Available Filters

- **`ids`**: Array of team UUIDs to filter by
- **`term`**: Filter by team name
- **`team_lead_id`**: Filter teams by team leader user ID

### Filter Examples

```php
// Search by team name
$designTeams = $teamleader->teams()->list([
    'term' => 'Design'
]);

// Filter by team leader
$leaderTeams = $teamleader->teams()->list([
    'team_lead_id' => '6a9106c3-ed2a-46a2-a919-eb3d41165dd2'
]);

// Filter by specific IDs
$specificTeams = $teamleader->teams()->list([
    'ids' => [
        '92296ad0-2d61-4179-b174-9f354ca2157f',
        '53635682-c382-4fbf-9fd9-9506ca4fbcdd'
    ]
]);

// Combine filters
$filteredTeams = $teamleader->teams()->list([
    'term' => 'Dev',
    'team_lead_id' => 'user-uuid-here'
]);
```

## Sorting

### Available Sort Fields

- **`name`**: Sort by team name

### Sorting Examples

```php
// Sort by name (ascending)
$teams = $teamleader->teams()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);

// Sort by name (descending)
$teams = $teamleader->teams()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'desc'
        ]
    ]
]);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "4b5291aa-2d78-09d2-882c-6c1483f00d88",
            "name": "Designers",
            "team_lead": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "user"
            },
            "members": [
                {
                    "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                    "type": "user"
                }
            ]
        }
    ]
}
```

## Data Fields

### Team Fields

- **`id`**: Team UUID
- **`name`**: Team name
- **`team_lead`**: Team leader object (nullable)
    - **`id`**: User UUID of the team leader
    - **`type`**: Resource type (always "user")
- **`members`**: Array of team member objects
    - **`id`**: User UUID of the team member
    - **`type`**: Resource type (always "user")

## Usage Examples

### Basic Operations

```php
// Get all teams
$allTeams = $teamleader->teams()->list();

// Search for design teams
$designTeams = $teamleader->teams()->search('Design');

// Get teams sorted by name
$sortedTeams = $teamleader->teams()->sortedByName();
```

### Filtering Teams

```php
// Get teams led by a specific user
$userTeams = $teamleader->teams()->byTeamLead('user-uuid-here');

// Get specific teams by ID
$specificTeams = $teamleader->teams()->byIds([
    'team-uuid-1',
    'team-uuid-2'
]);

// Complex filtering
$filteredTeams = $teamleader->teams()->list([
    'term' => 'Development',
    'team_lead_id' => 'manager-uuid-here'
]);
```

### Working with Team Data

```php
$teams = $teamleader->teams()->list();

foreach ($teams['data'] as $team) {
    echo "Team: " . $team['name'] . "\n";
    
    if ($team['team_lead']) {
        echo "Team Lead ID: " . $team['team_lead']['id'] . "\n";
    } else {
        echo "No team lead assigned\n";
    }
    
    echo "Members: " . count($team['members']) . "\n";
    
    foreach ($team['members'] as $member) {
        echo "- Member ID: " . $member['id'] . "\n";
    }
    
    echo "\n";
}
```

### Sorting Teams

```php
// Alphabetical order (A-Z)
$teamsAsc = $teamleader->teams()->sortedByName('asc');

// Reverse alphabetical order (Z-A)
$teamsDesc = $teamleader->teams()->sortedByName('desc');

// Using the general list method with sorting
$customSort = $teamleader->teams()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'desc'
        ]
    ]
]);
```

## Error Handling

```php
$result = $teamleader->teams()->list();

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Teams API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Teams API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Convenience methods**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Teams are **read-only** in the Teamleader API
- No individual team info, create, update, or delete operations are supported
- Teams don't support pagination - all teams are returned in a single response
- Teams don't support sideloading/includes
- The `team_lead` field can be null if no team leader is assigned
- Team members are returned as an array of user reference objects
- Only sorting by `name` is supported
- The `term` filter searches within team names

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class TeamController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $teams = $teamleader->teams()->sortedByName();
        return view('teams.index', compact('teams'));
    }
    
    public function search(Request $request, TeamleaderSDK $teamleader)
    {
        $term = $request->get('q');
        $teams = $teamleader->teams()->search($term);
        return response()->json($teams);
    }
    
    public function byLeader(TeamleaderSDK $teamleader, string $userId)
    {
        $teams = $teamleader->teams()->byTeamLead($userId);
        return view('teams.by-leader', compact('teams'));
    }
}
```

### Example Integration with User Management

```php
// Get teams for a specific user (as team leader)
$userTeams = $teamleader->teams()->byTeamLead($userId);

// Get user details and their teams
$user = $teamleader->users()->info($userId);
$leaderTeams = $teamleader->teams()->byTeamLead($userId);

// Find teams where user is a member (requires checking members array)
$allTeams = $teamleader->teams()->list();
$memberTeams = collect($allTeams['data'])->filter(function ($team) use ($userId) {
    return collect($team['members'])->pluck('id')->contains($userId);
});
```

## Limitations

Since the Teams API only provides a list endpoint:

- **No individual team details**: You cannot get detailed information about a single team
- **No team management**: Teams cannot be created, updated, or deleted via API
- **No pagination**: All teams are returned at once
- **Limited sorting**: Only name-based sorting is available
- **No includes**: Additional related data cannot be sideloaded

If you need these capabilities, you'll need to use the Teamleader Focus web interface for team management.

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
