# External Parties (Projects)

Manage external parties on projects in Teamleader Focus. External parties are contacts or companies that participate in a project with specific roles and functions.

## Namespace

```php
McoreServices\TeamleaderSDK\Resources\Projects\ExternalParties
```

## Base Path

```
projects-v2/externalParties
```

## Resource Capabilities

- ✅ Add to Project (custom method)
- ✅ Update (custom method)
- ✅ Delete (custom method)
- ❌ List/Index
- ❌ Info/Show
- ❌ Batch Operations
- ❌ Pagination
- ❌ Filtering
- ❌ Sorting
- ❌ Sideloading

## Available Methods

### addToProject()

Add an external party (contact or company) to a project with a specific function/role.

**Method Signature:**
```php
public function addToProject(
    $projectIdOrData,
    ?string $customerType = null,
    ?string $customerId = null,
    ?string $function = null,
    ?string $subFunction = null
): array
```

**Parameters:**

- `$projectIdOrData` (string|array) - Project UUID or complete data array
- `$customerType` (string|null) - Customer type: 'contact' or 'company' (required if using individual params)
- `$customerId` (string|null) - Customer UUID (required if using individual params)
- `$function` (string|null) - Function or role description (optional)
- `$subFunction` (string|null) - Sub-function or additional role description (optional)

**Available Customer Types:**
- `contact`
- `company`

**Examples:**

```php
// Add a contact as Project Manager
$result = $teamleader->externalParties()->addToProject(
    '7257b535-d40f-4699-b3bd-63679379b579',
    'contact',
    'f29abf48-337d-44b4-aad4-585f5277a456',
    'Project Manager'
);

// Add a company as contractor with sub-function
$result = $teamleader->externalParties()->addToProject(
    '7257b535-d40f-4699-b3bd-63679379b579',
    'company',
    'c8f94e2a-1b3c-4d5e-8f9a-0b1c2d3e4f5a',
    'Contractor',
    'Lead Contractor'
);

// Using array structure for more flexibility
$result = $teamleader->externalParties()->addToProject([
    'project_id' => '7257b535-d40f-4699-b3bd-63679379b579',
    'customer' => [
        'type' => 'contact',
        'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
    ],
    'function' => 'Project Coordinator',
    'sub_function' => 'Senior Coordinator'
]);
```

**Response:**
Returns 204 No Content on success.

---

### update()

Update an external party's information.

**Method Signature:**
```php
public function update(string $id, array $data): array
```

**Parameters:**

- `$id` (string) - External party UUID
- `$data` (array) - Update data containing:
    - `customer` (object) - Customer information (optional)
        - `type` (string) - 'contact' or 'company'
        - `id` (string) - Customer UUID
    - `function` (string|null) - Function/role description (optional, nullable)
    - `sub_function` (string|null) - Sub-function description (optional, nullable)

**Examples:**

```php
// Update the function of an external party
$result = $teamleader->externalParties()->update(
    '6126596f-6193-445a-935a-60c10df9f632',
    [
        'customer' => [
            'type' => 'contact',
            'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
        ],
        'function' => 'Lead Designer',
        'sub_function' => null
    ]
);

// Update only the function, keeping the same customer
$result = $teamleader->externalParties()->update(
    '6126596f-6193-445a-935a-60c10df9f632',
    [
        'function' => 'Senior Project Manager',
        'sub_function' => 'Team Lead'
    ]
);
```

**Response:**
Returns 204 No Content on success.

---

### delete()

Delete an external party from a project.

**Method Signature:**
```php
public function delete(string $id, ...$additionalParams): array
```

**Parameters:**

- `$id` (string) - External party UUID

**Examples:**

```php
// Delete an external party
$result = $teamleader->externalParties()->delete('6126596f-6193-445a-935a-60c10df9f632');
```

**Response:**
Returns 204 No Content on success.

---

### removeFromProject()

Alias method for `delete()`. Removes an external party from a project.

**Method Signature:**
```php
public function removeFromProject(string $id): array
```

**Examples:**

```php
// More semantic alias for deletion
$result = $teamleader->externalParties()->removeFromProject('6126596f-6193-445a-935a-60c10df9f632');
```

---

