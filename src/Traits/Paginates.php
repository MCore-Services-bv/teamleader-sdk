<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Traits;

use BadMethodCallException;
use Illuminate\Support\LazyCollection;
use McoreServices\TeamleaderSDK\Support\Cursor;

/**
 * lazy() and cursor() for every paginated resource.
 *
 * Both page through list() — the resource's own, so its filter, sort and
 * include validation apply to every page.
 */
trait Paginates
{
    /**
     * Every record matching the filters, fetched page by page as it is iterated.
     *
     *     Teamleader::companies()->lazy(['tags' => ['customer']])
     *         ->each(fn (array $company) => ...);
     *
     * Nothing is requested until the collection is iterated, and only as many
     * pages as the iteration consumes: `->take(10)` costs one request.
     * Validation errors therefore surface on first iteration, not here.
     *
     * @param  array  $filters  As for list()
     * @param  array  $options  As for list(). `page_size` defaults to 100;
     *                          `page_number` is the page to start from
     * @return LazyCollection<int, array<string, mixed>>
     *
     * @throws BadMethodCallException When this endpoint is not paginated
     */
    public function lazy(array $filters = [], array $options = []): LazyCollection
    {
        $cursor = $this->cursor($filters, $options);

        return LazyCollection::make(fn () => yield from $cursor);
    }

    /**
     * The pager behind lazy(), for when you need its state: the total where the
     * endpoint reports one, the pages fetched, the last page for resuming.
     *
     *     $cursor = Teamleader::calls()->cursor([], ['page_size' => 50]);
     *     $cursor->total();      // from meta.matches, or null
     *
     *     foreach ($cursor as $call) { ... }
     *     $cursor->lastPage();   // resume with $cursor->fromPage($cursor->lastPage() + 1)
     *
     * @throws BadMethodCallException When this endpoint is not paginated
     */
    public function cursor(array $filters = [], array $options = []): Cursor
    {
        $endpoint = $this->getBasePath().'.list';

        if (! $this->supportsPagination) {
            throw new BadMethodCallException(
                "{$endpoint} is not paginated: it returns every record in one response. Use list()"
                .(method_exists($this, 'all') ? ' or all()' : '').' instead of lazy() / cursor().'
            );
        }

        // Fluent includes (->withCustomFields()->lazy()) are consumed by the
        // first list() call. Captured once here, they apply to every page.
        $pending = $this->getPendingIncludes();
        $this->pendingIncludes = [];

        if ($pending !== []) {
            $options['include'] = [...(array) ($this->resolveIncludesOption($options) ?? []), ...$pending];
            unset($options['includes']);
        }

        $pageSize = (int) ($options['page_size'] ?? 100);
        $startPage = (int) ($options['page_number'] ?? 1);
        unset($options['page_size'], $options['page_number']);

        return new Cursor(
            fn (int $page, int $size) => $this->list($filters, [...$options, 'page_size' => $size, 'page_number' => $page]),
            $pageSize,
            $endpoint,
            $startPage,
        );
    }
}
