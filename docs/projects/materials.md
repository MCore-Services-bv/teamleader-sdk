# Materials

Manage materials in Teamleader Focus projects (New Projects API).

## Endpoint

`projects-v2/materials`

## Capabilities

| Feature | Supported |
|---------|-----------|
| Pagination | ❌ Not Supported |
| Filtering | ✅ Supported (by `ids` only) |
| Sorting | ❌ Not Supported |
| Sideloading | ❌ Not Supported |
| Creation | ✅ Supported |
| Update | ✅ Supported |
| Deletion | ❌ Not Supported |

---

## Methods

### `list(array $filters = []): array`

Lists materials matching the optional filter. Returns HTTP 200 with an array of material objects.

**Filter parameters:**

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | string[] | Specific material UUIDs to retrieve |

**Example:**

```php
// All materials (no filter)
$all = $teamleader->materials()->list();

// Specific materials by UUID
$materials = $teamleader->materials()->list([
    'ids' => ['uuid-1', 'uuid-2'],
]);
```

---

### `info(string $id): array`

Returns all information for one material. Response structure is identical to list items.

**Example:**

```php
$material = $teamleader->materials()->info('ff19a113-50ba-4afc-9fff-2e5c5c5a5485');

echo $material['data']['title'];
echo $material['data']['quantity'];           // actual quantity used
echo $material['data']['quantity_estimated']; // estimated quantity
echo $material['data']['billing_status'];
```

---

### `create(array $data): array`

Creates a new material on a project. Returns HTTP 201 with `data.{id, type}`.

**Required fields:**

| Field | Type | Description |
|-------|------|-------------|
| `project_id` | string | Project UUID |
| `title` | string | Material title |

**Optional fields:**

| Field | Type | Description |
|-------|------|-------------|
| `group_id` | string | Group UUID — omit to leave ungrouped |
| `after_id` | string\|null | UUID to place after; `null` = top; omit = bottom |
| `description` | string | Free-text description |
| `billing_method` | string | `fixed_price`, `unit_price`, or `non_billable` |
| `quantity` | number | Actual quantity used |
| `quantity_estimated` | number | Estimated quantity |
| `unit_price` | object\|null | `{amount, currency}` |
| `unit_cost` | object\|null | `{amount, currency}` |
| `unit_id` | string | Price unit UUID |
| `fixed_price` | object\|null | `{amount, currency}` — for `fixed_price` billing |
| `external_budget` | object\|null | `{amount, currency}` |
| `internal_budget` | object\|null | `{amount, currency}` |
| `start_date` | string | YYYY-MM-DD |
| `end_date` | string | YYYY-MM-DD |
| `product_id` | string | Product UUID to couple to this material |
| `assignees` | array | `[{type: team\|user, id: uuid}]` |

**Example:**

```php
$result = $teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Metal studs',
    'billing_method' => 'unit_price',
    'quantity_estimated' => 50,
    'unit_price' => ['amount' => 4.50, 'currency' => 'EUR'],
    'unit_cost' => ['amount' => 2.00, 'currency' => 'EUR'],
]);

$materialId = $result['data']['id'];
```

---

### `update(string $id, array $data): array`

Updates an existing material. Only `id` is required; all other fields are optional. Returns HTTP 204 (no body).

Providing `null` for a nullable field will **clear** that value.

**Updatable fields:**

| Field | Type | Description |
|-------|------|-------------|
| `title` | string | Material title |
| `description` | string\|null | Description (null to clear) |
| `status` | string | `to_do`, `in_progress`, `on_hold`, or `done` |
| `billing_method` | string | `fixed_price`, `unit_price`, or `non_billable` |
| `quantity` | number\|null | Actual quantity used |
| `quantity_estimated` | number\|null | Estimated quantity |
| `unit_price` | object\|null | `{amount, currency}` |
| `unit_cost` | object\|null | `{amount, currency}` |
| `unit_id` | string\|null | Price unit UUID |
| `fixed_price` | object\|null | `{amount, currency}` |
| `external_budget` | object\|null | `{amount, currency}` |
| `internal_budget` | object\|null | `{amount, currency}` |
| `start_date` | string\|null | YYYY-MM-DD |
| `end_date` | string\|null | YYYY-MM-DD |
| `product_id` | string\|null | Product UUID |

**Example:**

