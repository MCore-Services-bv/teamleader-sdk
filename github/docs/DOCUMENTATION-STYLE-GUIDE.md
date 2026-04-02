# SDK Documentation Style Guide

A reference for anyone writing or reviewing wiki docs for `mcore-services/teamleader-sdk`.

---

## Core Principle

**The PHP source is the only source of truth.** Docs are derived from it, never the other way around. Before writing anything, search project knowledge for the actual class and verify every capability flag, method signature, filter key, and return value against the code.

Common things the old docs got wrong that you must always check:
- Capability flags (`supportsFiltering`, `supportsPagination`, etc.) marked wrong
- Methods listed that don't exist, or existing methods not listed
- Filter keys using wrong names or wrong value types
- Sort fields that are silently forced to something else
- Return values described as exceptions when they're error arrays, or vice versa
- `info()` overrides that throw instead of hitting the API

---

## File Naming & Format

| File | Purpose |
|---|---|
| `resource-name.md` | Source-controlled documentation |
| `resource-name.txt` | Wiki upload (identical content, `.txt` extension) |

Wiki cross-links use `[[Wiki-Page-Name]]` syntax with title-cased hyphenated names — e.g. `[[Deal-Phases]]`, `[[Closing-Days]]`.

---

## Document Structure

Every resource doc follows this order. Omit sections that genuinely don't apply (e.g. no Sideloading section if the resource has none), but never add sections that aren't grounded in source.

```
# Resource Name

One-sentence description.

## Overview

2–4 sentences. What this resource does. Any critical warnings at the top
(e.g. non-standard pagination, methods that throw, read-only constraints).
Bold any behaviour that would surprise a developer.

## Endpoint

`camelCaseName` — the key used in TeamleaderSDK::$resources and in all
API endpoint strings (e.g. `closingDays`, `dealPhases`).

## Capabilities

Markdown table. Always include all rows even if ❌.

| Capability | Supported |
|---|---|
| Pagination | ✅ / ❌ |
| Filtering | ✅ / ❌ (list filter keys in parens if ✅) |
| Sorting | ✅ / ❌ (list valid fields if ✅) |
| Sideloading | ✅ / ❌ |
| Creation | ✅ / ❌ |
| Update | ✅ / ❌ |
| Deletion | ✅ / ❌ |

Add a blockquote note for any non-obvious behaviour:
> **Note:** `status` filter must be an array — `['open']` not `'open'`.

---

## Methods

One `###` heading per method in the order they appear in the class.
Standard CRUD order: list → info → create → update → delete, then
resource-specific methods (win, lose, move, accept, send, etc.).

### `methodName(type $param, type $param = default)`

Brief description. Note any pre-request validation and what it throws.
Note if the method returns empty (HTTP 204).

```php
// Minimal example
Teamleader::resource()->methodName($param);

// With options
Teamleader::resource()->methodName($param, [
    'key' => 'value',
]);
```

---

## Helper Methods

### `helperName()`

One line description. Note if it's client-side (no API call) or makes
multiple API calls.

Table format works well for groups of similar helpers:

| Method | Filter / action |
|---|---|
| `active()` | `status: active` |
| `byEmail(string $email)` | `email → {type: primary, email: $email}` |

---

## Filters

Table listing every filter key the buildFilters() method handles.

| Filter | Type | Description |
|---|---|---|
| `ids` | array | Filter by UUIDs |
| `status` | array | `active` or `deactivated` — string coerced to array |

---

## Sorting  (omit section if ❌)

Table of valid sort fields. Note any silent coercion (e.g. Sources always
forces `name`/`asc` regardless of input).

---

## Sideloading  (omit section if ❌)

Table of available includes with descriptions. Note feature-gating where
relevant (e.g. Quotations `expiry` is feature-gated).

Fluent methods table if they exist:

| Method | Include added |
|---|---|
| `withCustomer()` | `lead.customer` |

---

## Response Structure

Show the shape of `list()` and `info()` responses as PHP arrays.
Include `meta` pagination shape for paginated resources.
Note which methods return empty (HTTP 204).
Only show fields that are always present — don't invent optional fields.

