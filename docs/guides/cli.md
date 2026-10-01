# Command Line

Explore and query Teamleader from the terminal, without writing code. Every
command goes through the same resource methods your code uses, so filters,
sort fields and includes are validated the same way, and an error tells you
what the endpoint accepts.

{% hint style="info" %}
Available from v3.0.
{% endhint %}

**Nothing writes without `--write`.** With it, you are asked to confirm, and
the question names the endpoint and the number of requests. `--force` skips
the question, for scripts. In production `--force` is required as well, and a
run with `-n` (cron, CI) is refused rather than left waiting.

Every command takes `--connection=antwerp` for a connection other than the
default.

## What is there

```bash
php artisan teamleader:resources                  # every resource and what it supports
php artisan teamleader:resources --category=CRM
php artisan teamleader:describe deals             # filters, sort fields, includes, methods
```

`describe` is the resource's [reference page](../reference/README.md) in the
terminal, read from the same source.

## Listing records

```bash
php artisan teamleader:list companies
php artisan teamleader:list deals --filter=status[]=open --sort=created_at:desc --fields=id,title,estimated_value.amount
php artisan teamleader:list contacts --filter=tags[]=customer --all --format=csv > customers.csv
```

| Option | |
|---|---|
| `--filter=key=value` | Repeatable. `key[]=value` adds to a list, `key.sub=value` builds an object |
| `--sort=field` | `field:desc` for descending |
| `--include=a,b` | Includes to sideload |
| `--page-size=20`, `--page=1` | One page |
| `--all` | Every page, 100 records a request — with `--limit=` to stop early |
| `--fields=id,name` | Dot paths: `emails.0.email`, `address.city` |
| `--format=table` | `table`, `json` or `csv` |

Filter values are cast: `true`, `false`, `null` and numbers become their type.
Quote a value to keep it a string: `--filter='term="2024"'`.

An unknown filter is refused before anything is sent, with the accepted list:

```
$ php artisan teamleader:list deals --filter=colour=blue
Unsupported filter key for deals.list: colour. Supported: ids, term, customer, phase_id, …
```

## One record

```bash
php artisan teamleader:info deals 3f6c…
php artisan teamleader:info companies 3f6c… --include=custom_fields --format=json
```

A table shows every field flattened (`lead.customer.id`); `--fields=` picks
some.

## Export

```bash
php artisan teamleader:export contacts --filter=tags[]=customer --fields=id,first_name,last_name,emails.0.email
php artisan teamleader:export deals --format=jsonl --output=storage/deals.jsonl
php artisan teamleader:export companies --delimiter=';'            # Excel with a Belgian locale
```

Every page, 100 records a request, with a progress bar; the total is shown
where the endpoint reports one. Without `--output` the file goes to
`storage/app/teamleader/`. Filters, sorting and includes work as for `list`.
See [Bulk operations](bulk-operations.md#export) for how values are written.

## Import

```bash
php artisan teamleader:import companies companies.csv --dry-run      # see exactly what would be sent
php artisan teamleader:import companies companies.csv --write        # send it, after confirming
php artisan teamleader:import contacts updates.jsonl --update --write
php artisan teamleader:import deals won.csv --method=win --write     # any method, once per line
```

**Every line is validated before any is sent.** An invalid line stops the
import with the line numbers and what is wrong. Nothing is sent:

```
2 of 1200 rows are invalid. Nothing was sent.
  line 47: Unsupported field for companies.add: emial. Accepted: …
  line 802: …
```

| Option | |
|---|---|
| `--dry-run` | Validate and print the request bodies (`-v`: all of them); send nothing |
| `--update` | Each line updates the record in its `id` column |
| `--method=tag` | Call that method per line; the values, in column order, are its arguments |
| `--continue-on-error` | Keep going after a line the API refuses (default: stop) |
| `--unique-by=vat_number` | Only the first line per value is sent |
| `--resume=file` | Skip the lines a previous run did (see below) |
| `--queue`, `--chunk=50` | Dispatch to the queue — see [queued bulk](bulk-operations.md#queued-bulk) |
| `--numeric=a,b` | CSV columns to send as numbers |
| `--delimiter=;` | CSV delimiter |

### Files

- **CSV**: the header names the fields as dot paths, such as `address.city`
  or `lead.customer.id`. Cells are text, except `true`, `false`, `null` and JSON
  (`["vip","b2b"]`). **Empty cells are left out**, so on `--update` an empty
  cell leaves the field alone. Numbers stay text unless the column is named
  in `--numeric`, because a postal code or phone number looks like a number.
- **JSON Lines** (`.jsonl`): one JSON object per line, sent as it is. The
  format for typed or nested data.
- **JSON** (`.json`): an array of objects.

Results and errors refer to **line numbers** in your file.

### When an import stops

If a line is refused, or the run is interrupted, a results file is written to
`storage/app/teamleader/`. Fix the failed lines and run the same command with
`--resume=` that file: the lines that succeeded are not sent again.

## Raw calls

For an endpoint the SDK does not wrap:

```bash
php artisan teamleader:call users.me
php artisan teamleader:call companies.info --data='{"id":"3f6c…"}'
echo '{"filter":{"term":"Acme"}}' | php artisan teamleader:call companies.list --data=-
```

The body is sent as it is — **no validation** — so only reading endpoints
(`.list`, `.info`, `.me`, `.download`) run without `--write`.

## Errors

API errors print as one line with the HTTP status, whatever
`TEAMLEADER_THROW_EXCEPTIONS` says, and the command exits with 1. Invalid
input exits with 2. Add `-v` for the full exception.
