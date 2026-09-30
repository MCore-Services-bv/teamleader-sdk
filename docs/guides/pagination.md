# Pagination

Paginated resources take `page_size` and `page_number` in the options. The
defaults are 20 and 1.

```php
$page = Teamleader::companies()->list([], ['page_size' => 100, 'page_number' => 2]);
```

## Totals are the exception, not the rule

Teamleader returns a `meta` block with totals only when the request asks for
it with `includes=pagination`, and only some endpoints support that. Where they
do, the SDK sends it for you, and the resource's reference page says
*"the response includes `meta` with totals"*:

```php
$response = Teamleader::projects()->list([], ['page_size' => 50]);

$response['meta']['matches'];         // total number of records
$response['meta']['page']['number'];  // current page
$response['meta']['page']['size'];    // page size
```

Everywhere else there is **no total count**. The only signal that the list has
ended is a page shorter than the page size you asked for, so a final page that
happens to be full costs one extra, empty request.

## Fetching everything

```php
$all = [];
$page = 1;

do {
    $response = Teamleader::companies()->list(
        ['status' => 'active'],
        ['page_size' => 100, 'page_number' => $page++]
    );

    $all = [...$all, ...$response['data']];
} while (count($response['data']) === 100);
```

Some resources wrap this loop for you — `customFields()->all()`, for example.
Where they do, the method is listed on the resource's reference page.

## Large exports

A full export of a big account can run into the rate limit. In a queue job,
catch `RateLimitExceededException` and release the job with the page you
reached, rather than looping until the worker times out. See
[Rate limiting](rate-limiting.md).
