<?php

namespace McoreServices\TeamleaderSDK\Traits;

/**
 * Trait for sanitizing sensitive data before logging
 *
 * Ensures that tokens, passwords, credit cards, and other sensitive
 * information is never exposed in log files.
 *
 * Usage:
 * ```php
 * use SanitizesLogData;
 *
 * $sanitized = $this->sanitizeForLog($data);
 * Log::info('API request', $sanitized);
 * ```
 */
trait SanitizesLogData
{
    /**
     * List of sensitive keys that should be redacted in logs
     *
     * @var array
     */
    protected array $sensitiveKeys = [
        // OAuth & Authentication
        'access_token',
        'refresh_token',
        'token',
        'bearer',
        'authorization',
        'api_key',
        'apikey',
        'api_secret',
        'secret',

        // Passwords & Credentials
        'password',
        'passwd',
        'pwd',
        'pass',
        'passphrase',
        'secret_key',
        'private_key',

        // Payment Information
        'credit_card',
        'creditcard',
        'card_number',
        'cvv',
        'cvc',
        'card_cvv',
        'card_cvc',
        'exp_date',
        'expiry_date',

        // Personal Information
        'ssn',
        'social_security',
        'tax_id',
        'national_id',
        'passport',

        // Banking
        'iban',
        'account_number',
        'routing_number',
        'swift',
        'bic',

        // Other Sensitive
        'pin',
        'otp',
        'verification_code',
        'auth_code',
        'client_secret',
    ];

    /**
     * Patterns for detecting sensitive data in values
     *
     * @var array
     */
    protected array $sensitivePatterns = [
        // Credit card numbers (basic pattern)
        '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/',

        // Email addresses (optional, comment out if you want emails in logs)
        // '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',

        // Bearer tokens
        '/Bearer\s+[A-Za-z0-9\-._~+\/]+/',

        // UUIDs that might be sensitive identifiers
        // Uncomment if you want to hide all UUIDs
        // '/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i',
    ];

    /**
     * Replacement text for sensitive data
     *
     * @var string
     */
    protected string $redactedText = '***REDACTED***';

    /**
     * Sanitize data for safe logging
     *
     * Recursively processes arrays and objects to redact sensitive information.
     * Preserves data structure while removing sensitive values.
     *
     * @param mixed $data The data to sanitize
     * @param int $depth Current recursion depth (prevents infinite loops)
     * @return mixed Sanitized data
     */
    protected function sanitizeForLog($data, int $depth = 0)
    {
        // Prevent infinite recursion
        if ($depth > 10) {
            return '[MAX_DEPTH_REACHED]';
        }

        // Handle null and scalar types
        if ($data === null || is_scalar($data)) {
            return $this->sanitizeScalar($data);
        }

        // Handle arrays
        if (is_array($data)) {
            return $this->sanitizeArray($data, $depth);
        }

        // Handle objects
        if (is_object($data)) {
            return $this->sanitizeObject($data, $depth);
        }

        return $data;
    }

    /**
     * Sanitize scalar values (strings, numbers, booleans)
     *
     * @param mixed $value
     * @return mixed
     */
    protected function sanitizeScalar($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Check against sensitive patterns
        foreach ($this->sensitivePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return $this->redactedText;
            }
        }

        return $value;
    }

    /**
     * Sanitize array data
     *
     * @param array $data
     * @param int $depth
     * @return array
     */
    protected function sanitizeArray(array $data, int $depth): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Check if key is sensitive
            if ($this->isSensitiveKey($key)) {
                $sanitized[$key] = $this->redactedText;
                continue;
            }

            // Recursively sanitize nested structures
            $sanitized[$key] = $this->sanitizeForLog($value, $depth + 1);
        }

        return $sanitized;
    }

    /**
     * Sanitize object data
     *
     * @param object $data
     * @param int $depth
     * @return array|string
     */
    protected function sanitizeObject($data, int $depth)
    {
        // Convert to array if possible
        if (method_exists($data, 'toArray')) {
            return $this->sanitizeArray($data->toArray(), $depth);
        }

        // Use object_vars for stdClass and similar
        $vars = get_object_vars($data);
        if (!empty($vars)) {
            return $this->sanitizeArray($vars, $depth);
        }

        // For other objects, just return class name
        return '[OBJECT:' . get_class($data) . ']';
    }

    /**
     * Check if a key name is sensitive
     *
     * @param string $key
     * @return bool
     */
    protected function isSensitiveKey($key): bool
    {
        $key = strtolower($key);

        foreach ($this->sensitiveKeys as $sensitiveKey) {
            // Exact match
            if ($key === strtolower($sensitiveKey)) {
                return true;
            }

            // Contains match (e.g., "user_password" contains "password")
            if (str_contains($key, strtolower($sensitiveKey))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add custom sensitive keys
     *
     * @param array $keys
     * @return self
     */
    protected function addSensitiveKeys(array $keys): self
    {
        $this->sensitiveKeys = array_merge($this->sensitiveKeys, $keys);
        return $this;
    }

    /**
     * Add custom sensitive patterns
     *
     * @param array $patterns Regular expression patterns
     * @return self
     */
    protected function addSensitivePatterns(array $patterns): self
    {
        $this->sensitivePatterns = array_merge($this->sensitivePatterns, $patterns);
        return $this;
    }

    /**
     * Sanitize headers for logging
     *
     * Special handling for HTTP headers which often contain sensitive data
     *
     * @param array $headers
     * @return array
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'x-api-key',
            'x-auth-token',
            'cookie',
            'set-cookie',
        ];

        $sanitized = [];

        foreach ($headers as $name => $value) {
            $lowerName = strtolower($name);

            if (in_array($lowerName, $sensitiveHeaders)) {
                $sanitized[$name] = $this->redactedText;
            } else {
                $sanitized[$name] = $value;
            }
        }

        return $sanitized;
    }
}
