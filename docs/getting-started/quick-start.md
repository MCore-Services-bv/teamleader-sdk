# Quick Start

Once [authentication](authentication.md) is set up, every resource is a method
on the `Teamleader` facade.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// List
$companies = Teamleader::companies()->list(['status' => 'active']);

// Get one
$contact = Teamleader::contacts()->info('contact-uuid');

// Create
$deal = Teamleader::deals()->create([
    'title' => 'New opportunity',
    'lead'  => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
]);

// Update
Teamleader::companies()->update('company-uuid', ['name' => 'Acme Corp Ltd']);

// Delete
Teamleader::companies()->delete('company-uuid');
```

Responses are the decoded JSON body. A list comes back as `['data' => [...]]`,
a single record as `['data' => [...]]` with its fields, and a create as
`['data' => ['id' => ..., 'type' => ...]]`.

## The shape of a `list()` call

Filters go in the first argument, everything else in the second:

```php
$deals = Teamleader::deals()->list(
    ['phase_id' => $phaseId, 'responsible_user_id' => $userId],   // filters
    ['sort' => 'created_at', 'sort_order' => 'desc',              // options
     'page_size' => 50, 'page_number' => 1]
);
```

Each resource accepts its own filters and sort fields, listed on its page in
the [API reference](../reference/README.md). Anything else throws before the
request is sent — see [Validation](../guides/validation.md).

## Dependency injection

The facade resolves `McoreServices\TeamleaderSDK\TeamleaderSDK`, which you can
inject instead:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CompanySync
{
    public function __construct(private TeamleaderSDK $teamleader) {}

    public function active(): array
    {
        return $this->teamleader->companies()->list(['status' => 'active']);
    }
}
```

## Turn on exceptions

By default a failed request is logged and returned as an array with
`error => true`. For new code, turn exceptions on:

```env
TEAMLEADER_THROW_EXCEPTIONS=true
```

See [Error handling](../guides/error-handling.md) for what is thrown when.

## What a resource can do

Every resource page in the [API reference](../reference/README.md) lists its
endpoints, filters, sort fields, includes, accepted values and methods. The same
information is available at runtime:

```php
Teamleader::companies()->getCapabilities();   // supports_*, available_includes, endpoint
Teamleader::companies()->getDocumentation();  // description, filters, sort fields, examples
```

## Next

- [Filtering and sorting](../guides/filtering-and-sorting.md)
- [Pagination](../guides/pagination.md)
- [Sideloading](../guides/sideloading.md)
