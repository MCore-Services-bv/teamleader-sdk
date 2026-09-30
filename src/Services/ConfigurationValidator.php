<?php

namespace McoreServices\TeamleaderSDK\Services;

class ConfigurationValidator
{
    private array $errors = [];

    private array $warnings = [];

    public function validate(): ValidationResult
    {
        $this->errors = [];
        $this->warnings = [];

        $this->validateRequired();
        $this->validateUrls();
        $this->validateEnvironment();
        $this->validateFeatureConfig();
        $this->validateDatabaseConnection();
        $this->validatePhpExtensions();

        return new ValidationResult($this->errors, $this->warnings);
    }

    /**
     * Validate required configuration values
     */
    private function validateRequired(): void
    {
        $required = [
            'teamleader.client_id' => 'TEAMLEADER_CLIENT_ID',
            'teamleader.client_secret' => 'TEAMLEADER_CLIENT_SECRET',
            'teamleader.redirect_uri' => 'TEAMLEADER_REDIRECT_URI',
        ];

        foreach ($required as $config => $env) {
            $value = config($config);

            if (empty($value)) {
                $this->errors[] = "Missing required configuration: {$env}";

                continue;
            }

            // Additional validation for specific configs
            if ($config === 'teamleader.client_id' && strlen($value) < 10) {
                $this->warnings[] = "Client ID seems too short - please verify it's correct";
            }

            if ($config === 'teamleader.client_secret' && strlen($value) < 20) {
                $this->warnings[] = "Client secret seems too short - please verify it's correct";
            }
        }
    }

