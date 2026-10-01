<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

/**
 * What a bulk operation did, row by row. Every input row ends up in exactly
 * one of succeeded(), failed() and skipped(), under its input key.
 */
final class BulkResult
{
    /** @var array<int|string, array> */
    private array $succeeded = [];

    /** @var array<int|string, BulkFailure> */
    private array $failed = [];

    /** @var array<int|string, string> index => reason */
    private array $skipped = [];

    /** @var array<int|string, list<array{method: string, endpoint: string, body: array}>> */
    private array $requests = [];

    public function __construct(
        public readonly string $resource,
        public readonly string $operation,
        public readonly bool $dryRun = false,
    ) {}

    /** @internal */
    public function recordSuccess(int|string $index, array $response): void
    {
        $this->succeeded[$index] = $response;
    }

    /** @internal */
    public function recordFailure(BulkFailure $failure): void
    {
        $this->failed[$failure->index] = $failure;
    }

    /** @internal */
    public function recordSkip(int|string $index, string $reason): void
    {
        $this->skipped[$index] = $reason;
    }

    /**
     * @internal
     *
     * @param  list<array{method: string, endpoint: string, body: array}>  $requests
     */
    public function recordRequests(int|string $index, array $requests): void
    {
        $this->requests[$index] = $requests;
    }

    /** @return array<int|string, array> index => API response */
    public function succeeded(): array
    {
        return $this->succeeded;
    }

    /** @return array<int|string, BulkFailure> */
    public function failed(): array
    {
        return $this->failed;
    }

    /** @return array<int|string, string> index => why it was not sent */
    public function skipped(): array
    {
        return $this->skipped;
    }

    /**
     * The requests each row produced — on a dry run, what would have been sent.
     *
     * @return array<int|string, list<array{method: string, endpoint: string, body: array}>>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    /**
     * Keys of the rows that succeeded — what resumeFrom() needs. Small enough
     * to store between runs (json_encode it).
     *
     * @return list<int|string>
     */
    public function succeededIndices(): array
    {
        return array_keys($this->succeeded);
    }

    public function hasFailures(): bool
    {
        return $this->failed !== [];
    }

    /** Every row succeeded */
    public function isComplete(): bool
    {
        return $this->failed === [] && $this->skipped === [];
    }

    /**
     * Nothing is left to do: no failures, and no row left unsent because the
     * run stopped. Rows skipped on purpose — duplicates, or rows an earlier
     * run already did — do not count against it.
     */
    public function isFinished(): bool
    {
        return $this->failed === [] && ! in_array(BulkOperation::STOPPED, $this->skipped, true)
            && ! in_array(self::CANCELLED, $this->skipped, true);
    }

    /** @internal Recorded for rows of a queued batch that was cancelled */
    public const CANCELLED = 'Not sent: the batch was cancelled after a failure.';

    /**
     * @return array{succeeded: int, failed: int, skipped: int}
     */
    public function counts(): array
    {
        return ['succeeded' => count($this->succeeded), 'failed' => count($this->failed), 'skipped' => count($this->skipped)];
    }
}