```php
// Update status and quantity tracking
$teamleader->materials()->update('material-uuid', [
    'status' => 'in_progress',
    'quantity' => 8,
    'quantity_estimated' => 10,
]);

// Clear the product link
$teamleader->materials()->update('material-uuid', [
    'product_id' => null,
]);
```

---

## Response Structure

Both `list()` and `info()` return the same fields per material:

```json
{
  "data": {
    "id": "ff19a113-50ba-4afc-9fff-2e5c5c5a5485",
    "project": { "id": "...", "type": "nextgenProject" },
    "group": null,
    "title": "Metal studs",
    "description": null,
    "status": "in_progress",
    "billing_method": "unit_price",
    "billing_status": "not_billed",
    "quantity": 8,
    "quantity_estimated": 10,
    "unit_price": { "amount": 4.50, "currency": "EUR" },
    "unit_cost": { "amount": 2.00, "currency": "EUR" },
    "unit": null,
    "amount_billed": null,
    "external_budget": null,
    "external_budget_spent": null,
    "internal_budget": null,
    "price": { "amount": 36.00, "currency": "EUR" },
    "fixed_price": null,
    "cost": { "amount": 16.00, "currency": "EUR" },
    "margin": { "amount": 20.00, "currency": "EUR" },
    "margin_percentage": 55.6,
    "assignees": [
      {
        "assignee": { "type": "user", "id": "user-uuid" },
        "assign_type": "manual"
      }
    ],
    "start_date": "2024-01-15",
    "end_date": "2024-03-31",
    "product": null
  }
}
```

### `quantity` vs `quantity_estimated`

Both are nullable numbers. They serve different purposes:

| Field | Description | When to set |
|-------|-------------|-------------|
| `quantity` | Actual quantity used | Updated as work progresses |
| `quantity_estimated` | Planned/estimated quantity | Set during project planning |

Both fields are available on `create()`, `update()`, `list()`, and `info()`.

### `billing_status` (read-only)

| Value | Description |
|-------|-------------|
| `not_billable` | `billing_method` is `non_billable` |
| `not_billed` | Billable but not yet invoiced |
| `partially_billed` | Some quantity has been invoiced |
| `fully_billed` | All quantity has been invoiced |

---

## Usage Examples

### Track Estimated vs Actual Quantity

```php
// Create with planning estimate
$result = $teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Copper pipe (meters)',
    'billing_method' => 'unit_price',
    'quantity_estimated' => 25,
    'unit_price' => ['amount' => 8.50, 'currency' => 'EUR'],
]);

$materialId = $result['data']['id'];

// Update with actual usage when work is done
$teamleader->materials()->update($materialId, [
    'quantity' => 22,
    'status' => 'done',
]);

// Read back both values
$info = $teamleader->materials()->info($materialId);
$estimated = $info['data']['quantity_estimated']; // 25
$actual    = $info['data']['quantity'];            // 22
```

### Create Material with Fixed Price

```php
$teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'SSL Certificate',
    'billing_method' => 'fixed_price',
    'fixed_price' => ['amount' => 150.00, 'currency' => 'EUR'],
]);
```

### Create Material with Unit Price

```php
$teamleader->materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Server Storage (GB)',
    'billing_method' => 'unit_price',
    'quantity_estimated' => 100,
    'unit_price' => ['amount' => 10.00, 'currency' => 'EUR'],
    'unit_cost' => ['amount' => 5.00, 'currency' => 'EUR'],
]);
```

### Filter Materials by ID

```php
$materials = $teamleader->materials()->byIds(['uuid-1', 'uuid-2', 'uuid-3']);
```

---

## Best Practices

1. **Set `quantity_estimated` at creation** — capture the planned amount before work begins so you can compare against actual usage later
2. **Update `quantity` as work progresses** — keep actual usage current for accurate billing status
3. **Always specify `billing_method`** — determines which price field is used for invoicing
4. **Use `product_id`** — coupling a product from your catalogue keeps pricing consistent

---

## Related Resources

- **[Projects](projects.md)** — Materials belong to a project (`project_id`)
- **[Groups](groups.md)** — Organise materials into groups (`group_id`)
- **[Products](products.md)** — Couple materials to catalogue products (`product_id`)
- **[Invoices](../invoicing/invoices.md)** — Billed materials affect `billing_status`
