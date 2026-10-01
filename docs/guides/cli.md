# Command Line

Explore and query Teamleader from the terminal, without writing code. Every
command goes through the same resource methods your code uses, so filters,
sort fields and includes are validated the same way — and the error tells you
what the endpoint accepts.

{% hint style="info" %}
Available from v3.0. These commands only read. Export, import and raw calls
follow.
{% endhint %}

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

## Errors

API errors print as one line with the HTTP status, whatever
`TEAMLEADER_THROW_EXCEPTIONS` says, and the command exits with 1. Invalid
input exits with 2. Add `-v` for the full exception.