    /**
     * Validate URL formats
     */
    private function validateUrls(): void
    {
        $urls = [
            'teamleader.redirect_uri' => 'Redirect URI',
            'teamleader.base_url' => 'Base URL',
            'teamleader.auth_url' => 'Auth URL',
        ];

        foreach ($urls as $config => $name) {
            $url = config($config);

            if (empty($url)) {
                // Only redirect_uri is required
                if ($config === 'teamleader.redirect_uri') {
                    $this->errors[] = "Missing {$name}";
                }

                continue;
            }

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                $this->errors[] = "Invalid {$name} format: {$url}";

                continue;
            }

            // Additional URL validation
            if ($config === 'teamleader.redirect_uri') {
                if (! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'])) {
                    $this->errors[] = 'Redirect URI must use http or https scheme';
                }

                if (app()->environment('production') && parse_url($url, PHP_URL_SCHEME) === 'http') {
                    $this->warnings[] = 'Using HTTP redirect URI in production is not recommended - use HTTPS';
                }
            }
        }
    }

    /**
     * Validate environment-specific settings
     */
    private function validateEnvironment(): void
    {
        $environment = app()->environment();

        if ($environment === 'production' && config('app.debug')) {
            $this->warnings[] = 'Laravel debug mode is enabled in production - this can expose sensitive information';
        }

        // Check for localhost in production
        if ($environment === 'production') {
            $redirectUri = config('teamleader.redirect_uri');
            if (str_contains($redirectUri, 'localhost') || str_contains($redirectUri, '127.0.0.1')) {
                $this->errors[] = 'Redirect URI contains localhost/127.0.0.1 in production environment';
            }
        }
    }

    /**
     * Validate feature configuration
     */
    private function validateFeatureConfig(): void
    {
        // Validate API settings
        $timeout = config('teamleader.api.timeout');
        if ($timeout && ($timeout < 5 || $timeout > 300)) {
            $this->warnings[] = 'API timeout should be between 5 and 300 seconds';
        }

        $retryAttempts = config('teamleader.api.retry_attempts');
        if ($retryAttempts && ($retryAttempts < 1 || $retryAttempts > 10)) {
            $this->warnings[] = 'Retry attempts should be between 1 and 10';
        }

        $retryDelay = config('teamleader.api.retry_delay');
        if ($retryDelay !== null && ($retryDelay < 0 || $retryDelay > 30000)) {
            $this->warnings[] = 'Retry delay should be between 0 and 30000 milliseconds';
        }

        // Validate error handling
        if (config('teamleader.error_handling.throw_exceptions') === null) {
            $this->warnings[] = 'Exception throwing behavior is not explicitly configured';
        }
    }

    /**
     * Validate database connection for token storage
     */
    private function validateDatabaseConnection(): void
    {
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->errors[] = "Database connection failed: {$e->getMessage()}";

            return;
        }

        // Check if migrations might be needed
        try {
            $hasTable = \DB::getSchemaBuilder()->hasTable('teamleader_tokens');
            if (! $hasTable) {
                $this->warnings[] = 'Teamleader tokens table does not exist - token storage will be created automatically';
            }
        } catch (\Exception $e) {
            $this->warnings[] = "Could not check tokens table: {$e->getMessage()}";
        }
    }

    /**
     * Validate required PHP extensions
     */
    private function validatePhpExtensions(): void
    {
        $required = ['curl', 'json', 'openssl', 'mbstring'];
        $recommended = ['redis'];

        foreach ($required as $ext) {
            if (! extension_loaded($ext)) {
                $this->errors[] = "Required PHP extension '{$ext}' is not loaded";
            }
        }

        foreach ($recommended as $ext) {
            if (! extension_loaded($ext)) {
                $this->warnings[] = "Recommended PHP extension '{$ext}' is not loaded (the rate limiter needs Redis — through this extension or predis)";
            }
        }

        // Composer enforces this; the check covers a vendor directory copied
        // between machines.
        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            $this->errors[] = 'PHP '.PHP_VERSION.' is not supported. The SDK requires PHP 8.4 or higher';
        }
    }

    /**
     * Validate Laravel version compatibility
     */
    private function validateLaravelVersion(): void
    {
        $laravelVersion = app()->version();

        if (version_compare($laravelVersion, '12.0', '<')) {
            $this->errors[] = "Laravel version {$laravelVersion} is not supported. Minimum version is 12.0";
        }
    }

    /**
     * Get configuration suggestions
     */
    public function getSuggestions(): array
    {
        $suggestions = [];

        // Reliability suggestions
        $timeout = config('teamleader.api.timeout');
        if (! $timeout || $timeout < 30) {
            $suggestions[] = [
                'type' => 'reliability',
                'title' => 'Increase API Timeout',
                'description' => 'Consider increasing API timeout for better reliability',
                'config' => 'teamleader.api.timeout = 30',
            ];
        }

        return $suggestions;
    }

    /**
     * Generate configuration report
     */
    public function generateReport(): array
    {
        $validation = $this->validate();

        return [
            'overall_status' => $validation->isValid() ? 'valid' : 'invalid',
            'has_errors' => ! empty($this->errors),
            'has_warnings' => ! empty($this->warnings),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'suggestions' => $this->getSuggestions(),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'configuration_summary' => [
                'client_id' => ! empty(config('teamleader.client_id')) ? 'configured' : 'missing',
                'client_secret' => ! empty(config('teamleader.client_secret')) ? 'configured' : 'missing',
                'redirect_uri' => config('teamleader.redirect_uri', 'missing'),
                'rate_limiting_enabled' => (bool) config('teamleader.rate_limiting.enabled', true),
            ],
            'validated_at' => now()->toISOString(),
        ];
    }
}

class ValidationResult
{
    public function __construct(
        public readonly array $errors,
        public readonly array $warnings
    ) {}

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    public function getAllIssues(): array
    {
        return array_merge(
            array_map(fn ($error) => ['type' => 'error', 'message' => $error], $this->errors),
            array_map(fn ($warning) => ['type' => 'warning', 'message' => $warning], $this->warnings)
        );
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    public function getSummary(): string
    {
        if ($this->isValid()) {
            return $this->hasWarnings()
                ? "Configuration is valid with {$this->getWarningCount()} warning(s)"
                : 'Configuration is valid';
        }

        return "Configuration has {$this->getErrorCount()} error(s)".
            ($this->hasWarnings() ? " and {$this->getWarningCount()} warning(s)" : '');
    }
}
