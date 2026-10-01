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

    protected function sdk(): TeamleaderSDK
    {
        $name = $this->option('connection');

        return app(ConnectionManager::class)->connection(is_string($name) && $name !== '' ? $name : null);
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
