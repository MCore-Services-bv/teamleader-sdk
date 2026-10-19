<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Pulse;

use JsonException;

/**
 * The Pulse entry types the SDK records, and how their keys are built.
 *
 * Kept in one place so the recorders and the cards cannot drift apart. Every
 * type starts with `teamleader_`, so `pulse:clear --type=…` and queries on
 * `pulse_entries` can find them.
 *
 * @internal
 */
final class Entries
{
    /** One per API answer. Key [connection, endpoint]; value: duration in ms */
    public const REQUEST = 'teamleader_request';

    /** An answer slower than the threshold. Key [connection, endpoint]; value: ms */
    public const SLOW_REQUEST = 'teamleader_slow_request';

    /** One per API answer. Key: connection; value: duration in ms */
    public const CONNECTION_REQUEST = 'teamleader_connection_request';

    /** A request that did not succeed. Key [connection, endpoint, reason] */
    public const FAILURE = 'teamleader_failure';

    /** A request that did not succeed. Key: connection */
    public const CONNECTION_FAILURE = 'teamleader_connection_failure';

    /** A failure that never reached the API (no answer, no tokens, rate-limit wait ran out). Key: connection */
    public const CONNECTION_UNANSWERED = 'teamleader_connection_unanswered';

    /** The SDK held a request back for the rate limit. Key: connection; value: ms waited */
    public const RATE_LIMIT_WAIT = 'teamleader_rate_limit_wait';

    /** The rate-limit wait ran out and the request was refused. Key: connection */
    public const RATE_LIMIT_GAVE_UP = 'teamleader_rate_limit_gave_up';

    /** The access token was renewed. Key: connection */
    public const TOKEN_REFRESHED = 'teamleader_token_refreshed';

    /** Renewing the access token failed. Key: connection */
    public const TOKEN_REFRESH_FAILED = 'teamleader_token_refresh_failed';

    /** Value (not an entry): the connection's last known state. Key: connection name, ungrouped */
    public const CONNECTION_STATE = 'teamleader_connection_state';

    /** Teamleader time of one request, job or command. Key [kind, name, via]; value: ms */
    public const CALLER_TIME = 'teamleader_caller_time';

    /** Total time of that request, job or command. Same key; value: ms */
    public const CALLER_DURATION = 'teamleader_caller_duration';

    /** API calls it made. Same key; value: number of calls */
    public const CALLER_CALLS = 'teamleader_caller_calls';

    /** Connection state: connected and working */
    public const STATE_OK = 'ok';

    /** Connection state: Teamleader refused the refresh token; someone must connect again */
    public const STATE_REAUTHORIZE = 'reauthorize';

    /** Connection state: no tokens stored; the account was never connected or was disconnected */
    public const STATE_NOT_CONNECTED = 'not_connected';

    /**
     * Every entry type, for `Pulse::purge()` and tests.
     *
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::REQUEST,
            self::SLOW_REQUEST,
            self::CONNECTION_REQUEST,
            self::FAILURE,
            self::CONNECTION_FAILURE,
            self::CONNECTION_UNANSWERED,
            self::RATE_LIMIT_WAIT,
            self::RATE_LIMIT_GAVE_UP,
            self::TOKEN_REFRESHED,
            self::TOKEN_REFRESH_FAILED,
            self::CONNECTION_STATE,
            self::CALLER_TIME,
            self::CALLER_DURATION,
            self::CALLER_CALLS,
        ];
    }

    /**
     * How a failure reason reads in the cards.
     */
    public static function reasonLabel(string $reason): string
    {
        return match ($reason) {
            self::STATE_REAUTHORIZE => 'needs reconnecting',
            self::STATE_NOT_CONNECTED => 'not connected',
            'rate_limit_wait' => 'rate-limit wait ran out',
            'network' => 'no answer',
            default => 'HTTP '.$reason,
        };
    }

    /**
     * How a connection state reads in the cards.
     */
    public static function stateLabel(string $state): string
    {
        return match ($state) {
            self::STATE_REAUTHORIZE => 'Needs reconnecting',
            self::STATE_NOT_CONNECTED => 'Not connected',
            default => 'Connected',
        };
    }

    /**
     * @param  list<string|int|null>  $parts
     *
     * @throws JsonException
     */
    public static function key(array $parts): string
    {
        return json_encode($parts, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * The parts of a key built by key(); an empty list for anything else.
     *
     * @return list<mixed>
     */
    public static function parts(string $key): array
    {
        $parts = json_decode($key, true);

        return is_array($parts) && array_is_list($parts) ? $parts : [];
    }
}
