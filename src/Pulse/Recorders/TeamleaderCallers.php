<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Pulse\Recorders;

use Carbon\CarbonImmutable;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobReleasedAfterException;
use Illuminate\Routing\Events\Routing;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\Concerns\Ignores;
use Laravel\Pulse\Recorders\Concerns\LivewireRoutes;
use Laravel\Pulse\Recorders\Concerns\Sampling;
use McoreServices\TeamleaderSDK\Events\RateLimitWaited;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Pulse\Entries;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records how much of each web request, queued job and Artisan command was
 * spent on Teamleader: the time in API calls (answered or timed out) and
 * rate-limit waits, the number of calls, and the total time of the request,
 * job or command itself. The pause between retry attempts is not counted.
 *
 * Answers "why is this page slow?" when the answer is "it makes 14 Teamleader
 * calls". Only requests, jobs and commands that called Teamleader are recorded.
 *
 * Add it to `recorders` in config/pulse.php:
 *
 *     \McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderCallers::class => [
 *         'enabled' => env('PULSE_TEAMLEADER_CALLERS_ENABLED', true),
 *         'sample_rate' => env('PULSE_TEAMLEADER_CALLERS_SAMPLE_RATE', 1),
 *         'ignore' => [],
 *     ],
 *
 * `ignore` holds patterns matched against the route (`/admin/quotations`), job
 * class or command name.
 *
 * A command started with Artisan::call() (from a web request, a job or another
 * command) is a caller of its own, and its time also counts toward whatever
 * started it. One started with `$this->call()` from another command is part of
 * that command: Laravel fires no command events for it.
 * Long-running worker commands (queue:work, horizon, octane:start, …) are not
 * callers themselves: the jobs and requests they run are. Jobs on the `sync`
 * queue are part of whatever dispatched them.
 */
final class TeamleaderCallers
{
    use Ignores, LivewireRoutes, Sampling;

    /**
     * Commands that run other work for as long as they live.
     */
    private const WORKERS = [
        'queue:work',
        'queue:listen',
        'horizon',
        'horizon:work',
        'horizon:supervisor',
        'octane:start',
        'octane:frankenphp',
        'octane:roadrunner',
        'octane:swoole',
        'schedule:work',
        'reverb:start',
        'pulse:check',
        'pulse:work',
    ];

    /**
     * @var list<class-string>
     */
    public array $listen = [
        ResponseReceived::class,
        RequestFailed::class,
        RateLimitWaited::class,
        JobProcessing::class,
        JobProcessed::class,
        JobFailed::class,
        JobReleasedAfterException::class,
        CommandStarting::class,
        CommandFinished::class,
        Routing::class,
    ];

    /**
     * Jobs and commands in progress, innermost last.
     *
     * @var list<array{kind: string, name: string, id: ?string, started: float, ms: float, calls: int}>
     */
    private array $frames = [];

    /** Teamleader time outside any job or command: the web request's */
    private float $ms = 0.0;

    /** Teamleader calls outside any job or command */
    private int $calls = 0;

    public function __construct(
        private readonly Pulse $pulse,
    ) {}

    /**
     * Called by Pulse once: hooks into the end of every web request.
     */
    public function register(Application $app): void
    {
        $callback = function (Kernel $kernel) {
            if (method_exists($kernel, 'whenRequestLifecycleIsLongerThan')) {
                $kernel->whenRequestLifecycleIsLongerThan(-1, fn (Carbon $startedAt, Request $request, Response $response) => $this->pulse->rescue(
                    fn () => $this->recordRequest($startedAt, $request)
                ));
            }
        };

        $app->afterResolving(Kernel::class, $callback);

        if ($app->resolved(Kernel::class)) {
            $callback($app->make(Kernel::class));
        }
    }

    public function record(object $event): void
    {
        match (true) {
            $event instanceof ResponseReceived => $this->add($event->durationMs, 1),
            // A request that got no answer: the time it waited for one
            $event instanceof RequestFailed => $event->durationMs !== null ? $this->add($event->durationMs, 1) : null,
            $event instanceof RateLimitWaited => $this->add((float) $event->waitedMs, 0),
            $event instanceof JobProcessing => $this->startJob($event),
            $event instanceof JobProcessed, $event instanceof JobFailed, $event instanceof JobReleasedAfterException => $this->finishJob($event),
            $event instanceof CommandStarting => $this->startCommand($event),
            $event instanceof CommandFinished => $this->finishCommand($event),
            $event instanceof Routing => $this->startRequest(),
            default => null,
        };
    }

