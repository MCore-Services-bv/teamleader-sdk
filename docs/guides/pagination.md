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

`lazy()` pages through a list for you. It returns a Laravel
[`LazyCollection`](https://laravel.com/docs/collections#lazy-collections):
nothing is requested until you iterate it, and only as many pages as you
consume are fetched.

```php
Teamleader::companies()
    ->lazy(['status' => 'active'])
    ->each(function (array $company) {
        // one record at a time; pages of 100 are fetched as needed
    });

Teamleader::deals()->lazy(['status' => 'open'])->take(10)->all();   // one request
```

- Filters and options are the same as for `list()`, and are validated the same
  way. Because nothing is sent until iteration, an invalid filter throws on
  the first iteration, not when you call `lazy()`.
- `page_size` defaults to **100** (instead of `list()`'s 20), so a full export
  costs a fifth of the requests. `page_number` is the page to start from.
- Fluent includes apply to every page:
  `Teamleader::deals()->withCustomFields()->lazy()`.
- A failed page throws, even with `TEAMLEADER_THROW_EXCEPTIONS=false`. Treating
  an error as an empty page would end the iteration early and silently lose
  every record after it.
- On a resource that is not paginated, `lazy()` throws a
  `BadMethodCallException`. Those endpoints return everything in one `list()`
  response.

{% hint style="info" %}
`lazy()` and `cursor()` are available from v3.0.
{% endhint %}

### Totals, progress and resuming

`cursor()` returns the pager itself, when you need its state:

```php
$cursor = Teamleader::calls()->cursor([], ['page_size' => 50]);

$cursor->total();       // meta.matches where the endpoint reports it, otherwise null

foreach ($cursor as $call) {
    // ...
}

$cursor->pagesFetched();
$cursor->lastPage();    // the last page completed
```

To resume an interrupted export, start a cursor after the last completed page:
`$cursor->fromPage($lastPage + 1)`, or pass `page_number` to `lazy()`.

Where the endpoint reports a total, the cursor stops as soon as it has read
that many records, which saves the empty request at the end.

### Safety

The cursor stops with an `UnexpectedValueException` if the endpoint returns
more records than the page size, or the same first record on two consecutive
pages. Either means the endpoint is not honouring the page parameters, and
paging it would never end.

Some resources also have an `all()` helper that fetches every page into one
array — `customFields()->all()`, for example. `lazy()` works everywhere and
keeps memory flat.

## Large exports

A full export of a big account can run into the rate limit: at 200 requests a
minute and 100 records a page, that is about 20,000 records a minute at best. In a queue job,
catch `RateLimitExceededException` and release the job with the page you
reached, rather than looping until the worker times out. See
[Rate limiting](rate-limiting.md).
