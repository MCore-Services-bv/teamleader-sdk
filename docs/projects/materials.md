# Materials Resource

The Materials resource provides access to managing materials within projects in Teamleader Focus.

## Base Endpoint

```
projects-v2/materials
```

## Resource Capabilities

- ✅ Create materials
- ✅ Read material information
- ✅ Update materials
- ✅ Delete materials
- ✅ List materials with filtering
- ✅ Assign users/teams to materials
- ✅ Unassign users/teams from materials
- ✅ Duplicate materials
- ❌ Batch operations
- ❌ Pagination
- ❌ Sorting
- ❌ Sideloading

## Available Methods

### List Materials

List all materials that match the optional filters provided.

```php
$materials = $teamleader->materials()->list([
    'ids' => ['uuid1', 'uuid2', 'uuid3']
]);
```

**Filters:**
- `ids` - Array of material UUIDs

### Get Material Information

Get detailed information about a specific material.

```php
$material = $teamleader->materials()->info('material-uuid');
```

**Response includes:**
- Basic material info (id, title, description)
- Project and group references
- Status and billing information
- Pricing details (unit_price, fixed_price, cost, margin)
- Budgets (external, internal)
- Assigned users/teams
- Dates (start_date, end_date)
- Associated product

### Create Material

Create a new material in a project.

```php
$material = $teamleader->materials()->create([
    'project_id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
    'title' => 'WD-40 Multi-Use Product',
    'description' => 'Protects metal from rust and corrosion',
    'billing_method' => 'unit_price',
    'quantity' => 10,
    'unit_price' => [
        'amount' => 25.50,
        'currency' => 'EUR'
    ],
    'unit_cost' => [
        'amount' => 15.00,
        'currency' => 'EUR'
    ],
    'start_date' => '2023-01-18',
    'end_date' => '2023-03-22'
]);
```

**Required fields:**
- `project_id` - UUID of the project
- `title` - Material title

**Optional fields:**
- `group_id` - Group UUID (if omitted, material is not added to a group)
- `after_id` - Material UUID to place after (null = top, omitted = bottom)
- `description` - Material description
- `billing_method` - Billing method: `fixed_price`, `unit_price`, or `non_billable`
- `quantity` - Quantity (only if billing_method is `unit_price`)
- `unit_price` - Object with `amount` and `currency`
- `unit_cost` - Object with `amount` and `currency`
- `unit_id` - Unit of measure UUID
- `fixed_price` - Object with `amount` and `currency`
- `external_budget` - Object with `amount` and `currency`
- `internal_budget` - Object with `amount` and `currency`
- `start_date` - Start date (YYYY-MM-DD format)
- `end_date` - End date (YYYY-MM-DD format)
- `product_id` - Product UUID
- `assignees` - Array of assignee objects with `type` (user/team) and `id`

**Status values:**
- `to_do` - To do
- `in_progress` - In progress
- `on_hold` - On hold
- `done` - Done

**Billing methods:**
- `fixed_price` - Fixed price
- `unit_price` - Unit price (requires quantity)
- `non_billable` - Non-billable

### Update Material

Update an existing material. All fields except `id` are optional.

```php
$material = $teamleader->materials()->update('material-uuid', [
    'title' => 'Updated Material Title',
    'status' => 'in_progress',
    'quantity' => 15,
    'unit_price' => [
        'amount' => 28.00,
        'currency' => 'EUR'
    ]
]);
```

**Note:** Providing `null` for nullable fields will clear that value from the material.

### Delete Material

Delete a material from a project.

```php
$result = $teamleader->materials()->delete('material-uuid');
```

**Response:** 204 No Content

### Assign User/Team to Material

Assign a user or a team to a material.

```php
// Assign user
$result = $teamleader->materials()->assign(
    'material-uuid',
    'user',
    'user-uuid'
);

// Assign team
$result = $teamleader->materials()->assign(
    'material-uuid',
    'team',
    'team-uuid'
);
```

**Parameters:**
- `$materialId` - Material UUID
- `$assigneeType` - Assignee type: `user` or `team`
- `$assigneeId` - Assignee UUID

**Response:** 204 No Content

### Unassign User/Team from Material

Unassign a user or a team from a material.

```php
// Unassign user
$result = $teamleader->materials()->unassign(
    'material-uuid',
    'user',
    'user-uuid'
);

// Unassign team
$result = $teamleader->materials()->unassign(
    'material-uuid',
    'team',
    'team-uuid'
);
```

**Parameters:**
- `$materialId` - Material UUID
- `$assigneeType` - Assignee type: `user` or `team`
- `$assigneeId` - Assignee UUID

**Response:** 204 No Content

### Duplicate Material

Create a copy of an existing material.

```php
$newMaterial = $teamleader->materials()->duplicate('origin-material-uuid');
```

**Response:** Returns the created material's data with `id` and `type`.

### Get Materials by IDs

Convenience method to get multiple specific materials.

```php
$materials = $teamleader->materials()->byIds([
    'uuid1',
    'uuid2',
    'uuid3'
]);
```

## Usage Examples

### Create a material with fixed price

```php
$material = $teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Project Management Services',
    'billing_method' => 'fixed_price',
    'fixed_price' => [
        'amount' => 5000.00,
        'currency' => 'EUR'
    ],
    'external_budget' => [
        'amount' => 5000.00,
        'currency' => 'EUR'
    ],
    'internal_budget' => [
        'amount' => 3000.00,
        'currency' => 'EUR'
    ]
]);
```

