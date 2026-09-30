# Validation

The Teamleader API answers `200 OK` to a filter, sort field, include, option or
body field it does not recognise, and ignores it. Nothing in the response says
so. A typo looks like success:

- a mistyped **filter** returns every record, unfiltered
- a mistyped **sort field** returns the default order
- a mistyped **include** returns the response without the extra data — or, when
  the field is returned by default anyway, looks like it worked
- a mistyped **body field** on an update changes nothing, and reports success

The SDK checks those names, and the values of enumerated fields, before the
request is sent, and throws an `InvalidArgumentException` naming what the
endpoint accepts.

```php
Teamleader::companies()->update('company-uuid', ['vat' => 'BE0123456789']);
// InvalidArgumentException: companies.update does not accept: vat. The API would
// ignore it and report success. Accepted fields: addresses, bic, ..., vat_number, website.

Teamleader::companies()->update('company-uuid', ['preferred_currency' => 'EURO']);
// InvalidArgumentException: Invalid preferred_currency for companies.update: 'EURO'.
// Must be one of: BAM, CAD, CHF, ..., EUR, ...

Teamleader::timeTracking()->list(['updated_since' => '2026-08-01T00:00:00+02:00']);
// InvalidArgumentException: Invalid filter key 'updated_since' for timeTracking.list.
// Supported filters: ids, user_id, started_after, started_before, ...
```

## What is checked

| | Checked against |
|---|---|
| Filter keys | The endpoint's declared filters |
| Sort fields and order | The endpoint's declared sort fields; `asc` / `desc` |
| Includes | The includes of that exact endpoint — `list` and `info` often differ |
| Body fields on create and update | The fields the endpoint accepts |
| Enumerated values | The values the specification lists — statuses, currencies, types |
| Required fields | Fields the endpoint requires, including "one of" rules |

All of it comes from `@teamleader/focus-api-specification`, and the lists are
public constants on each resource class — `Companies::WRITE_FIELDS`,
`ProjectTasks::STATUSES` and so on. Each [reference page](../reference/README.md)
shows them under **Accepted values**, so you can build a form or a mapping
against the same list the SDK checks.

```php
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectTasks;

$options = ProjectTasks::STATUSES;   // ['to_do', 'in_progress', 'on_hold', 'done']
```

## What is not checked

Client-side validation covers the *shape* of a request. It does not know your
data: an unknown UUID, a closed deal or a missing permission is still answered
by the API, and becomes a `NotFoundException`, `ValidationException` or
`AuthorizationException`. See [Error handling](error-handling.md).

Where the SDK and the specification disagree with the live API, the
specification wins until the API is shown to behave otherwise; see
[Specification parity](../project/specification-parity.md) for how differences
are tracked.

## Catching it

`InvalidArgumentException` means the code is wrong, not the data or the network.
Let it fail your tests rather than catching it in production:

```php
it('syncs active companies', function () {
    Teamleader::companies()->list(['status' => 'active']);   // throws on a typo
});
```

## Upgrading from before v2.3

Between v2.2.4 and v2.3.0 these checks were added resource by resource. Code
that passed an unsupported name now throws where it used to be silently
ignored. In every such case the call was not doing what it appeared to — fix
the name rather than catching the exception. See [Upgrading](../project/upgrading.md).