### updateRole()

Convenience method to update only the function and sub_function of an external party.

**Method Signature:**
```php
public function updateRole(string $id, ?string $function = null, ?string $subFunction = null): array
```

**Parameters:**

- `$id` (string) - External party UUID
- `$function` (string|null) - Function/role description
- `$subFunction` (string|null) - Sub-function description

**Examples:**

```php
// Update just the role
$result = $teamleader->externalParties()->updateRole(
    '6126596f-6193-445a-935a-60c10df9f632',
    'Technical Lead'
);

// Update both function and sub-function
$result = $teamleader->externalParties()->updateRole(
    '6126596f-6193-445a-935a-60c10df9f632',
    'Architect',
    'Senior Solutions Architect'
);

// Clear the sub-function
$result = $teamleader->externalParties()->updateRole(
    '6126596f-6193-445a-935a-60c10df9f632',
    'Project Manager',
    null
);
```

---

## Usage Examples

### Basic Workflow

```php
// 1. Add a contact to a project
$addResult = $teamleader->externalParties()->addToProject(
    'project-uuid',
    'contact',
    'contact-uuid',
    'Project Manager'
);

// 2. Update their role
$updateResult = $teamleader->externalParties()->updateRole(
    'external-party-uuid',
    'Senior Project Manager',
    'Team Lead'
);

// 3. Remove them from the project
$deleteResult = $teamleader->externalParties()->removeFromProject('external-party-uuid');
```

### Adding Multiple External Parties

```php
$projectId = '7257b535-d40f-4699-b3bd-63679379b579';

// Add project manager
$teamleader->externalParties()->addToProject(
    $projectId,
    'contact',
    'contact-uuid-1',
    'Project Manager'
);

// Add contractor company
$teamleader->externalParties()->addToProject(
    $projectId,
    'company',
    'company-uuid-1',
    'General Contractor'
);

// Add architect
$teamleader->externalParties()->addToProject(
    $projectId,
    'contact',
    'contact-uuid-2',
    'Architect',
    'Lead Design Architect'
);
```

### Updating External Party Information

```php
$externalPartyId = '6126596f-6193-445a-935a-60c10df9f632';

// Change the customer and function
$teamleader->externalParties()->update($externalPartyId, [
    'customer' => [
        'type' => 'company',
        'id' => 'new-company-uuid'
    ],
    'function' => 'Subcontractor',
    'sub_function' => 'Electrical'
]);

// Or just update the role using the convenience method
$teamleader->externalParties()->updateRole(
    $externalPartyId,
    'Lead Subcontractor'
);
```

## Error Handling

```php
use InvalidArgumentException;

try {
    // This will throw an exception if customer type is invalid
    $result = $teamleader->externalParties()->addToProject(
        'project-uuid',
        'invalid-type', // Invalid customer type
        'customer-uuid'
    );
} catch (InvalidArgumentException $e) {
    echo 'Error: ' . $e->getMessage();
}

try {
    // This will throw an exception if required fields are missing
    $result = $teamleader->externalParties()->addToProject([
        'project_id' => 'project-uuid'
        // Missing customer information
    ]);
} catch (InvalidArgumentException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Notes

- All endpoints return **204 No Content** on success
- The `customer.type` field only accepts `'contact'` or `'company'`
- Both `function` and `sub_function` fields are optional and nullable
- The `project_id` must be a valid UUID from the Projects V2 API
- External party relationships are specific to individual projects
- When updating, you can change the customer, function, and sub_function independently
- Use the `updateRole()` convenience method when you only need to change functions

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/projects-v2/externalParties.addToProject` | Add an external party to a project |
| POST | `/projects-v2/externalParties.update` | Update an external party |
| POST | `/projects-v2/externalParties.delete` | Delete an external party |

## Related Resources

- [Projects](projects.md) - Manage projects (New Projects API v2)
- [Legacy Projects](legacy-projects.md) - Manage legacy projects
- [Companies](../crm/companies.md) - Company management
- [Contacts](../crm/contacts.md) - Contact management

## API Documentation

For more details, refer to the official Teamleader API documentation:
- [External Parties API](https://developer.teamleader.eu/projects-v2/externalParties)
