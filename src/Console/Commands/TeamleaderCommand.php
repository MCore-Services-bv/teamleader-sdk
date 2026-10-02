<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use Throwable;

/**
 * Shared by the teamleader:* data commands: the --connection option, and
 * errors printed as one readable line instead of a stack trace.
 */
abstract class TeamleaderCommand extends Command
{
    /** Appended to every data command's signature */
    protected const CONNECTION_OPTION = '{--connection= : The Teamleader connection (default: the default connection)}';

    /** For the list and export commands */
    protected const SUBJECT_OPTION = '{--subject= : type:uuid, e.g. deal:3f6c… — required for notes, emailTracking and files}';

    /** Resources whose list endpoint refuses to run without a subject filter */
    protected const SUBJECT_REQUIRED = [
        'notes' => 'notes.list',
        'emailTracking' => 'emailTracking.list',
        'files' => 'files.list',
    ];

    /**
     * The filters with --subject added as `subject: {type, id}`.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException When --subject is malformed, or missing where the endpoint requires one
     */
    protected function withSubject(string $resource, array $filters): array
    {
        $subject = $this->option('subject');

        if (is_string($subject) && $subject !== '') {
            [$type, $id] = array_pad(explode(':', $subject, 2), 2, '');

            if ($type === '' || $id === '') {
                throw new InvalidArgumentException("--subject takes type:uuid, e.g. --subject=deal:3f6c…; got '{$subject}'.");
            }

            $filters['subject'] = ['type' => $type, 'id' => $id];
        }

        $endpoint = self::SUBJECT_REQUIRED[$resource] ?? null;

        if ($endpoint !== null && ! isset($filters['subject']) && ! isset($filters['subject.type'])) {
            throw new InvalidArgumentException(
                "{$endpoint} only lists records of one subject. Add --subject=<type>:<uuid>, e.g. --subject=deal:3f6c…"
            );
        }

        return $filters;
    }

    protected function sdk(): TeamleaderSDK
    {
        $name = $this->option('connection');
        $manager = app(ConnectionManager::class);

        if (! is_string($name) || $name === '') {
            // Only named connections: say so, instead of the default's missing credentials
            if (! $manager->hasDefaultConnection()) {
                throw new InvalidArgumentException($manager->missingDefaultMessage());
            }

            return $manager->connection();
        }

        return $manager->connection($name);
    }

    /**
     * A response that is an error array — throw_exceptions off — as the
     * exception it stands for, so the command reports it instead of printing
     * an empty table.
     *
     * @throws TeamleaderException
     */
    protected function ensureSuccessful(array $response): array
    {
        if (! empty($response['error'])) {
            $status = isset($response['status_code']) ? (int) $response['status_code'] : null;

            throw new TeamleaderException(
                (string) ($response['message'] ?? 'The request failed'),
                (int) $status, null, [], $status, (array) ($response['errors'] ?? [])
            );
        }

        return $response;
    }

    /**
     * Run the command body, turning the errors a user can act on into a message.
     *
     * @param  callable(): int  $body
     */
    protected function guarded(callable $body): int
    {
        try {
            return $body();
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::INVALID;
        } catch (TeamleaderException $e) {
            $status = $e->getStatusCode() ? " (HTTP {$e->getStatusCode()})" : '';
            $this->error($e->getMessage().$status);

            foreach (array_slice($e->getAllErrors(), 1) as $error) {
                $this->line("  {$error}");
            }

            return self::FAILURE;
        } catch (Throwable $e) {
            if ($this->getOutput()->isVerbose()) {
                throw $e;
            }

            $this->error($e->getMessage().' (run with -v for details)');

            return self::FAILURE;
        }
    }
}
