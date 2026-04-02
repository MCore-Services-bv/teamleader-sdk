# Resources

How the Teamleader SDK resource architecture works.

## Overview

Every entity in the SDK — companies, deals, invoices, etc. — is represented by a resource class. All resource classes
extend the base `Resource` class, which provides standard CRUD methods, filtering, sorting, pagination, and sideloading
through `FilterTrait`.

Resources are accessed via the `Teamleader` facade:

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

Teamleader::companies()   // Companies resource
Teamleader::deals()       // Deals resource
Teamleader::invoices()    // Invoices resource
```

---

## Standard Methods

Every resource inherits these methods from the base `Resource` class. Individual resources may override them to add
validation or custom behaviour.

### `list(array $filters = [], array $options = [])`

Retrieve a list of records. Filters go in the first argument, options (sort, pagination, includes) in the second.

```php
$companies = Teamleader::companies()->list(
    ['status' => 'active'],
    ['page_size' => 50, 'sort' => 'name']
);
```

### `info(mixed $id, mixed $includes = null)`

Retrieve a single record by UUID. The optional second argument accepts a comma-separated string or array of sideloaded
relationships.

```php
$company = Teamleader::companies()->info('company-uuid');
$company = Teamleader::companies()->info('company-uuid', 'custom_fields,responsible_user');
```

### `create(array $data)`

Create a new record.

```php
$company = Teamleader::companies()->create([
    'name' => 'Acme Corp',
]);
```

### `update(mixed $id, array $data)`

Update an existing record.

```php
Teamleader::companies()->update('company-uuid', [
    'name' => 'Acme Corp Ltd',
]);
```

### `delete(mixed $id, ...$additionalParams)`

Delete a record.

```php
Teamleader::companies()->delete('company-uuid');
```

---

## Fluent Interface

Resources support a fluent `with()` method for sideloading, which can be chained before any method call.

```php
$company = Teamleader::companies()
    ->with('custom_fields')
    ->with('responsible_user')
    ->info('company-uuid');

$companies = Teamleader::companies()
    ->with('custom_fields,price_list')
    ->list(['status' => 'active']);
```

---

## Capabilities

Each resource declares which operations it supports via boolean flags. Not every resource supports every method.

```php
// Inspect a resource's capabilities at runtime
$capabilities = Teamleader::companies()->getCapabilities();

// Returns:
[
    'supports_pagination'  => true,
    'supports_filtering'   => true,
    'supports_sorting'     => true,
    'supports_sideloading' => true,
    'supports_creation'    => true,
    'supports_update'      => true,
    'supports_deletion'    => true,
    'supports_batch'       => false,
    'default_includes'     => [],
    'available_includes'   => ['custom_fields', 'responsible_user', ...],
    'endpoint'             => 'companies',
]
```

Calling a method on a resource that does not support it will throw a `BadMethodCallException`.

---

## Resource Categories

### CRM

| Resource       | Facade method     | Notes                        |
|----------------|-------------------|------------------------------|
| Companies      | `companies()`     | Full CRUD + `uploadLogo()`   |
| Contacts       | `contacts()`      | Full CRUD + `uploadAvatar()` |
| Business Types | `businessTypes()` | Read-only                    |
| Tags           | `tags()`          | Read-only                    |
| Addresses      | `addresses()`     | Read-only                    |

### Deals

| Resource     | Facade method   | Notes     |
|--------------|-----------------|-----------|
| Deals        | `deals()`       | Full CRUD |
| Quotations   | `quotations()`  | Full CRUD |
| Orders       | `orders()`      | Read-only |
| Lost Reasons | `lostReasons()` | Read-only |
| Sources      | `sources()`     | Read-only |

### Invoicing

| Resource                | Facade method              | Notes               |
|-------------------------|----------------------------|---------------------|
| Invoices                | `invoices()`               | Full CRUD + actions |
| Credit Notes            | `creditNotes()`            | Full CRUD           |
| Payment Methods         | `paymentMethods()`         | Read-only           |
| Payment Terms           | `paymentTerms()`           | Read-only           |
| Tax Rates               | `taxRates()`               | Read-only           |
| Withholding Tax Rates   | `withholdingTaxRates()`    | Read-only           |
| Commercial Discounts    | `commercialDiscounts()`    | Read-only           |
| Subscriptions           | `subscriptions()`          | Full CRUD           |
| Bookkeeping Submissions | `bookkeepingSubmissions()` | Read-only           |
| Incoming Invoices       | `incomingInvoices()`       | Full CRUD           |
| Incoming Credit Notes   | `incomingCreditNotes()`    | Full CRUD           |
| Receipts                | `receipts()`               | Full CRUD           |

### Projects

| Resource      | Facade method    | Notes     |
|---------------|------------------|-----------|
| Projects      | `projects()`     | Full CRUD |
| Project Lines | `projectLines()` | Full CRUD |
| Milestones    | `milestones()`   | Full CRUD |

### Planning

| Resource        | Facade method      | Notes     |
|-----------------|--------------------|-----------|
| Planning Events | `planningEvents()` | Full CRUD |
| Plannable Items | `plannableItems()` | Read-only |

### Products

| Resource           | Facade method         | Notes     |
|--------------------|-----------------------|-----------|
| Products           | `products()`          | Full CRUD |
| Product Categories | `productCategories()` | Read-only |

### Time Tracking

| Resource      | Facade method    | Notes           |
|---------------|------------------|-----------------|
| Time Tracking | `timeTracking()` | Full CRUD       |
| Timers        | `timers()`       | Start/stop/info |

### Expenses

| Resource | Facade method | Notes     |
|----------|---------------|-----------|
| Expenses | `expenses()`  | Full CRUD |

### General

| Resource           | Facade method         | Notes           |
|--------------------|-----------------------|-----------------|
| Users              | `users()`             | Read-only       |
| Departments        | `departments()`       | Read-only       |
| Teams              | `teams()`             | Read-only       |
| Work Types         | `workTypes()`         | Read-only       |
| Custom Fields      | `customFields()`      | Read-only       |
| Files              | `files()`             | Upload/download |
| Notes              | `notes()`             | Full CRUD       |
| Webhooks           | `webhooks()`          | Full CRUD       |
| Email Tracking     | `emailTracking()`     | Read-only       |
| Document Templates | `documentTemplates()` | Read-only       |
| Activity Types     | `activityTypes()`     | Read-only       |

---

## Introspection

Every resource exposes documentation helpers:

```php
// Full documentation array
$docs = Teamleader::companies()->getDocumentation();

// Markdown string
$markdown = Teamleader::companies()->generateMarkdownDocs();

// Capabilities only
$caps = Teamleader::companies()->getCapabilities();
```

---

## Related Resources

- [[Filtering]] — Filters, sorting, pagination
- [[Sideloading]] — Loading related data
- [[Usage]] — Getting started and authentication
- [[Errors]] — Exception reference
