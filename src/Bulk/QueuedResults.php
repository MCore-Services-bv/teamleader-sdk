<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Bulk;

use Illuminate\Support\Facades\Cache;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

/**
 * What the chunks of one queued bulk run have done, kept in the cache.
 *
 * A job that releases itself on a rate limit is re-queued with its original
 * payload — anything it remembered in its own properties is gone. Recording
 * each finished row here is what lets the retry resume after the rows it
 * already sent, instead of sending them again: Teamleader has no idempotency
 * keys, so a resent create is a duplicate record.
 *
 * Responses are reduced to the record id, so ten thousand rows stay small.
 * Kept for a week.
 */
final class QueuedResults
{
    private const TTL = 604800;

    public function __construct(public readonly string $runId) {}

    /**
     * @param  list<string>  $chunks
     */
    public function start(string $connection, string $resource, string $operation, array $chunks): void
    {
        Cache::put($this->key('meta'), compact('connection', 'resource', 'operation', 'chunks'), self::TTL);
    }

    public static function forBatch(string $batchId): ?self
    {
        $runId = Cache::get("teamleader:bulk:batch:{$batchId}");

        return is_string($runId) ? new self($runId) : null;
    }

    public function linkBatch(string $batchId): void
    {
        Cache::put("teamleader:bulk:batch:{$batchId}", $this->runId, self::TTL);
    }

    /**
     * @return list<int|string>
     */
    public function succeededIndices(string $chunk): array
    {
        return array_keys($this->chunk($chunk)['succeeded']);
    }

    /**
     * Record a chunk's run. Succeeded rows are always kept. Failed and skipped
     * rows only when the chunk is finished — on a rate-limit release those
     * rows are about to be retried.
     */
    public function record(string $chunk, BulkResult $result, bool $final): void
    {
        $stored = $this->chunk($chunk);

        foreach ($result->succeeded() as $index => $response) {
            $stored['succeeded'][$index] = $response['data']['id'] ?? null;
        }

        if ($final) {
            foreach ($result->failed() as $index => $failure) {
                $stored['failed'][$index] = [$failure->message(), $failure->statusCode(), $failure->beforeSending];
            }

            foreach ($result->skipped() as $index => $reason) {
                if ($reason !== BulkOperation::ALREADY_SUCCEEDED) {
                    $stored['skipped'][$index] = $reason;
                }
            }
        }

        Cache::put($this->key("chunk:{$chunk}"), $stored, self::TTL);
    }

    /**
     * @param  list<int|string>  $indices
     */
    public function skip(string $chunk, array $indices, string $reason): void
    {
        $stored = $this->chunk($chunk);

        foreach ($indices as $index) {
            if (! array_key_exists($index, $stored['succeeded'])) {
                $stored['skipped'][$index] = $reason;
            }
        }

        Cache::put($this->key("chunk:{$chunk}"), $stored, self::TTL);
    }

    /**
     * Every chunk's rows as one BulkResult. Succeeded rows carry
     * `['data' => ['id' => ...]]`; failures carry the message and status.
     */
    public function collect(): ?BulkResult
    {
        $meta = Cache::get($this->key('meta'));

        if (! is_array($meta)) {
            return null;
        }

        $result = new BulkResult($meta['resource'], $meta['operation']);

        foreach ($meta['chunks'] as $chunk) {
            $stored = $this->chunk($chunk);

            foreach ($stored['succeeded'] as $index => $id) {
                $result->recordSuccess($index, ['data' => ['id' => $id]]);
            }

            foreach ($stored['failed'] as $index => [$message, $status, $beforeSending]) {
                $exception = new TeamleaderException($message, (int) $status, null, [], $status);
                $result->recordFailure(new BulkFailure($index, null, $exception, (bool) $beforeSending));
            }

            foreach ($stored['skipped'] as $index => $reason) {
                $result->recordSkip($index, $reason);
            }
        }

        return $result;
    }

    /** @return array{succeeded: array, failed: array, skipped: array} */
    private function chunk(string $chunk): array
    {
        $stored = Cache::get($this->key("chunk:{$chunk}"));

        return is_array($stored) ? $stored : ['succeeded' => [], 'failed' => [], 'skipped' => []];
    }

    private function key(string $suffix): string
    {
        return "teamleader:bulk:{$this->runId}:{$suffix}";
    }
}
