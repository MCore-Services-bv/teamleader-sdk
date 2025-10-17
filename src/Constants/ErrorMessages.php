<?php

namespace McoreServices\TeamleaderSDK\Constants;

/**
 * Standardized Error Messages
 *
 * Provides consistent, clear, actionable error messages throughout the SDK.
 * Each message follows the format: [Context] Problem. Resolution hint.
 */
class ErrorMessages
{
    /**
     * Authentication Errors
     */
    public const AUTH_NO_TOKEN = 'Authentication required. No access token available. Please connect to Teamleader using the OAuth flow.';
    public const AUTH_TOKEN_EXPIRED = 'Authentication expired. Access token is no longer valid. Please reconnect to Teamleader.';
    public const AUTH_TOKEN_INVALID = 'Authentication failed. The provided access token is invalid. Please reconnect to Teamleader.';
    public const AUTH_REFRESH_FAILED = 'Token refresh failed. Unable to obtain new access token. Please reconnect to Teamleader.';
    public const AUTH_CALLBACK_FAILED = 'OAuth callback failed. Could not exchange authorization code for tokens. Please try again.';
    public const AUTH_NO_REFRESH_TOKEN = 'Token refresh not possible. No refresh token available. Please reconnect to Teamleader.';

    /**
     * Authorization Errors
     */
    public const AUTHORIZATION_INSUFFICIENT = 'Insufficient permissions. Your account does not have permission to perform this action.';
    public const AUTHORIZATION_SCOPE_MISSING = 'Missing required scope. This operation requires additional OAuth permissions.';
    public const AUTHORIZATION_FEATURE_DISABLED = 'Feature not available. This feature is not enabled for your Teamleader account.';

    /**
     * Validation Errors
     */
    public const VALIDATION_FAILED = 'Validation failed. The provided data does not meet Teamleader\'s requirements.';
    public const VALIDATION_REQUIRED_FIELD = 'Required field missing. Please provide all required fields.';
    public const VALIDATION_INVALID_FORMAT = 'Invalid format. Please check the data format and try again.';
    public const VALIDATION_INVALID_UUID = 'Invalid UUID format. The provided ID is not a valid UUID.';
    public const VALIDATION_INVALID_DATE = 'Invalid date format. Please use ISO 8601 format (YYYY-MM-DD).';
    public const VALIDATION_INVALID_EMAIL = 'Invalid email format. Please provide a valid email address.';

    /**
     * Resource Errors
     */
    public const RESOURCE_NOT_FOUND = 'Resource not found. The requested resource does not exist or has been deleted.';
    public const RESOURCE_ALREADY_EXISTS = 'Resource already exists. A resource with these details already exists.';
    public const RESOURCE_CONFLICT = 'Resource conflict. The operation conflicts with the current state of the resource.';

    /**
     * Rate Limiting Errors
     */
    public const RATE_LIMIT_EXCEEDED = 'Rate limit exceeded. Too many requests. Please wait before trying again.';
    public const RATE_LIMIT_APPROACHING = 'Rate limit warning. Approaching request limit. Consider reducing request frequency.';

    /**
     * Server Errors
     */
    public const SERVER_ERROR = 'Teamleader server error. The server encountered an error. Please try again later.';
    public const SERVER_UNAVAILABLE = 'Service unavailable. Teamleader is temporarily unavailable. Please try again later.';
    public const SERVER_TIMEOUT = 'Request timeout. The server took too long to respond. Please try again.';
    public const SERVER_GATEWAY_ERROR = 'Gateway error. Unable to reach Teamleader servers. Please try again later.';

    /**
     * Connection Errors
     */
    public const CONNECTION_FAILED = 'Connection failed. Unable to connect to Teamleader. Please check your internet connection.';
    public const CONNECTION_TIMEOUT = 'Connection timeout. Failed to establish connection. Please check your network.';
    public const CONNECTION_DNS_FAILED = 'DNS resolution failed. Unable to resolve Teamleader domain. Please check your network configuration.';
    public const CONNECTION_SSL_FAILED = 'SSL connection failed. Unable to establish secure connection. Please check your SSL configuration.';

    /**
     * Configuration Errors
     */
    public const CONFIG_MISSING_REQUIRED = 'Configuration error. Missing required configuration: %s. Please set in config/teamleader.php or .env';
    public const CONFIG_INVALID_VALUE = 'Configuration error. Invalid value for %s. Please check your configuration.';
    public const CONFIG_INVALID_URL = 'Configuration error. Invalid URL format for %s. Please provide a valid URL.';
    public const CONFIG_VALIDATION_FAILED = 'Configuration validation failed. Please run: php artisan teamleader:config:validate';

    /**
     * API Errors
     */
    public const API_BAD_REQUEST = 'Bad request. The request was malformed or contains invalid data.';
    public const API_UNKNOWN_ERROR = 'Unknown API error. An unexpected error occurred. Please contact support.';
    public const API_RESPONSE_INVALID = 'Invalid API response. Unable to parse response from Teamleader.';

    /**
     * Helper method to format messages with parameters
     */
    public static function format(string $message, ...$params): string
    {
        return sprintf($message, ...$params);
    }
}
