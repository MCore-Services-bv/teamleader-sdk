<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Listeners;

use Illuminate\Support\Facades\Log;
use McoreServices\TeamleaderSDK\Events\RequestSending;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use Psr\Log\LoggerInterface;

/**
 * Writes requests and responses to the SDK's log channel.
 *
 * Registered by the service provider when `teamleader.logging.log_requests`
 * or `log_responses` is on. Both are off by default: bodies contain your
 * customers' data, and logging them is a decision, not a default. Tokens,
 * secrets and other sensitive keys are redacted before the event is fired,
 * so they never reach this listener.
 */
final class LogApiTraffic
{
    public function requestSending(RequestSending $event): void
    {
        $this->logger()->debug("Teamleader request: {$event->method} {$event->endpoint}", [
            'body' => $event->body,
        ]);
    }

    public function responseReceived(ResponseReceived $event): void
    {
        $this->logger()->debug("Teamleader response: {$event->statusCode} {$event->endpoint}", [
            'duration_ms' => $event->durationMs,
            'body' => $event->body,
        ]);
    }

    private function logger(): LoggerInterface
    {
        $channel = config('teamleader.logging.channel');

        return is_string($channel) && $channel !== '' ? Log::channel($channel) : Log::driver();
    }
}
