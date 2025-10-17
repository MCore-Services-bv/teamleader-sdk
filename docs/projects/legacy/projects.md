# Legacy Projects

Manage legacy projects in Teamleader Focus (Old Projects API).

## Overview

The Legacy Projects resource manages projects using the old Teamleader Projects API. These projects use milestones instead of tasks/materials/groups.

**Note:** This is the legacy API. For new projects, use the [New Projects API](../projects.md). Legacy projects are maintained for backward compatibility.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Usage Examples](#usage-examples)
- [Related Resources](#related-resources)

## Endpoint

`projects` (not `projects-v2`)

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`, `info()`, `create()`, `update()`, `delete()`

Standard CRUD operations similar to new projects, but with different structure.

### `close()`

Close a project (also closes all phases and tasks).

```php
Teamleader::legacyProjects()->close('project-uuid');
```

### `reopen()`

Reopen a closed project.

```php
Teamleader::legacyProjects()->reopen('project-uuid');
```

### `addParticipant()`

Add a participant to a legacy project.

```php
Teamleader::legacyProjects()->addParticipant('project-uuid', [
    'participant' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ],
    'role' => 'decision_maker'  // or 'member'
]);
```

### `updateParticipant()`

Update a participant's role.

```php
Teamleader::legacyProjects()->updateParticipant(
    'project-uuid',
    ['type' => 'user', 'id' => 'user-uuid'],
    'member'
);
```

## Helper Methods

```php
// Get by status
$active = Teamleader::legacyProjects()->active();
$onHold = Teamleader::legacyProjects()->onHold();
$done = Teamleader::legacyProjects()->done();
$cancelled = Teamleader::legacyProjects()->cancelled();

// Get for customer
$projects = Teamleader::legacyProjects()->forCustomer('company-uuid', 'company');

// Get for participant
$projects = Teamleader::legacyProjects()->forParticipant('user-uuid');

// Search
$projects = Teamleader::legacyProjects()->search('website');

// Updated since
$projects = Teamleader::legacyProjects()->updatedSince('2024-01-01T00:00:00+00:00');
```

## Filtering

- `customer` - Object with type and id
- `status` - active, on_hold, done, cancelled
- `participant_id` - User UUID
- `term` - Search term
- `updated_since` - ISO 8601 datetime

## Sorting

- `due_on`
- `title`
- `created_at`

## Usage Examples

### Create Legacy Project

**Required fields:**
- `title`
- `starts_on`
- `milestones` (at least one)
- `participants` (at least one decision maker)

```php
$project = Teamleader::legacyProjects()->create([
    'title' => 'Website Redesign',
    'starts_on' => '2024-01-01',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'milestones' => [
        [
            'name' => 'Phase 1',
            'due_on' => '2024-03-31',
            'responsible_user_id' => 'user-uuid',
            'billing_method' => 'time_and_materials'
        ]
    ],
    'participants' => [
        [
            'participant' => ['type' => 'user', 'id' => 'user-uuid'],
            'role' => 'decision_maker'
        ]
    ]
]);
```

### Complete Project Lifecycle

```php
$projectId = 'project-uuid';

// Update status
Teamleader::legacyProjects()->update($projectId, [
    'status' => 'on_hold'
]);

// Resume
Teamleader::legacyProjects()->update($projectId, [
    'status' => 'active'
]);

// Complete
Teamleader::legacyProjects()->close($projectId);

// Reopen if needed
Teamleader::legacyProjects()->reopen($projectId);
```

## Best Practices

1. **Use New Projects API for new work**: Legacy API is for maintenance only
2. **Migrate when possible**: Consider migrating to new projects
3. **Work with milestones**: See [Legacy Milestones](milestones.md)

## Related Resources

- **[Legacy Milestones](milestones.md)** - Manage project milestones
- **[New Projects](../projects.md)** - Modern projects API
- **[Companies](../../crm/companies.md)** - Project customers
