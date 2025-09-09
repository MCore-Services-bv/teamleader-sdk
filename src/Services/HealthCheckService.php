<?php

namespace McoreServices\TeamleaderSDK\Services;

use McoreServices\TeamleaderSDK\TeamleaderSDK;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthCheckService
{
    private TeamleaderSDK $sdk;
    private ConfigurationValidator $configValidator;

    public function __construct(TeamleaderSDK $sdk, ConfigurationValidator $configValidator)
    {
        $this->sdk = $sdk;
        $this->configValidator = $configValidator;
    }

    /**
     * Perform comprehensive health check
     */
    public function check(): HealthCheckResult
    {
        $checks = [
            'configuration' => $this->checkConfiguration(),
            'authentication' => $this->checkAuthentication(),
            'api_connectivity' => $this->checkApiConnectivity(),
            'rate_limits' => $this->checkRateLimits(),
            'token_status' => $this->checkTokenStatus(),
            'dependencies' => $this->checkDependencies(),
            'database_connection' => $this->checkDatabaseConnection(),
            'cache_system' => $this->checkCacheSystem(),
            'error_handling' => $this->checkErrorHandling()
        ];

        return new HealthCheckResult($checks);
    }

    /**
     * Check configuration validity
     */
    private function checkConfiguration(): array
    {
        try {
            $validation = $this->configValidator->validate();

            $details = [
                'is_valid' => $validation->isValid(),
                'error_count' => $validation->getErrorCount(),
                'warning_count' => $validation->getWarningCount(),
                'summary' => $validation->getSummary()
            ];

            if (!$validation->isValid()) {
                $details['errors'] = $validation->errors;
            }

            if ($validation->hasWarnings()) {
                $details['warnings'] = $validation->warnings;
            }

            return [
                'status' => $validation->isValid() ? 'healthy' : 'error',
                'details' => $details
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'details' => [
                    'error' => 'Configuration validation failed: ' . $e->getMessage(),
                    'exception' => get_class($e)
                ]
            ];
        }
    }

    /**
     * Check authentication status
     */
    private function checkAuthentication(): array
    {
        try {
            $isAuthenticated = $this->sdk->isAuthenticated();
            $tokenInfo = $this->sdk->getTokenService()->getTokenInfo();

            $status = 'healthy';
            if (!$isAuthenticated) {
                $status = 'warning';
            } elseif ($tokenInfo['needs_refresh']) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'details' => [
                    'is_authenticated' => $isAuthenticated,
                    'token_info' => $tokenInfo,
                    'token_source' => $tokenInfo['token_source'] ?? 'unknown',
                    'expires_in_minutes' => isset($tokenInfo['expires_in'])
                        ? round($tokenInfo['expires_in'] / 60, 1)
                        : null
                ]
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'details' => [
                    'error' => 'Authentication check failed: ' . $e->getMessage(),
                    'exception' => get_class($e)
                ]
            ];
        }
    }

    /**
     * Check API connectivity
     */
    private function checkApiConnectivity(): array
    {
        try {
            if (!$this->sdk->isAuthenticated()) {
                return [
                    'status' => 'skipped',
                    'details' => ['reason' => 'Not authenticated - cannot test connectivity']
                ];
            }

            $start = microtime(true);
            $response = $this->sdk->users()->me();
            $duration = round((microtime(true) - $start) * 1000, 2);

            $success = !isset($response['error']);

            $details = [
                'response_time_ms' => $duration,
                'success' => $success,
                'api_version' => $this->sdk->getApiVersion()
            ];

            if (!$success) {
                $details['error'] = $response['message'] ?? 'Unknown API error';
                $details['status_code'] = $response['status_code'] ?? null;
            }

            // Performance warnings
            if ($success && $duration > 3000) {
                $status = 'warning';
                $details['warning'] = 'API response time is slow (>3s)';
            } elseif ($success && $duration > 1000) {
                $status = 'warning';
                $details['warning'] = 'API response time is moderate (>1s)';
            } else {
                $status = $success ? 'healthy' : 'error';
            }

            return [
                'status' => $status,
                'details' => $details
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'details' => [
                    'error' => 'Connectivity test failed: ' . $e->getMessage(),
                    'exception' => get_class($e)
                ]
            ];
        }
    }

    /**
     * Check rate limit status
     */
    private function checkRateLimits(): array
    {
        try {
            $stats = $this->sdk->getRateLimitStats();
            $usage = $stats['usage_percentage'];

            $status = match (true) {
                $usage >= 95 => 'critical',
                $usage >= 85 => 'warning',
                $usage >= 70 => 'caution',
                default => 'healthy'
            };

            $details = array_merge($stats, [
                'status_description' => $this->getRateLimitDescription($status),
                'recommended_action' => $this->getRateLimitRecommendation($usage)
            ]);

            return [
                'status' => $status,
                'details' => $details
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'details' => [
                    'error' => 'Rate limit check failed: ' . $e->getMessage(),
                    'exception' => get_class($e)
                ]
            ];
        }
    }

    /**
     * Check token status and validity
     */
    private function checkTokenStatus(): array
    {
        try {
            $tokenInfo = $this->sdk->getTokenService()->getTokenInfo();

            $hasTokens = $tokenInfo['has_access_token'] && $tokenInfo['has_refresh_token'];
            $needsRefresh = $tokenInfo['needs_refresh'] ?? true;
            $expiresIn = $tokenInfo['expires_in'] ?? 0;

            $status = match (true) {
                !$hasTokens => 'error',
                $expiresIn < 300 => 'critical', // Less than 5 minutes
                $expiresIn < 900 => 'warning',  // Less than 15 minutes
                $needsRefresh => 'caution',
                default => 'healthy'
            };

            $details = $tokenInfo;
            $details['status_description'] = $this->getTokenStatusDescription($status);

            return [
                'status' => $status,
                'details' => $details
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'details' => [
                    'error' => 'Token status check failed: ' . $e->getMessage(),
                    'exception' => get_class($e)
                ]
            ];
        }
    }

    /**
     * Check system dependencies
     */
    private function checkDependencies(): array
    {
        $checks = [];
        $overallStatus = 'healthy';

        // Check required PHP extensions
        $requiredExtensions = ['curl', 'json', 'openssl', 'mbstring'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $checks['php_extension_' . $ext] = $loaded;
            if (!$loaded) {
                $overallStatus = 'error';
            }
        }

        // Check recommended PHP extensions
        $recommendedExtensions = ['redis', 'memcached'];
        foreach ($recommendedExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $checks['php_extension_' . $ext . '_optional'] = $loaded;
            if (!$loaded && $overallStatus === 'healthy') {
                $overallStatus = 'warning';
            }
        }

        // Check PHP version
        $phpVersion = PHP_VERSION;
        $checks['php_version'] = version_compare($phpVersion, '8.2.0', '>=');
        $checks['php_version_current'] = $phpVersion;

        // Check Laravel version
        $laravelVersion = app()->version();
        $checks['laravel_version'] = version_compare($laravelVersion, '10.0', '>=');
        $checks['laravel_version_current'] = $laravelVersion;

        // Check Guzzle availability
        $checks['guzzle_available'] = class_exists(\GuzzleHttp\Client::class);

        // Check Carbon availability
        $checks['carbon_available'] = class_exists(\Carbon\Carbon::class);

        return [
            'status' => $overallStatus,
            'details' => $checks
        ];
    }

    /**
     * Check database connection for token storage
     */
    private function checkDatabaseConnection(): array
    {
        try {
            // Test basic connection
            $pdo = DB::connection()->getPdo();
            $connectionOk = true;

            // Test token table accessibility
            $hasTokenTable = DB::getSchemaBuilder()->hasTable('teamleader_tokens');

            $details = [
                'connection_active' => $connectionOk,
                'has_tokens_table' => $hasTokenTable,
                'driver' => DB::connection()->getDriverName()
            ];

            if ($hasTokenTable) {
                // Count tokens
                try {
                    $tokenCount = DB::table('teamleader_tokens')->count();
                    $details['token_records'] = $tokenCount;
                } catch (Exception $e) {
                    $details['token_count_error'] = $e->getMessage();
                }
            }

            $status = $connectionOk ? 'healthy' : 'error';
            if (!$hasTokenTable && $status === 'healthy') {
                $status = 'warning';
                $details['warning'] = 'Tokens table will be created automatically when needed';
            }

            return [
                'status' => $status,
                'details' => $details
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'details' => [
                    'error' => 'Database check failed: ' . $e->getMessage(),
                    'exception' => get_class($e)
                ]
            ];
        }
    }

    /**
     * Check cache system functionality
     */
    private function checkCacheSystem(): array
    {
        try {
            $cacheEnabled = config('teamleader.caching.enabled', false);

            if (!$cacheEnabled) {
                return [
                    'status' => 'disabled',
                    'details' => ['message' => 'Caching is disabled in configuration']
                ];
            }

            $store = config('teamleader.caching.store', 'default');
            $testKey = 'teamleader_health_check_' . uniqid();
            $testValue = 'test_' . time();

            // Test cache write
            Cache::store($store)->put($testKey, $testValue, 60);

            // Test cache read
            $cachedValue = Cache::store($store)->get($testKey);

            // Test cache delete
            Cache::store($store)->forget($testKey);

            $working = $cachedValue === $testValue;

            return [
                'status' => $working ? 'healthy' : 'error',
                'details' => [
                    'enabled' => $cacheEnabled,
                    'store' => $store,
                    'working' => $working,
                    'driver' => config("cache.stores.{$store}.driver")
                ]
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'details' => [
                    'error' => 'Cache system check failed: ' . $e->getMessage(),
                    'exception' => get_class($e)
                ]
            ];
        }
    }

    /**
     * Check error handling configuration
     */
    private function checkErrorHandling(): array
    {
        try {
            $errorHandler = $this->sdk->getErrorHandler();
            $throwsExceptions = $errorHandler->getThrowExceptions();

            $details = [
                'throws_exceptions' => $throwsExceptions,
                'log_errors' => config('teamleader.error_handling.log_errors', true),
                'include_stack_trace' => config('teamleader.error_handling.include_stack_trace', false),
                'parse_teamleader_errors' => config('teamleader.error_handling.parse_teamleader_errors', true)
            ];

            // Test error handler with a mock error
            try {
                $mockResult = [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Health check test error'
                ];

                // This should not throw in non-exception mode
                $errorHandler->handleApiError($mockResult, 'health_check_test');
                $details['error_handler_working'] = true;

            } catch (Exception $e) {
                if ($throwsExceptions) {
                    $details['error_handler_working'] = true;
                    $details['exception_thrown_as_expected'] = true;
                } else {
                    $details['error_handler_working'] = false;
                    $details['unexpected_exception'] = $e->getMessage();
                }
            }

            return [
                'status' => 'healthy',
                'details' => $details
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'details' => [
                    'error' => 'Error handling check failed: ' . $e->getMessage(),
                    'exception' => get_class($e)
                ]
            ];
        }
    }

    /**
     * Get rate limit status description
     */
    private function getRateLimitDescription(string $status): string
    {
        return match ($status) {
            'healthy' => 'Rate limits are within safe range',
            'caution' => 'Rate limit usage is moderate - monitor closely',
            'warning' => 'Rate limit usage is high - consider throttling',
            'critical' => 'Rate limit usage is critical - immediate throttling required',
            default => 'Unknown status'
        };
    }

    /**
     * Get rate limit recommendation
     */
    private function getRateLimitRecommendation(float $usage): string
    {
        return match (true) {
            $usage >= 95 => 'Stop making requests and wait for reset',
            $usage >= 85 => 'Implement aggressive throttling',
            $usage >= 70 => 'Consider implementing request throttling',
            default => 'No action required'
        };
    }

    /**
     * Get token status description
     */
    private function getTokenStatusDescription(string $status): string
    {
        return match ($status) {
            'healthy' => 'Tokens are valid and not expiring soon',
            'caution' => 'Tokens are valid but may need refresh soon',
            'warning' => 'Tokens are expiring soon',
            'critical' => 'Tokens are expiring very soon',
            'error' => 'Tokens are missing or invalid',
            default => 'Unknown token status'
        };
    }

    /**
     * Get overall health score (0-100)
     */
    public function getHealthScore(): int
    {
        $result = $this->check();
        $checks = $result->getChecks();

        $scores = [];
        foreach ($checks as $check) {
            $scores[] = match ($check['status']) {
                'healthy' => 100,
                'caution' => 80,
                'warning' => 60,
                'critical' => 20,
                'error' => 0,
                'skipped' => null,
                'disabled' => null,
                default => 50
            };
        }

        // Remove null values (skipped/disabled checks)
        $scores = array_filter($scores, fn($score) => $score !== null);

        return empty($scores) ? 0 : (int) round(array_sum($scores) / count($scores));
    }
}

