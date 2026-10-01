# Bulk Operations

Moving many records in or out of Teamleader: paging, the rate limit and
failures are handled for you.

{% hint style="info" %}
Available from v3.0.
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

## Writing many records

```php
$result = Teamleader::bulk()
    ->create('companies', $rows)
    ->continueOnError()
    ->run();

$result->succeeded();   // [row key => API response]
$result->failed();      // [row key => BulkFailure]
$result->skipped();     // [row key => reason]
```

Every row ends up in exactly one of the three, under the key it had in your
input — so a string key such as a line number or your own id maps results
straight back.

| Operation | Each row |
|---|---|
| `create('companies', $rows)` | The array passed to `companies()->create()` |
| `update('companies', $rows)` | An array with `id`; the rest goes to `update($id, ...)` |
| `delete('companies', $ids)` | An id, or an array with `id` |
| `call('deals', 'win', $rows)` | The arguments for that method — a list is spread, anything else is the one argument |

Each row goes through the resource's own method, with exactly the validation
a single call gets.

### Every row is validated before any is sent

By default, `run()` first passes every row through the resource's
validation without sending anything. If any row is invalid, it throws a
`BulkValidationException` listing **all** invalid rows — and nothing has been
sent:

```
2 of 10000 rows are invalid for companies; nothing was sent.
  row 7412: Unsupported field for companies.add: emial. Accepted: ...
  row 9001: ...
```

`$e->failures` holds every one. So an import with a typo in row 7,412 fails
in a second, before creating 7,411 records you then have to find and delete.

`->validateFirst(false)` validates each row only as it is sent.

### When the API refuses a row

By default the operation **stops** at the first row Teamleader refuses; the
rows after it are reported as skipped. `->continueOnError()` sends every row
and collects the failures. Two failures always stop, because no later row can
do better: the rate limit running out, and a connection that
[needs to be connected again](../getting-started/authentication.md#when-teamleader-refuses-the-refresh-token).

Bulk operations throw internally whatever `TEAMLEADER_THROW_EXCEPTIONS` says,
so a refusal is never counted as a success, and restore your setting
afterwards.

### Dry run

```php
$preview = Teamleader::bulk()->create('companies', $rows)->dryRun();

$preview->failed();     // the invalid rows
$preview->requests();   // [row key => the exact request bodies that would be sent]
```

Nothing is sent. Validation is local, so a dry run is the real thing minus
the network.

### Duplicates and resuming

Teamleader has no idempotency keys: a create that is sent twice creates two
records. Two tools:

```php
// Only the first row per VAT number is sent; later ones are skipped
->uniqueBy(fn (array $row) => $row['vat_number'] ?? null)

// After a stop or a crash, skip the rows that already succeeded
$done = $first->succeededIndices();           // store this (json_encode) between runs
Teamleader::bulk()->create('companies', $rows)->resumeFrom($done)->run();
```

Resuming relies on the row keys staying the same between runs — use stable
keys, or the same input file.

For imports that may meet records that already exist in Teamleader, look them
up first — `lazy()` with a filter — and split your rows into creates and
updates.

### Progress

```php
->onProgress(fn (BulkProgress $p) => $bar->setProgress($p->processed))
```

`processed`, `total`, `succeeded`, `failed`, `skipped`, `percentage()`.

### Rate limit and duration

Rows are sent one at a time through the rate limiter. During `run()` the SDK
waits out a full rate-limit window rather than giving up (65 seconds instead
of `TEAMLEADER_RATE_LIMIT_MAX_WAIT_MS`), which suits a command or a job. At
200 requests a minute, **10,000 rows take about 50 minutes.**
`->waitForRateLimit(false)` keeps your normal limit — in a web request,
say, where waiting a minute is not an option.
