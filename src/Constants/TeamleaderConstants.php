<?php

namespace McoreServices\TeamleaderSDK\Constants;

/**
 * Teamleader SDK Constants
 *
 * Central location for all magic numbers and configuration defaults.
 * Using constants improves maintainability and makes values self-documenting.
 */
class TeamleaderConstants
{
    /**
     * API Configuration
     */
    public const DEFAULT_API_VERSION = '2023-09-26';
    public const BASE_URL = 'https://api.focus.teamleader.eu';
    public const AUTH_URL = 'https://focus.teamleader.eu';

    /**
     * HTTP Timeouts (in seconds)
     */
    public const DEFAULT_TIMEOUT = 30;
    public const DEFAULT_CONNECT_TIMEOUT = 10;
    public const DEFAULT_READ_TIMEOUT = 25;
    public const MIN_TIMEOUT = 5;
    public const MAX_TIMEOUT = 300;

    /**
     * Retry Configuration
     */
    public const DEFAULT_RETRY_ATTEMPTS = 3;
    public const MAX_RETRY_ATTEMPTS = 10;
    public const DEFAULT_RETRY_DELAY = 1000; // milliseconds
    public const MAX_RETRY_DELAY = 30000; // 30 seconds max

    /**
     * Rate Limiting
     */
    public const RATE_LIMIT_PER_MINUTE = 200;
    public const RATE_LIMIT_WINDOW = 60; // seconds
    public const THROTTLE_THRESHOLD = 0.7; // Start throttling at 70%
    public const AGGRESSIVE_THROTTLE_THRESHOLD = 0.9; // Aggressive at 90%

    /**
     * Pagination
     */
    public const DEFAULT_PAGE_SIZE = 20;
    public const MIN_PAGE_SIZE = 1;
    public const MAX_PAGE_SIZE = 100;
    public const DEFAULT_PAGE_NUMBER = 1;

    /**
     * Sideloading
     */
    public const MAX_INCLUDES_PER_REQUEST = 10;

    /**
     * Caching (in seconds)
     */
    public const DEFAULT_CACHE_TTL = 3600; // 1 hour
    public const MIN_CACHE_TTL = 60; // 1 minute
    public const MAX_CACHE_TTL = 86400; // 24 hours

    /**
     * Token Management
     */
    public const TOKEN_EXPIRY_BUFFER = 300; // Refresh 5 minutes before expiry
    public const TOKEN_CACHE_TTL = 3600; // Cache tokens for 1 hour
    public const TOKEN_LOCK_TIMEOUT = 30; // Lock timeout for refresh

    /**
     * HTTP Status Codes
     */
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;

    /**
     * OAuth Grant Types
     */
    public const GRANT_TYPE_AUTH_CODE = 'authorization_code';
    public const GRANT_TYPE_REFRESH = 'refresh_token';

    /**
     * Cache Keys
     */
    public const CACHE_KEY_TOKENS = 'teamleader_tokens';
    public const CACHE_KEY_RATE_LIMIT = 'teamleader_rate_limit';
    public const CACHE_KEY_CONFIG_VALIDATION = 'teamleader_config_validation';

    /**
     * Log Channels
     */
    public const LOG_CHANNEL_DEFAULT = 'default';
    public const LOG_CHANNEL_TEAMLEADER = 'teamleader';

    /**
     * Date Formats
     */
    public const DATE_FORMAT_ISO8601 = 'Y-m-d\TH:i:sP';
    public const DATE_FORMAT_SIMPLE = 'Y-m-d';
    public const DATE_FORMAT_DATETIME = 'Y-m-d H:i:s';
}