---

## Usage Examples

3–5 practical, copy-paste-ready examples. Prefer examples that show
combinations (filter + sideload, create + move, etc.) over single-method
demos. Examples should use realistic UUIDs like `'company-uuid'`,
not `'abc123'`.

---

## Error Handling

Show the actual exception types and messages thrown by the source.
Use try/catch blocks. Only show exceptions that the SDK itself throws —
not hypothetical API errors unless they're documented.

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    // ...
} catch (InvalidArgumentException $e) {
    // 'Exact message from source'
}
```

---

## Related Resources

Wiki links to directly related resources. Keep to resources that are
actually referenced in the source or are the canonical next step for
a user of this resource.

- [[Other-Resource]] — one-line description of relationship
```

---

## Writing Rules

### Tone and voice
- Write for a Laravel developer who knows PHP but is new to this SDK
- Direct, concrete, no filler — every sentence earns its place
- Use "throws" not "will throw", "returns" not "will return"

### Method signatures in headings
Always include the full signature with types and defaults:
```
### `list(array $filters = [], array $options = [])`
### `delete(string $id, string $newPhaseId)`
### `uploadLogo(string $id, string|null $image)`
```

### Required vs optional fields
In `create()` and similar sections, distinguish required from optional in a table or with a `> **Required:**` blockquote. Always note what the validation throws.

### Enum values
Whenever a field has a fixed set of valid values, list them explicitly:
> **Valid values:** `open`, `accepted`, `expired`, `rejected`, `closed`

### Silent coercion
If the source silently transforms input (e.g. string status to array, sort field forced to `name`), document it with a blockquote note — this is the kind of gotcha that wastes hours.

### Client-side vs server-side
Always clarify when a helper method is **client-side** (fetches all data then filters/sorts in PHP) vs a server-side filter. Client-side operations are expensive on large accounts.

### Multiple API calls
Note when a method makes more than one API call (e.g. `manageTags()` makes two calls, `allForDepartment()` makes N calls).

### Return values for 204 responses
Methods that return HTTP 204 (empty body) should be documented as returning an empty array. Note this explicitly rather than leaving it ambiguous.

### No navigation tables
Do not include a `## Navigation` table of contents. GitHub wiki renders its own sidebar.

### No `## See Also` sections
Cross-linking belongs in `## Related Resources` only.

---

## Capabilities Checklist

Before shipping any doc, verify each of these against the source:

- [ ] `supportsCreation` / `supportsUpdate` / `supportsDeletion` all match
- [ ] `supportsPagination` — does `list()` actually build a `page` param?
- [ ] `supportsFiltering` — does `buildFilters()` exist and handle the listed keys?
- [ ] `supportsSorting` — does `buildSort()` exist? Are valid fields documented?
- [ ] `supportsSideloading` — is `$availableIncludes` populated?
- [ ] All methods in the class are documented (including helpers)
- [ ] All filters in `buildFilters()` are in the Filters table
- [ ] All `throw` statements are represented in Error Handling
- [ ] Status filter values are shown as arrays where required
- [ ] Any `update()` that injects id into body is noted
- [ ] Any `info()` override (throws or simulated via list) is noted
- [ ] Any client-side helpers (search, filter in PHP) are flagged
- [ ] Any multi-API-call methods are flagged

---

## Sections to Skip

Only include a section if it has real content from the source. Do not create empty or placeholder sections.

| Skip if... |
|---|
| No filters → omit `## Filters` |
| No sorting → omit `## Sorting` |
| No sideloading → omit `## Sideloading` |
| No helper methods → omit `## Helper Methods` |
| All errors are generic API errors → keep `## Error Handling` brief |

---

## Remaining Sections to Document

For reference, the sections not yet completed as of the end of the Deals session:

**Invoicing**
- Invoices, Credit Notes, Payment Methods, Payment Terms, Tax Rates,
  Withholding Tax Rates, Commercial Discounts, Subscriptions,
  Bookkeeping Submissions, Incoming Invoices, Incoming Credit Notes, Receipts

**Other sections**
- Projects, Planning, Products, Time Tracking, Expenses
