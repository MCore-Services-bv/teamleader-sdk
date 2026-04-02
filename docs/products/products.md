# Products

Manage products in Teamleader Focus.

## Overview

The Products resource provides full CRUD for managing the product catalogue. Products can be physical goods or services,
and carry pricing, stock, category, tax rate, and custom field data.

Access via `Teamleader::products()`.

> **`create()` posts to `products.add`** — not `.create`.
>
> **`update()` injects `id` into the request body.**
>
> **Includes key is `includes` (plural)** in both `info()` and `list()`.
>
> **No sorting supported** — the API does not mention sort parameters for products.

## Endpoint

`products`

## Capabilities

| Capability  | Supported                                  |
|-------------|--------------------------------------------|
| Pagination  | ✅ Supported                                |
| Filtering   | ✅ Supported                                |
| Sorting     | ❌ Not supported                            |
| Sideloading | ✅ Supported (`suppliers`, `custom_fields`) |
| Creation    | ✅ Supported                                |
| Update      | ✅ Supported                                |
| Deletion    | ✅ Supported                                |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$products = Teamleader::products()->list();

$products = Teamleader::products()->list(
    ['term' => 'laptop'],
    ['page_size' => 50, 'page_number' => 1]
);

// With sideloading via options
$products = Teamleader::products()->list([], ['include' => 'suppliers,custom_fields']);

// With fluent interface
$products = Teamleader::products()
    ->withSuppliers()
    ->withCustomFields()
    ->list();
```

---

### `info(mixed $id, mixed $includes = null)`

```php
$product = Teamleader::products()->info('product-uuid');

$product = Teamleader::products()->info('product-uuid', 'suppliers');
$product = Teamleader::products()->info('product-uuid', ['suppliers', 'custom_fields']);

// Fluent interface
$product = Teamleader::products()
    ->withSuppliers()
    ->info('product-uuid');
```

---

### `create(array $data)`

Posts to `products.add`. At least one of `name` or `code` is required — throws `InvalidArgumentException` if both are
absent.

```php
$product = Teamleader::products()->create([
    'name'                => 'Dark Chocolate Cookies',
    'code'                => 'COOK-DARK-001',
    'description'         => 'Premium dark chocolate cookies, 200g bag',
    'unit_of_measure_id'  => 'unit-uuid',
    'product_category_id' => 'category-uuid',
    'tax_rate_id'         => 'tax-rate-uuid',
    'selling_price'       => ['amount' => 4.99, 'currency' => 'EUR'],
    'purchase_price'      => ['amount' => 2.50, 'currency' => 'EUR'],
    'stock'               => ['amount' => 500],
    'department_id'       => 'dept-uuid',
]);
```

---

### `update(mixed $id, array $data)`

The `id` is injected into the request body.

```php
Teamleader::products()->update('product-uuid', [
    'selling_price' => ['amount' => 5.49, 'currency' => 'EUR'],
    'stock'         => ['amount' => 450],
]);
```

---

### `delete(mixed $id)`

```php
Teamleader::products()->delete('product-uuid');
```

---

## Helper Methods

### `search(string $term)`

Passes `term` filter to `list()`.

```php
$products = Teamleader::products()->search('chocolate');
```

### `updatedSince(string $date)`

Passes `updated_since` filter to `list()`.

```php
$products = Teamleader::products()->updatedSince('2025-01-01T00:00:00+02:00');
```

### Fluent include methods

| Method               | Include         |
|----------------------|-----------------|
| `withSuppliers()`    | `suppliers`     |
| `withCustomFields()` | `custom_fields` |

```php
$products = Teamleader::products()->withSuppliers()->withCustomFields()->list();
```

---

## Filters

| Filter          | Type   | Description             |
|-----------------|--------|-------------------------|
| `ids`           | array  | Filter by product UUIDs |
| `term`          | string | Search on name or code  |
| `updated_since` | string | ISO 8601 datetime       |

---

## Sideloading

| Include         | Description                          |
|-----------------|--------------------------------------|
| `suppliers`     | Supplier references for this product |
| `custom_fields` | Custom field values                  |

---

## Usage Examples

### Create a product and use it on an invoice

```php
// Look up required references first
$unit     = Teamleader::unitsOfMeasure()->findByName('piece');
$category = Teamleader::productCategories()->forDepartment('dept-uuid');
$taxRate  = Teamleader::taxRates()->findByRate(0.21, 'dept-uuid');

$product = Teamleader::products()->create([
    'name'                => 'Consulting Hour',
    'code'                => 'CONS-HR-001',
    'unit_of_measure_id'  => $unit['id'],
    'product_category_id' => $category['data'][0]['id'],
    'tax_rate_id'         => $taxRate['id'],
    'selling_price'       => ['amount' => 150.0, 'currency' => 'EUR'],
]);

// Use on an invoice line item
$productId = $product['data']['id'];
```

### Search and cache

```php
$products = Cache::remember('tl_products', 3600, fn() => Teamleader::products()->list());

// Search locally once cached
$filtered = array_filter($products['data'], fn($p) => str_contains($p['name'], 'Cookie'));
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing name and code on create
try {
    Teamleader::products()->create(['description' => 'Test']);
} catch (InvalidArgumentException $e) {
    // 'Either name or code is required when creating a product'
}
```

---

## Related Resources

- [[Product-Categories]] — Category assignment for products
- [[Units-of-Measure]] — `unit_of_measure_id` for products
- [[Tax-Rates]] — `tax_rate_id` for products
- [[Price-Lists]] — Price lists that may apply to products
- [[Invoices]] — Products used on invoice line items
- [[Quotations]] — Products used on quotation line items
