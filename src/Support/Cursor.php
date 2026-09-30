<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Support;

use Closure;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use UnexpectedValueException;

/**
 * Pages through a list endpoint, one request per page, as it is iterated.
 *
 * Built by Resource::cursor() and Resource::lazy(). Each page is fetched with
 * the resource's own list(), so filter, sort and include validation apply to
 * every page exactly as to a single call.
 *
 * The end of the list is a page shorter than the page size, an empty page, or
 * — on the endpoints that report one — the total from `meta.matches`, which
 * saves the empty request a full last page would otherwise cost.
 *
 * Two guards against looping forever: a page larger than the page size means
 * the endpoint ignored the page parameters, and a page that starts with the
 * same record as the one before means it ignored the page number. Both throw
 * instead of fetching the same records again.
 *
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
final class Cursor implements IteratorAggregate
{
    private ?int $total = null;

    private int $pagesFetched = 0;

    private ?int $lastPage = null;

    private bool $totalProbed = false;

    /**
     * @param  Closure(int $pageNumber, int $pageSize): array  $fetch  Returns one page's response
     * @param  string  $endpoint  For messages, e.g. `companies.list`
     */
    public function __construct(
        private readonly Closure $fetch,
        private readonly int $pageSize,
        private readonly string $endpoint,
        private readonly int $startPage = 1,
    ) {
        if ($pageSize < 1) {
            throw new InvalidArgumentException("page_size must be at least 1, {$pageSize} given.");
        }

        if ($startPage < 1) {
            throw new InvalidArgumentException("page_number must be at least 1, {$startPage} given.");
        }
    }

    /**
     * A new cursor over the same query, starting at another page.
     *
     * For resuming: `$cursor->fromPage($cursor->lastPage() + 1)` continues after
     * the last page a previous run completed.
     */
    public function fromPage(int $pageNumber): self
    {
        return new self($this->fetch, $this->pageSize, $this->endpoint, $pageNumber);
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    public function getIterator(): Generator
    {
        $page = $this->startPage;
        $yielded = 0;
        $previousFirstId = null;

        while (true) {
            $response = ($this->fetch)($page, $this->pageSize);
            $records = $this->records($response, $page);

            $this->pagesFetched++;
            $this->lastPage = $page;

            if (isset($response['meta']['matches']) && is_numeric($response['meta']['matches'])) {
                $this->total = (int) $response['meta']['matches'];
            }

            if (count($records) > $this->pageSize) {
                throw new UnexpectedValueException(
                    "{$this->endpoint} returned ".count($records)." records for a page size of {$this->pageSize}. "
                    .'The endpoint is not honouring the page parameters, so paging it would never end.'
                );
            }

            $firstId = $records[0]['id'] ?? null;

            if ($firstId !== null && $firstId === $previousFirstId) {
                throw new UnexpectedValueException(
                    "{$this->endpoint} returned the same first record on pages ".($page - 1)." and {$page}. "
                    .'The endpoint is ignoring the page number, so paging it would never end.'
                );
            }

            foreach ($records as $record) {
                yield $record;
                $yielded++;
            }

            if (count($records) < $this->pageSize) {
                return;
            }

            // Known total reached: skip the request that would come back empty
            if ($this->total !== null && ($page - 1) * $this->pageSize + count($records) >= $this->total) {
                return;
            }

            $previousFirstId = $firstId;
            $page++;
        }
    }

    /**
     * The total number of matches, when the endpoint reports one
     * (`meta.matches`, sent by resources that request `includes=pagination`).
     *
     * Known once the first page has been fetched; fetches it if it has not.
     * Null for endpoints that report no total.
     */
    public function total(): ?int
    {
        if ($this->pagesFetched === 0 && ! $this->totalProbed) {
            $this->totalProbed = true;
            $response = ($this->fetch)($this->startPage, $this->pageSize);
            $this->records($response, $this->startPage);

            if (isset($response['meta']['matches']) && is_numeric($response['meta']['matches'])) {
                $this->total = (int) $response['meta']['matches'];
            }
        }

        return $this->total;
    }

    /** Pages requested so far */
    public function pagesFetched(): int
    {
        return $this->pagesFetched;
    }

    /** The last page number fetched, or null before the first */
    public function lastPage(): ?int
    {
        return $this->lastPage;
    }

    public function pageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * The records of one page, or an exception for an error response.
     *
     * With `throw_exceptions` off the SDK returns failures as arrays. Reading one
     * as an empty page would end the iteration early and silently lose every
     * record after it, so it throws regardless of that setting.
     *
     * @return list<array<string, mixed>>
     */
    private function records(array $response, int $page): array
    {
        if (! empty($response['error'])) {
            throw new TeamleaderException(
                "{$this->endpoint} failed on page {$page}: ".($response['message'] ?? 'unknown error'),
                0,
                null,
                ['page' => $page, 'endpoint' => $this->endpoint],
                isset($response['status_code']) ? (int) $response['status_code'] : null
            );
        }

        $data = $response['data'] ?? [];

        return is_array($data) ? array_values($data) : [];
    }
}