### Create a material with unit pricing

```php
$material = $teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Metal Studs',
    'description' => 'Industrial grade metal studs',
    'billing_method' => 'unit_price',
    'quantity' => 100,
    'unit_price' => [
        'amount' => 12.50,
        'currency' => 'EUR'
    ],
    'unit_cost' => [
        'amount' => 8.00,
        'currency' => 'EUR'
    ],
    'unit_id' => 'unit-uuid'
]);
```

### Create a non-billable material

```php
$material = $teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Internal Review',
    'billing_method' => 'non_billable',
    'description' => 'Internal quality assurance review'
]);
```

### Update material status and quantity

```php
$material = $teamleader->materials()->update('material-uuid', [
    'status' => 'done',
    'quantity' => 95 // Updated quantity used
]);
```

### Assign multiple team members to a material

```php
// Assign project manager
$teamleader->materials()->assign(
    'material-uuid',
    'user',
    'pm-user-uuid'
);

// Assign team
$teamleader->materials()->assign(
    'material-uuid',
    'team',
    'team-uuid'
);
```

### Complete workflow: Create, assign, update, and complete

```php
// Create material
$material = $teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Website Development',
    'billing_method' => 'fixed_price',
    'fixed_price' => [
        'amount' => 10000.00,
        'currency' => 'EUR'
    ],
    'status' => 'to_do',
    'start_date' => '2024-01-15',
    'end_date' => '2024-03-15'
]);

$materialId = $material['data']['id'];

// Assign developer
$teamleader->materials()->assign($materialId, 'user', 'developer-uuid');

// Start work
$teamleader->materials()->update($materialId, [
    'status' => 'in_progress'
]);

// Complete
$teamleader->materials()->update($materialId, [
    'status' => 'done'
]);
```

### Working with material groups

```php
// Create material in a specific group
$material = $teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Material in Group',
    'group_id' => 'group-uuid',
    'billing_method' => 'non_billable'
]);

// Move material to top of group
$teamleader->materials()->update('material-uuid', [
    'after_id' => null // null places at top
]);
```

## Response Structure

### Create Response

```json
{
  "data": {
    "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
    "type": "material"
  }
}
```

### Info Response

```json
{
  "data": {
    "id": "ff19a113-50ba-4afc-9fff-2e5c5c5a5485",
    "project": {
      "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
      "type": "nextgenProject"
    },
    "group": {
      "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
      "type": "nextgenProjectGroup"
    },
    "title": "WD-40® Multi-Use Product Industrial Size",
    "description": "Protects metal from rust and corrosion",
    "status": "in_progress",
    "billing_method": "unit_price",
    "billing_status": "not_billable",
    "quantity": 10,
    "unit_price": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "unit_cost": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "unit": {
      "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
      "type": "priceunit"
    },
    "amount_billed": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "external_budget": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "external_budget_spent": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "internal_budget": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "price": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "fixed_price": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "cost": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "margin": {
      "amount": 123.3,
      "currency": "EUR"
    },
    "margin_percentage": 15.5,
    "assignees": [
      {
        "assignee": {
          "type": "user",
          "id": "66abace2-62af-0836-a927-fe3f44b9b47b"
        },
        "assign_type": "manual"
      }
    ],
    "start_date": "2023-01-18",
    "end_date": "2023-03-22",
    "product": {
      "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
      "type": "product"
    }
  }
}
```

### List Response

```json
{
  "data": [
    {
      "id": "39c64ba9-ebf1-491a-8486-a0b96ff21c07",
      "project": {
        "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
        "type": "nextgenProject"
      },
      "title": "Metal studs",
      "status": "in_progress",
      "billing_method": "unit_price",
      "quantity": 100,
      "unit_price": {
        "amount": 123.3,
        "currency": "EUR"
      }
    }
  ]
}
```

## Validation Rules

### Create Validation

- `project_id` is required
- `title` is required
- `billing_method` must be one of: `fixed_price`, `unit_price`, `non_billable`
- `quantity` can only be provided when `billing_method` is `unit_price`
- `status` must be one of: `to_do`, `in_progress`, `on_hold`, `done`
- Monetary fields must contain both `amount` and `currency`

### Update Validation

- `id` is required
- Same validation rules as create for optional fields
- Providing `null` for nullable fields clears the value

### Assign/Unassign Validation

- Assignee `type` must be either `user` or `team`
- Assignee `id` is required

## Error Handling

```php
use InvalidArgumentException;

try {
    $material = $teamleader->materials()->create([
        'project_id' => 'project-uuid',
        'title' => 'Test Material',
        'billing_method' => 'invalid_method' // Invalid
    ]);
} catch (InvalidArgumentException $e) {
    // Handle validation error
    echo $e->getMessage();
}
```

## Best Practices

1. **Always provide project_id and title** when creating materials
2. **Match billing_method with appropriate pricing fields** (unit_price for unit_price method, fixed_price for fixed_price method)
3. **Include budgets** for better project tracking (external_budget, internal_budget)
4. **Set realistic start and end dates** for materials that span time
5. **Use assignees** to track responsibility
6. **Link to products** when materials are based on catalog items
7. **Update status** as work progresses (to_do → in_progress → done)
8. **Use groups** to organize related materials within a project
9. **Track costs** with unit_cost to calculate margins automatically

## Related Resources

- Projects - Parent resource for materials
- Products - Can be linked to materials
- Users - Can be assigned to materials
- Teams - Can be assigned to materials
- Units of Measure - Used for unit pricing