class HealthCheckResult
{
    public function __construct(
        private array $checks
    ) {}

    public function isHealthy(): bool
    {
        return !$this->hasErrors() && !$this->hasCriticalIssues();
    }

    public function hasErrors(): bool
    {
        return in_array('error', array_column($this->checks, 'status'));
    }

    public function hasCriticalIssues(): bool
    {
        return in_array('critical', array_column($this->checks, 'status'));
    }

    public function hasWarnings(): bool
    {
        return in_array('warning', array_column($this->checks, 'status'));
    }

    public function getOverallStatus(): string
    {
        $statuses = array_column($this->checks, 'status');

        if (in_array('error', $statuses)) return 'error';
        if (in_array('critical', $statuses)) return 'critical';
        if (in_array('warning', $statuses)) return 'warning';
        if (in_array('caution', $statuses)) return 'caution';

        return 'healthy';
    }

    public function getChecks(): array
    {
        return $this->checks;
    }

    public function getChecksByStatus(string $status): array
    {
        return array_filter($this->checks, fn($check) => $check['status'] === $status);
    }

    public function toArray(): array
    {
        return [
            'overall_status' => $this->getOverallStatus(),
            'is_healthy' => $this->isHealthy(),
            'has_errors' => $this->hasErrors(),
            'has_critical_issues' => $this->hasCriticalIssues(),
            'has_warnings' => $this->hasWarnings(),
            'checks' => $this->checks,
            'summary' => $this->getSummary(),
            'timestamp' => now()->toISOString()
        ];
    }

    public function getSummary(): array
    {
        $statuses = array_column($this->checks, 'status');

        return [
            'total_checks' => count($this->checks),
            'healthy' => count(array_filter($statuses, fn($s) => $s === 'healthy')),
            'caution' => count(array_filter($statuses, fn($s) => $s === 'caution')),
            'warning' => count(array_filter($statuses, fn($s) => $s === 'warning')),
            'critical' => count(array_filter($statuses, fn($s) => $s === 'critical')),
            'error' => count(array_filter($statuses, fn($s) => $s === 'error')),
            'skipped' => count(array_filter($statuses, fn($s) => $s === 'skipped')),
            'disabled' => count(array_filter($statuses, fn($s) => $s === 'disabled'))
        ];
    }
}
