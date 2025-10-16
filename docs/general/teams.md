# Teams

Manage teams in Teamleader Focus.

## Overview

The Teams resource provides read-only access to team information in your Teamleader account. Teams are groups of users that work together, and can be used to organize and filter data throughout Teamleader.

**Important:** The Teams resource is read-only. You cannot create, update, or delete teams through the API. Teams must be managed through the Teamleader interface.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`teams`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of teams with optional filtering and sorting.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Sorting options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all teams
$teams = Teamleader::teams()->list();

// Search teams by name
$teams = Teamleader::teams()->list([
    'term' => 'Sales'
]);

// Get teams sorted by name
$teams = Teamleader::teams()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);
```

**Note:** The Teams resource does not support the `info()` method. Use `list()` with filters to get specific teams.

## Helper Methods

The Teams resource provides convenient helper methods for common operations:

### `search()`

Search teams by name.

```php
// Search for teams containing "Sales"
$teams = Teamleader::teams()->search('Sales');

// Search for teams containing "Marketing"
$teams = Teamleader::teams()->search('Marketing');
```

## Filters

### Available Filters

#### `ids`
Filter by specific team UUIDs.

```php
$teams = Teamleader::teams()->list([
    'ids' => ['team-uuid-1', 'team-uuid-2']
]);
```

#### `term`
Search filter on team name.

```php
// Find teams with "Design" in the name
$teams = Teamleader::teams()->list([
    'term' => 'Design'
]);
```

#### `team_lead_id`
Filter teams by team leader (user UUID).

```php
// Get all teams led by a specific user
$teams = Teamleader::teams()->list([
    'team_lead_id' => 'user-uuid'
]);
```

## Sorting

### Available Sort Fields

- `name` - Sort by team name

### Sort Examples

```php
// Sort by name (ascending)
$teams = Teamleader::teams()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);

// Sort by name (descending)
$teams = Teamleader::teams()->list([], [
    'sort' => [['field' => 'name', 'order' => 'desc']]
]);
```

## Response Structure

### Team Object

```php
[
    'id' => 'team-uuid',
    'name' => 'Sales Team',
    'team_lead' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ]
]
```

## Usage Examples

### Get All Teams

```php
$teams = Teamleader::teams()->list();

foreach ($teams['data'] as $team) {
    echo $team['name'] . ' (Leader: ' . $team['team_lead']['id'] . ')' . PHP_EOL;
}
```

### Search for Teams

```php
// Search for teams
$salesTeams = Teamleader::teams()->search('Sales');
$designTeams = Teamleader::teams()->search('Design');
```

### Get Teams by Leader

```php
// Get all teams for a specific team leader
$userId = 'user-uuid';
$teams = Teamleader::teams()->list([
    'team_lead_id' => $userId
]);

if (!empty($teams['data'])) {
    echo "Teams led by this user:";
    foreach ($teams['data'] as $team) {
        echo "  - " . $team['name'] . PHP_EOL;
    }
}
```

### Build a Team Dropdown

```php
$teams = Teamleader::teams()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);

$dropdown = [];
foreach ($teams['data'] as $team) {
    $dropdown[$team['id']] = $team['name'];
}

// Use in Blade template
<select name="team_id">
    @foreach($dropdown as $id => $name)
        <option value="{{ $id }}">{{ $name }}</option>
    @endforeach
</select>
```

### Find Team by Name

```php
function findTeamByName($searchName)
{
    $teams = Teamleader::teams()->search($searchName);
    
    foreach ($teams['data'] as $team) {
        if (strcasecmp($team['name'], $searchName) === 0) {
            return $team;
        }
    }
    
    return null;
}

$salesTeam = findTeamByName('Sales Team');
```

## Common Use Cases

### Team Selection in Forms

```php
class ReportController extends Controller
{
    public function create()
    {
        $teams = Teamleader::teams()->list([], [
            'sort' => [['field' => 'name', 'order' => 'asc']]
        ]);
        
        return view('reports.create', [
            'teams' => $teams['data']
        ]);
    }
}
```

### Cache Teams

```php
use Illuminate\Support\Facades\Cache;

class TeamService
{
    public function getAllTeams()
    {
        return Cache::remember('all_teams', 3600, function() {
            return Teamleader::teams()->list([], [
                'sort' => [['field' => 'name', 'order' => 'asc']]
            ]);
        });
    }
    
    public function getTeamsByLeader($userId)
    {
        $cacheKey = "teams.leader.{$userId}";
        
        return Cache::remember($cacheKey, 3600, function() use ($userId) {
            return Teamleader::teams()->list([
                'team_lead_id' => $userId
            ]);
        });
    }
    
    public function clearCache()
    {
        Cache::flush();
    }
}
```

### Sync Teams to Local Database

```php
use App\Models\Team;
use Illuminate\Console\Command;

class SyncTeamsCommand extends Command
{
    protected $signature = 'teamleader:sync-teams';
    
