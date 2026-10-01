# Bulk Operations

Moving many records in or out of Teamleader: paging, the rate limit and
failures are handled for you.

{% hint style="info" %}
Available from v3.0. This page covers export; bulk writes follow.
{% endhint %}

## Export

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

Teamleader::bulk()
    ->export('contacts', ['tags' => ['customer']])
    ->toCsv(storage_path('contacts.csv'), ['id', 'first_name', 'last_name', 'emails.0.email']);
```

`export()` takes a resource key — the name you use as `Teamleader::{key}()` —
and the same filters and options as that resource's `list()`. They are
validated the same way. Paginated resources are read 100 records a page;
others in a single `list()` call. Each returns the number of records.

| Method | Writes |
|---|---|
| `toCsv($path, $columns = null)` | One row per record. Columns are dot paths: `address.city`, `emails.0.email` |
| `toJsonLines($path)` | One complete JSON object per line — for files code reads back |
| `each(fn (array $record, int $i) => ...)` | Nothing; hands each record to your callback |

For another connection: `Teamleader::connection('antwerp')->bulk()->export(...)`.

### CSV details

- **Columns.** Name them. Without, the first record's fields are used,
  flattened — and a field missing from the first record is missing from the
  file.
- **Values.** Arrays become JSON, booleans `true` / `false`, null an empty cell.
- **Formulas.** Text starting with `=`, `+`, `-` or `@` gets a leading `'`, so
  a spreadsheet does not execute a value from Teamleader as a formula. Negative
  numbers are left alone. For a file only code reads, pass
  `escapeFormulas: false`.
- **Delimiter.** `toCsv($path, $columns, ';')` for Excel with a Dutch or
  Belgian locale.

### Safe to fail

The file is written under a temporary name and moved into place when
complete. A failed page throws — even with `TEAMLEADER_THROW_EXCEPTIONS=false`
— and leaves no partial file behind.

### Progress

```php
Teamleader::bulk()->export('deals')
    ->onProgress(fn (int $done, ?int $total) => $this->output->write("\r{$done}/".($total ?? '?')))
    ->toJsonLines(storage_path('deals.jsonl'));
```

`$total` is known on the endpoints that report one; elsewhere it is null.

### How long it takes

At 100 records a request and 200 requests a minute, at most about 20,000
records a minute. In a web request, export in a queued job instead.
