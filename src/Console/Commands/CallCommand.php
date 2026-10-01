<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use InvalidArgumentException;
use JsonException;
use McoreServices\TeamleaderSDK\Console\Support\WriteGuard;

/**
 * A raw API call, for endpoints the SDK does not wrap yet. No validation
 * happens here — the body goes as given — so only reading endpoints run
 * without --write.
 */
class CallCommand extends TeamleaderCommand
{
    /** Actions that only read: users.me, companies.info, deals.list, files.download, … */
    private const READING_ACTIONS = ['list', 'info', 'me', 'download', 'count'];

    protected $signature = 'teamleader:call
                            {endpoint : e.g. companies.info or projects-v2/projects.list}
                            {--data= : The JSON body, or - to read it from stdin}
                            {--write : Allow an endpoint that changes data}
                            {--force : Do not ask for confirmation (required in production)}
                            '.self::CONNECTION_OPTION;

    protected $description = 'Call any Teamleader endpoint with a raw JSON body — read-only unless --write';

    public function handle(): int
    {
        return $this->guarded(function (): int {
            $endpoint = (string) $this->argument('endpoint');

            if (! preg_match('/^[A-Za-z0-9\/_-]+\.[A-Za-z0-9_-]+$/', $endpoint)) {
                throw new InvalidArgumentException("'{$endpoint}' is not an endpoint name. Use resource.action, e.g. companies.info.");
            }

            $body = $this->body();
            $action = substr($endpoint, strrpos($endpoint, '.') + 1);

            if (! in_array($action, self::READING_ACTIONS, true)
                && ! (new WriteGuard($this))->allows("{$endpoint} (raw, unvalidated)", 1)) {
                return self::FAILURE;
            }

            $response = $this->ensureSuccessful($this->sdk()->request('POST', $endpoint, $body));
            unset($response['headers']);

            $this->line((string) json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        });
    }

    /**
     * @return array<mixed>
     */
    private function body(): array
    {
        $data = $this->option('data');

        if ($data === null || $data === '') {
            return [];
        }

        if ($data === '-') {
            $data = (string) stream_get_contents(STDIN);
        }

        try {
            $body = json_decode((string) $data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException("--data is not valid JSON: {$e->getMessage()}");
        }

        if (! is_array($body)) {
            throw new InvalidArgumentException('--data must be a JSON object.');
        }

        return $body;
    }
}
