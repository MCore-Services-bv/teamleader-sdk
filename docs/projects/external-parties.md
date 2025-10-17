# External Parties

Manage external parties on projects in Teamleader Focus.

## Overview

External parties represent stakeholders on a project who are not part of your organization (e.g., contractors, consultants, client representatives). They can be contacts or companies with defined roles.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
- [Usage Examples](#usage-examples)
- [Related Resources](#related-resources)

## Endpoint

`projects-v2/externalParties`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported (via addToProject)
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `addToProject()`

Add an external party to a project.

**Can be called two ways:**

```php
// Method 1: Individual parameters
Teamleader::external_parties()->addToProject(
    'project-uuid',           // Project ID
    'contact',                // Customer type: 'contact' or 'company'
    'contact-uuid',           // Customer ID
    'Project Manager',        // Function
    'Senior PM'               // Sub-function (optional)
);

// Method 2: Data array
Teamleader::external_parties()->addToProject([
    'project_id' => 'project-uuid',
    'customer' => [
        'type' => 'contact',
        'id' => 'contact-uuid'
    ],
    'function' => 'Project Manager',
    'sub_function' => 'Senior PM'
]);
```

### `update()`

Update an existing external party.

```php
Teamleader::external_parties()->update('external-party-uuid', [
    'customer' => [
        'type' => 'contact',
        'id' => 'contact-uuid'
    ],
    'function' => 'Lead Designer',
    'sub_function' => null
]);
```

### `delete()`

Remove an external party from a project.

```php
Teamleader::external_parties()->delete('external-party-uuid');
```

## Usage Examples

### Add Contact as External Party

```php
$result = Teamleader::external_parties()->addToProject(
    'project-uuid',
    'contact',
    'contact-uuid',
    'Technical Consultant'
);
```

### Add Company as External Party

```php
$result = Teamleader::external_parties()->addToProject(
    'project-uuid',
    'company',
    'contractor-company-uuid',
    'Contractor',
    'Lead Contractor'
);
```

### Add Multiple External Parties

```php
$projectId = 'project-uuid';

$parties = [
    ['type' => 'contact', 'id' => 'contact-1', 'function' => 'Project Manager'],
    ['type' => 'company', 'id' => 'company-1', 'function' => 'Contractor'],
    ['type' => 'contact', 'id' => 'contact-2', 'function' => 'Designer'],
];

foreach ($parties as $party) {
    Teamleader::external_parties()->addToProject([
        'project_id' => $projectId,
        'customer' => [
            'type' => $party['type'],
            'id' => $party['id']
        ],
        'function' => $party['function']
    ]);
    
    echo "Added: {$party['function']}\n";
}
```

### Update External Party Role

```php
$externalPartyId = 'external-party-uuid';

// Change role
Teamleader::external_parties()->update($externalPartyId, [
    'function' => 'Senior Consultant',
    'sub_function' => 'Technical Lead'
]);
```

### Replace External Party

```php
$oldPartyId = 'old-party-uuid';
$projectId = 'project-uuid';

// Remove old party
Teamleader::external_parties()->delete($oldPartyId);

// Add new party
Teamleader::external_parties()->addToProject(
    $projectId,
    'contact',
    'new-contact-uuid',
    'Project Manager'
);
```

## Best Practices

1. **Use Descriptive Functions**: Make roles clear
```php
// Good
'function' => 'Technical Consultant'
'sub_function' => 'Backend Specialist'

// Avoid
'function' => 'Person'
```

2. **Validate Customer Type**: Only 'contact' or 'company'
```php
$validTypes = ['contact', 'company'];
if (!in_array($customerType, $validTypes)) {
    throw new Exception('Invalid customer type');
}
```

3. **Track External Party IDs**: Store the returned ID for updates/deletes
```php
$result = Teamleader::external_parties()->addToProject(...);
$externalPartyId = $result['data']['id'];
// Store this for later operations
```

## Related Resources

- **[Projects](projects.md)** - Parent projects
- **[Contacts](../crm/contacts.md)** - Contact information
- **[Companies](../crm/companies.md)** - Company information
