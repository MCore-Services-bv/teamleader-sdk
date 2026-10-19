<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature\Pulse\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use McoreServices\TeamleaderSDK\Events\RateLimitWaited;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use RuntimeException;

/**
 * A queued job that "calls Teamleader": it fires the events the SDK fires for
 * each call, so the callers recorder can be tested without HTTP.
 */
final class CallsTeamleaderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(
        public int $calls = 3,
        public float $msPerCall = 100.0,
        public int $waitMs = 0,
        public bool $fail = false,
    ) {}

    public function handle(): void
    {
        for ($i = 0; $i < $this->calls; $i++) {
            event(new ResponseReceived('POST', 'invoices.info', 200, $this->msPerCall));
        }

        if ($this->waitMs > 0) {
            event(new RateLimitWaited($this->waitMs, 95.0, 'invoices.info'));
        }

        if ($this->fail) {
            throw new RuntimeException('Failed on purpose');
        }
    }
}
