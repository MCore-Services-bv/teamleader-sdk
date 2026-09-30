# Artisan Commands

| Command | |
|---|---|
| `teamleader:status` | Connection, token and rate-limit status |
| `teamleader:health` | Health checks: configuration, authentication, tokens, API connectivity, rate limits, database, the token cache and dependencies |
| `teamleader:config:validate` | Validate the configuration and environment |
| `teamleader:export-uuids` | Print account UUIDs for `config/teamleader.php` |

## `teamleader:status`

```bash
php artisan teamleader:status
php artisan teamleader:status --json
```

Whether the application is connected, when the token expires, and current
rate-limit usage.

## `teamleader:health`

```bash
php artisan teamleader:health
php artisan teamleader:health --score   # the overall score only
php artisan teamleader:health --json
php artisan teamleader:health --fix     # attempt to fix what it can
```

Suitable for a deployment check or a monitoring probe; `--json` gives
machine-readable output.

## `teamleader:config:validate`

```bash
php artisan teamleader:config:validate
php artisan teamleader:config:validate --fix      # how to fix each issue
php artisan teamleader:config:validate --report   # the full configuration report
php artisan teamleader:config:validate --json
```

Set `TEAMLEADER_VALIDATE_ON_BOOT=true` to run the same checks whenever the
application boots.

## `teamleader:export-uuids`

```bash
php artisan teamleader:export-uuids
php artisan teamleader:export-uuids --resource=deal-phases
```

Prints the account's reference records as configuration to paste into
`config/teamleader.php`. `--resource` takes one of `departments`, `users`,
`teams`, `work-types`, `pipelines`, `deal-phases`, `deal-sources`,
`lost-reasons`, `payment-terms`, `tax-rates`, `price-lists`, `business-types`,
`product-categories`, `activity-types`, `call-outcomes`, `units-of-measure` and
`custom-fields`. See [Configuration](../getting-started/configuration.md#storing-reference-uuids).
