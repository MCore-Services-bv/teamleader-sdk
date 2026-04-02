# External Parties

Manage external stakeholders on projects in Teamleader Focus.

## Overview

External parties are contacts or companies linked to a project with a functional role (e.g. contractor, client
representative). They are separate from the project's internal team.

Access via `Teamleader::external_parties()`.

> **SDK key uses underscore:** `external_parties` — not camelCase.
>
> **No standard `list()` method.** Use `addToProject()`, `update()`, and `delete()` to manage external parties.
>
> **`addToProject()` accepts two call signatures** — an array or individual parameters.

## Endpoint

`projects-v2/externalParties`

## Capabilities

| Capability  | Supported              |
|-------------|------------------------|
| Pagination  | ❌ Not supported        |
| Filtering   | ❌ Not supported        |
| Sorting     | ❌ Not supported        |
| Sideloading | ❌ Not supported        |
| Creation    | ✅ Via `addToProject()` |
| Update      | ✅ Supported            |
| Deletion    | ✅ Supported            |

---

## Methods

### `addToProject()`

Two calling conventions — array or individual params:

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Array form
$result = Teamleader::external_parties()->addToProject([
    'project_id' => 'project-uuid',
    'customer'   => ['type' => 'company', 'id' => 'company-uuid'],
    'function'   => 'Main Contractor',
    'sub_function' => 'Electrical',
]);

// Individual params: (projectId, type, customerId, function, subFunction)
$result = Teamleader::external_parties()->addToProject(
    'project-uuid',
    'contact',
    'contact-uuid',
    'Project Consultant',
    'Senior'  // optional sub_function
);
```

`customer.type` must be `contact` or `company`. `function` and `sub_function` are optional.

---

### `update(string $id, array $data)`

Injects `id` into the request body. Customer type is validated if provided.

```php
Teamleader::external_parties()->update('external-party-uuid', [
    'function'     => 'Lead Designer',
    'sub_function' => null,
]);

// Update the customer reference
Teamleader::external_parties()->update('external-party-uuid', [
    'customer' => ['type' => 'contact', 'id' => 'new-contact-uuid'],
]);
```

---

### `delete(string $id)`

Throws if `$id` is empty.

```php
Teamleader::external_parties()->delete('external-party-uuid');
```

---

## Helper Methods

### `removeFromProject(string $id)`

Alias for `delete()`.

```php
Teamleader::external_parties()->removeFromProject('external-party-uuid');
```

### `updateRole(string $id, ?string $function, ?string $subFunction)`

Updates only the role fields.

```php
Teamleader::external_parties()->updateRole('external-party-uuid', 'Technical Lead', null);
```

---

## Usage Examples

```php
// Add a company as contractor
Teamleader::external_parties()->addToProject(
    'project-uuid', 'company', 'company-uuid', 'Sub-contractor'
);

// Later update their role
Teamleader::external_parties()->updateRole('external-party-uuid', 'Prime Contractor');

// Remove when no longer involved
Teamleader::external_parties()->removeFromProject('external-party-uuid');
```

---

## Error Handling

```php
use InvalidArgumentException;

// Missing required params in individual-param form
try {
    Teamleader::external_parties()->addToProject('project-uuid'); // missing type and id
} catch (InvalidArgumentException $e) {
    // 'When using individual parameters, projectId, customerType, and customerId are required'
}

// Invalid customer type
try {
    Teamleader::external_parties()->addToProject('project-uuid', 'team', 'team-uuid', 'Lead');
} catch (InvalidArgumentException $e) {
    // 'Invalid customer type. Must be one of: contact, company'
}
```

---

## Related Resources

- [[Projects]] — Projects that external parties belong to
- [[Contacts]] — External party contacts
- [[Companies]] — External party companies
