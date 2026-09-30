# Upgrading

## From 2.3 to 3.0

> 3.0 is in development on the `3.x` branch. This section grows as the
> breaking changes land.

### Requirements

**PHP 8.4 or higher.** 3.0 drops PHP 8.2 and 8.3. Laravel 12 and 13 are both
still supported.

| | 2.3 | 3.0 |
|---|---|---|
| PHP | 8.2 – 8.5 | 8.4 – 8.5 |
| Laravel | 12, 13 | 12, 13 |

Check your version with `php -v`. If you are on 8.2 or 8.3, stay on
`^2.3` until you have upgraded PHP; 2.x receives security fixes for three
months after 3.0 is released.

### Sorting

No code changes are needed. Two small differences:

- Sort validation messages name the endpoint:
  `Invalid sort field: title. deals.list accepts: created_at, weighted_value.`
  If you match on the old `Accepted:` wording, match on `Invalid sort field`
  instead.
- `timeTracking()->list()` now also accepts a list of field names and a
  `['starts_on' => 'desc']` map, like every other resource.

### Removed methods and resource keys

Everything deprecated during the 2.2.x audit is gone. Each of these now fails
with "Call to undefined method" (or, for a resource key, an exception naming
the new key). Search your code for the left-hand column:

| Removed | Use instead |
|---|---|
| `users()->getWeekSchedule($id)` | `userSchedules()->forUser($id, $from, $until)` — at most seven days |
| `plannableItems()->active()` | `plannableItems()->list()`; filter on `completion_statuses` / `planned_time_statuses` |
| `invoices()->draft()` | `invoices()->listDrafts()` |
| `lostReasons()->search($ids)` | `lostReasons()->byIds($ids)`, or `all()` without ids |
| `companies()->byName($name)` | `companies()->search($name)` — the `term` filter |
| `quotations()->byStatus($status)` | `quotations()->list()`, filtered client-side on `data[].status` |
| `creditNotes()->paid()`, `unpaid()` | `creditNotes()->list()`, filtered client-side on `data[].paid` |
| `products()->withCustomFields()` | nothing — `info()` always returns custom fields |
| `deals()->withCustomer()`, `withResponsibleUser()`, `withDepartment()`, `withCurrentPhase()`, `withSource()`, `withAll()` | nothing — the data is returned on every deal |
| `calenderEvents()` | `calendarEvents()` |
| `creditnotes()` | `creditNotes()` |
| `payment_methods()` | `paymentMethods()` |
| `payment_terms()` | `paymentTerms()` |
| `external_parties()` | `externalParties()` |
| `plannable_items()` | `plannableItems()` |
| `user_availability()` | `userAvailability()` |
| `$sdk->getDeprecatedResourceAliases()` | nothing — there are no aliases left |

In 2.3 all of these still ran, most of them with an `E_USER_DEPRECATED`
notice in your log. If you upgrade to 2.3 first and clear those notices, this
step is a no-op.

## From 2.2 to 2.3

There are no breaking API changes in the SDK itself, and 2.3 runs on the same
PHP and Laravel versions. What does change across 2.2.4 – 2.3.0 is that
requests which used to be silently wrong now throw.

**Silent no-ops now throw `InvalidArgumentException`.** An unsupported filter,
sort field, include, option or body field used to be passed to the API — which
ignored it — or dropped by the SDK. Either way the call returned unfiltered
data, or an update changed nothing. Now it throws, naming the accepted values.

If upgrading surfaces one of these, the call was not doing what it appeared
to. Fix the name rather than catching the exception; the resource's
[reference page](../reference/README.md) lists what it accepts.

The most common cases:

| Before | Instead |
|---|---|
| `companies()->list(['name' => ...])` | `['term' => ...]` — searches name, VAT, email and phone |
| `timeTracking()->list(['updated_since' => ...])` | `started_after` / `ended_after` |
| `companies()->info($id, 'custom_fields')` | nothing — custom fields are always returned on `info` |
| `contacts()->with(...)->info($id)` | nothing — `contacts.info` takes no includes |
| A sort on a field the endpoint does not sort by | a field from the resource's **Sorting** section |

**Stricter-than-API checks were relaxed** at the same time, where the SDK
demanded fields the API does not — meeting customers, timer subjects, expense
totals, task work types on update. Code that worked around those can be
simplified.

The full list, release by release, is in the
[changelog](https://github.com/MCore-Services-bv/teamleader-sdk/blob/main/CHANGELOG.md).

## From 1.x to 2.0

2.0 dropped Laravel 10 and 11, which are end of life with unpatched security
advisories. On Laravel 12 no changes are needed. On Laravel 10 or 11, upgrade
Laravel, or pin the SDK to `^1.2` until you can.