    private function add(float $ms, int $calls): void
    {
        $top = array_key_last($this->frames);

        if ($top === null) {
            $this->ms += $ms;
            $this->calls += $calls;

            return;
        }

        $this->frames[$top]['ms'] += $ms;
        $this->frames[$top]['calls'] += $calls;
    }

    /**
     * A web request is being routed: calls made before it (in a console
     * process that also handles a request, as in tests) are not its own.
     */
    private function startRequest(): void
    {
        if ($this->frames === []) {
            $this->ms = 0.0;
            $this->calls = 0;
        }
    }

    private function startJob(JobProcessing $event): void
    {
        if ($event->connectionName === 'sync') {
            return;
        }

        $this->frames[] = [
            'kind' => 'job',
            'name' => $event->job->resolveName(),
            'id' => $this->jobId($event),
            'started' => $this->now(),
            'ms' => 0.0,
            'calls' => 0,
        ];
    }

    private function finishJob(JobProcessed|JobFailed|JobReleasedAfterException $event): void
    {
        if ($event->connectionName === 'sync') {
            return;
        }

        $top = array_key_last($this->frames);

        if ($top === null || $this->frames[$top]['kind'] !== 'job' || $this->frames[$top]['id'] !== $this->jobId($event)) {
            return;
        }

        $this->finishFrame();
    }

    private function startCommand(CommandStarting $event): void
    {
        if ($event->command === null || in_array($event->command, self::WORKERS, true)) {
            return;
        }

        $this->frames[] = [
            'kind' => 'command',
            'name' => $event->command,
            'id' => null,
            'started' => $this->now(),
            'ms' => 0.0,
            'calls' => 0,
        ];
    }

    private function finishCommand(CommandFinished $event): void
    {
        $top = array_key_last($this->frames);

        if ($top === null || $this->frames[$top]['kind'] !== 'command' || $this->frames[$top]['name'] !== $event->command) {
            return;
        }

        $this->finishFrame();
    }

    /**
     * Record the innermost job or command, and count its Teamleader time
     * toward whatever runs it.
     */
    private function finishFrame(): void
    {
        $frame = array_pop($this->frames);

        if ($frame === null) {
            return;
        }

        $this->add($frame['ms'], $frame['calls']);

        $this->store($frame['kind'], $frame['name'], null, $frame['ms'], $frame['calls'], $this->now() - $frame['started']);
    }

    private function recordRequest(Carbon $startedAt, Request $request): void
    {
        [$ms, $calls] = [$this->ms, $this->calls];
        $this->ms = 0.0;
        $this->calls = 0;

        if ($calls === 0 && $ms <= 0.0) {
            return;
        }

        if (! $request->route() instanceof Route) {
            return;
        }

        [$path, $via] = $this->resolveRoutePath($request);

        $this->store('request', $request->method().' '.$path, $via, $ms, $calls, (float) $startedAt->diffInMilliseconds());
    }

    private function store(string $kind, string $name, ?string $via, float $ms, int $calls, float $duration): void
    {
        if ($calls === 0 && $ms <= 0.0) {
            return;
        }

        $timestamp = CarbonImmutable::now()->getTimestamp();

        $this->pulse->lazy(function () use ($kind, $name, $via, $ms, $calls, $duration, $timestamp) {
            if ($this->shouldIgnore($this->ignoreSubject($kind, $name)) || ! $this->shouldSample()) {
                return;
            }

            $key = Entries::key([$kind, $name, $via]);

            // Never more Teamleader time than the whole: rounding, and a
            // duration measured slightly differently from the calls
            $duration = (int) round(max($duration, $ms));

            $this->pulse->record(Entries::CALLER_TIME, $key, (int) round($ms), $timestamp)->count()->sum()->avg()->max();
            $this->pulse->record(Entries::CALLER_DURATION, $key, $duration, $timestamp)->sum()->max();
            $this->pulse->record(Entries::CALLER_CALLS, $key, $calls, $timestamp)->sum()->max();
        });
    }

    /**
     * What `ignore` patterns are matched against: the path for a request
     * ("GET /admin" → "/admin"), the class or command name otherwise.
     */
    private function ignoreSubject(string $kind, string $name): string
    {
        return $kind === 'request' ? (string) substr($name, (int) strpos($name, ' ') + 1) : $name;
    }

    private function jobId(JobProcessing|JobProcessed|JobFailed|JobReleasedAfterException $event): string
    {
        return (string) ($event->job->uuid() ?? $event->job->getJobId());
    }

    /**
     * Milliseconds, monotonic.
     */
    private function now(): float
    {
        return hrtime(true) / 1e6;
    }
}
