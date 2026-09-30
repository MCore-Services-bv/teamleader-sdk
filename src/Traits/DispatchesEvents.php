<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Traits;

use Closure;
use Throwable;

/**
 * Fire an SDK event through Laravel's dispatcher, and only when it has a
 * listener.
 *
 * The event is built by a closure, so an event nobody listens to costs one
 * hasListeners() lookup — the request body is never sanitised and no object
 * is created. Outside a Laravel application (bin/spec-audit, a plain PHPUnit
 * test) nothing is fired at all.
 */
trait DispatchesEvents
{
    /**
     * @param  class-string  $event
     * @param  Closure(): object  $make
     */
    protected function fireEvent(string $event, Closure $make): void
    {
        try {
            if (! function_exists('app') || ! app()->bound('events')) {
                return;
            }

            $events = app('events');

            if (! $events->hasListeners($event)) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        // Outside the try: a listener that throws should surface, not be
        // swallowed as if no listener existed.
        $events->dispatch($make());
    }
}