    public function handle()
    {
        $this->info('Syncing teams...');
        
        $teams = Teamleader::teams()->list();
        
        foreach ($teams['data'] as $teamData) {
            Team::updateOrCreate(
                ['teamleader_id' => $teamData['id']],
                [
                    'name' => $teamData['name'],
                    'team_lead_id' => $teamData['team_lead']['id'],
                ]
            );
        }
        
        $this->info('Teams synced successfully!');
    }
}
```

### Team-Based Filtering

```php
class DealService
{
    public function getDealsForTeam($teamId)
    {
        // Get team members
        $team = $this->findTeamById($teamId);
        
        if (!$team) {
            return [];
        }
        
        // Get deals assigned to team members
        // This would require getting user IDs for the team
        // and then filtering deals by those users
        
        return $this->getDealsForUsers($this->getTeamUserIds($teamId));
    }
    
    private function findTeamById($teamId)
    {
        $teams = Teamleader::teams()->list(['ids' => [$teamId]]);
        return $teams['data'][0] ?? null;
    }
}
```

### Team Leadership Report

```php
class TeamLeadershipReport
{
    public function generate()
    {
        $teams = Teamleader::teams()->list();
        $report = [];
        
        foreach ($teams['data'] as $team) {
            $leaderId = $team['team_lead']['id'];
            
            if (!isset($report[$leaderId])) {
                $leader = Teamleader::users()->info($leaderId);
                $report[$leaderId] = [
                    'leader_name' => $leader['data']['first_name'] . ' ' . $leader['data']['last_name'],
                    'teams' => []
                ];
            }
            
            $report[$leaderId]['teams'][] = $team['name'];
        }
        
        return $report;
    }
}
```

### Validate Team Assignment

```php
class TeamValidator
{
    public function isValidTeam($teamId): bool
    {
        $teams = Teamleader::teams()->list(['ids' => [$teamId]]);
        return !empty($teams['data']);
    }
    
    public function isUserTeamLead($userId): bool
    {
        $teams = Teamleader::teams()->list([
            'team_lead_id' => $userId
        ]);
        
        return !empty($teams['data']);
    }
    
    public function getTeamsByLeader($userId): array
    {
        $teams = Teamleader::teams()->list([
            'team_lead_id' => $userId
        ]);
        
        return $teams['data'];
    }
}
```

## Best Practices

### 1. Cache Team Data

Teams change infrequently, so cache them:

```php
// Good: Cache for 1 hour
$teams = Cache::remember('all_teams', 3600, function() {
    return Teamleader::teams()->list();
});

// Bad: Fetching on every request
$teams = Teamleader::teams()->list();
```

### 2. Use Search for Name Lookups

```php
// Good: Use search method
$teams = Teamleader::teams()->search('Sales');

// Less ideal: Manual filtering
$allTeams = Teamleader::teams()->list();
$filtered = array_filter($allTeams['data'], function($team) {
    return stripos($team['name'], 'Sales') !== false;
});
```

### 3. Sort Results for User-Facing Lists

```php
// Good: Sorted alphabetically
$teams = Teamleader::teams()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);

// Bad: Unsorted (inconsistent order)
$teams = Teamleader::teams()->list();
```

### 4. Handle Empty Results

```php
$teams = Teamleader::teams()->search('NonExistentTeam');

if (empty($teams['data'])) {
    // No teams found
    return response()->json([
        'message' => 'No teams found matching your search'
    ], 404);
}
```

### 5. Combine with User Data

When displaying team information, combine with user data:

```php
$teams = Teamleader::teams()->list();

foreach ($teams['data'] as &$team) {
    $leader = Teamleader::users()->info($team['team_lead']['id']);
    $team['leader_name'] = $leader['data']['first_name'] . ' ' . $leader['data']['last_name'];
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $teams = Teamleader::teams()->list();
} catch (TeamleaderException $e) {
    Log::error('Error fetching teams', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Provide fallback
    return ['data' => []];
}
```

## Working with Team Members

**Note:** The Teams API endpoint only returns team information (name and leader). It does not include team members. To get team members, you would need to:

1. Get all users
2. Filter users based on their team assignment (if available in user data)
3. Or manage team membership in your local database

```php
// Example: Get users and group by team
class TeamMemberService
{
    public function getUsersByTeam()
    {
        $users = Teamleader::users()->active();
        $teams = Teamleader::teams()->list();
        
        $teamMembers = [];
        
        // Group users by team (if team info is available in user data)
        foreach ($users['data'] as $user) {
            if (isset($user['team']['id'])) {
                $teamId = $user['team']['id'];
                if (!isset($teamMembers[$teamId])) {
                    $teamMembers[$teamId] = [];
                }
                $teamMembers[$teamId][] = $user;
            }
        }
        
        return $teamMembers;
    }
}
```

## Limitations

1. **No info() method**: You cannot fetch individual team details. Use `list()` with the `ids` filter instead.
2. **No pagination**: All teams are returned in a single response.
3. **No team members**: The API does not return team member information.

```php
// To get a specific team, use list with ids filter
$team = Teamleader::teams()->list(['ids' => ['team-uuid']]);

if (!empty($team['data'])) {
    $teamData = $team['data'][0];
}
```

## Related Resources

- [Users](users.md) - Users can be team leaders
- [Departments](departments.md) - Another organizational structure

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
