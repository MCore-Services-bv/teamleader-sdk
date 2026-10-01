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

`export()` takes a resource key, which is the name you use in
`Teamleader::{key}()`, and the same filters and options as that resource's
`list()`. They are validated the same way. Paginated resources are read 100
records a page, others in a single `list()` call. Each returns the number of records.

| Method | Writes |
|---|---|
| `toCsv($path, $columns = null)` | One row per record. Columns are dot paths: `address.city`, `emails.0.email` |
| `toJsonLines($path)` | One complete JSON object per line — for files code reads back |
| `each(fn (array $record, int $i) => ...)` | Nothing; hands each record to your callback |

For another connection: `Teamleader::connection('antwerp')->bulk()->export(...)`.

### CSV details

- **Columns.** Name them. Without them, the first record's fields are used,
  flattened, so a field missing from the first record is missing from the
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
complete. A failed page throws, even with `TEAMLEADER_THROW_EXCEPTIONS=false`,
and leaves no partial file behind.

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
input. A string key, such as a line number or your own id, maps results
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
`BulkValidationException` listing **all** invalid rows, and nothing is
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

Resuming relies on the row keys staying the same between runs, so use stable
keys or the same input file.

For imports that may meet records that already exist in Teamleader, look them
up first with `lazy()` and a filter, and split your rows into creates and
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
`->waitForRateLimit(false)` keeps your normal limit. Use it in a web request,
for example, where waiting a minute is not an option.

## Queued bulk

For anything that would run longer than a request, or that should survive a
deploy, send the rows from queue workers:

```php
$batch = Teamleader::bulk()
    ->create('companies', $rows)
    ->continueOnError()
    ->dispatch(chunk: 50);

$batch->id;   // keep it: Bus::findBatch($batch->id) for progress, Horizon shows it too
```

- **Validation, `uniqueBy()` and `resumeFrom()` happen in `dispatch()`**, in
  your request: an invalid row throws `BulkValidationException` and nothing is
  queued.
- Each chunk is one job, on the connection you dispatched from
  (`Teamleader::connection('antwerp')->bulk()->...`).
- **Rate limits.** A job that meets the rate limit releases itself for as long
  as Teamleader asks, instead of blocking the worker, and resumes after the
  rows it already sent, so a retried chunk never sends a row twice. Jobs retry
  for up to a day.
- **Stopping.** Without `continueOnError()`, the first refusal cancels the
  batch: chunks that had not started yet report their rows as skipped.
- Chunks may run in parallel on several workers. They share the connection's
  rate-limit window, so this does not make the import faster than 200 rows a
  minute. It only frees your workers while they wait.

### The result

```php
use McoreServices\TeamleaderSDK\Events\BulkBatchFinished;

Event::listen(function (BulkBatchFinished $event) {
    $event->result->counts();    // ['succeeded' => 9998, 'failed' => 2, 'skipped' => 0]
    $event->result->failed();    // [row key => BulkFailure]
    $event->cancelled;
});

// Or at any time, also while it runs:
Teamleader::bulk()->result($batch->id);
```

Succeeded rows carry only the record's id: `['data' => ['id' => '...']]`,
whatever else the API answered. When a later step needs more than the id,
keep your own mapping table — your row key to the new id, stored from the
`BulkBatchFinished` listener — and read the rest with `info()`. Results are
kept in the cache for a week.

### Requirements

- A real queue, such as `database`, `redis` or `sqs`. `dispatch()` refuses the `sync`
  driver, which would run everything inside the request.
- Laravel's batches table, once per application:

  ```bash
  php artisan make:queue-batches-table
  php artisan migrate
  ```
- A cache store shared by the workers, such as Redis or the database. The
  chunks record their progress there.

### With Horizon

Horizon only works queues on a `redis` connection, so dispatch to one:

```php
Teamleader::bulk()->create('contacts', $rows)->dispatch(chunk: 50, queueConnection: 'redis', queue: 'teamleader');
```

The 200 requests a minute are per Teamleader account, not per worker. A
dedicated supervisor with one or two processes is enough; more processes
only wait for the same window.

```php
// config/horizon.php, under 'environments'
'teamleader' => [
    'connection' => 'redis',
    'queue' => ['teamleader'],
    'maxProcesses' => 2,
],
```

The jobs set their own retry window (a day), which takes precedence over the
supervisor's `tries`.

## Migrating from another system

Teamleader dates every record at the moment it is created. A deal, note,
e-mail or file cannot be backdated through the API, so put the original date
in the content or in a custom field when it matters. Some other behaviour to
plan for:

- **Select custom fields** take the option label, not the option id. See
  [Custom Fields](custom-fields.md).
- **Pipelines** come with four fixed phases that cannot be deleted. Map onto
  them instead of deleting and recreating.
- **Notes** have no type and are authored by the connected user.
- **E-mail tracking** files every item as a received e-mail, and items cannot
  be updated or deleted afterwards. Try one record before a bulk run.
- **Files**: `call('files', 'uploadFile', [[$path, 'deal', $dealId, 'Folder'], …])`
  uploads local files, one row per file.

Check every step with `dryRun()` first. It shows the exact request bodies,
and it is where a wrong value format shows up before any data is touched.
