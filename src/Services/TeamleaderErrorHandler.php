<?php

namespace McoreServices\TeamleaderSDK\Services;

use McoreServices\TeamleaderSDK\Traits\SanitizesLogData;
use McoreServices\TeamleaderSDK\Exceptions\{
    TeamleaderException,
    AuthenticationException,
    AuthorizationException,
    NotFoundException,
    ValidationException,
    RateLimitExceededException,
    ServerException,
    ConnectionException,
    ConfigurationException
};
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use GuzzleHttp\Exception\GuzzleException;

class TeamleaderErrorHandler
{
    use SanitizesLogData;
    private LoggerInterface $logger;
    private bool $throwExceptions;

    public function __construct(LoggerInterface $logger = null, bool $throwExceptions = null)
    {
        $this->logger = $logger ?: new NullLogger();
        $this->throwExceptions = $throwExceptions ?? config('teamleader.error_handling.throw_exceptions', false);
    }

    /**
     * Handle API response errors
     */
    public function handleApiError(array $result, string $context = ''): void
    {
        if (!isset($result['error']) || !$result['error']) {
            return; // No error to handle
        }

        $statusCode = $result['status_code'] ?? 0;
        $message = $result['message'] ?? 'Unknown error';
        $errors = $result['errors'] ?? [$message];

        $errorContext = [
            'context' => $context,
            'status_code' => $statusCode,
            'primary_error' => $message,
            'all_errors' => $errors,
            'response_data' => $result['response'] ?? null,
            'headers' => $result['headers'] ?? []
        ];

        // Log the error with appropriate severity
        $this->logError($statusCode, $message, $errorContext);

        // Throw exception if configured to do so
        if ($this->throwExceptions) {
            throw $this->createException($statusCode, $message, $errors, $errorContext);
        }
    }

    /**
     * Handle Guzzle HTTP exceptions
     */
    public function handleGuzzleException(GuzzleException $exception, string $context = ''): void
    {
        $statusCode = 0;
        $responseBody = null;

        if (method_exists($exception, 'getResponse') && $exception->getResponse()) {
            $response = $exception->getResponse();
            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();
        }

        $errorContext = [
            'context' => $context,
            'status_code' => $statusCode,
            'response_body' => $responseBody,
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage()
        ];

        $this->logger->error('Teamleader HTTP exception', $this->sanitizeForLog($errorContext));

        if ($this->throwExceptions) {
            // Wrap Guzzle exception in our own exception type
            throw new ConnectionException(
                'HTTP request failed: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception,
                $errorContext,
                $statusCode
            );
        }
    }

    /**
     * Handle configuration errors
     */
    public function handleConfigurationError(string $message, array $context = []): void
    {
        $this->logger->critical('Teamleader configuration error', [
            'message' => $message,
            'context' => $context
        ]);

        if ($this->throwExceptions) {
            throw new ConfigurationException($message, 0, null, $context);
        }
    }

    /**
     * Execute callback with automatic retry logic for transient errors
     */
    public function withRetry(callable $callback, int $maxAttempts = 3, string $context = ''): mixed
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $maxAttempts) {
            try {
                return $callback();
            } catch (ServerException|RateLimitExceededException|ConnectionException $e) {
                $lastException = $e;

                if ($attempt === $maxAttempts) {
                    $this->logger->error("Max retry attempts reached for {$context}", [
                        'attempts' => $maxAttempts,
                        'final_error' => $e->getMessage()
                    ]);
                    throw $e;
                }

                $delay = $this->calculateRetryDelay($e, $attempt);

                $this->logger->info("Retrying {$context} after error (attempt {$attempt}/{$maxAttempts})", [
                    'error' => $e->getMessage(),
                    'delay_seconds' => $delay / 1000,
                    'exception_type' => get_class($e)
                ]);

                usleep($delay * 1000); // Convert ms to microseconds
                $attempt++;
            } catch (AuthenticationException|AuthorizationException|NotFoundException|ValidationException $e) {
                // Don't retry these - they won't succeed on retry
                $this->logger->warning("Non-retryable error in {$context}: " . $e->getMessage());
                throw $e;
            }
        }

        // This should never be reached, but just in case
        throw $lastException ?: new TeamleaderException('Retry logic failed unexpectedly');
    }

    /**
     * Check if an error is retryable
     */
    public function isRetryableError(TeamleaderException $exception): bool
    {
        return match (true) {
            $exception instanceof ServerException,
                $exception instanceof RateLimitExceededException,
                $exception instanceof ConnectionException => true,
            default => false
        };
    }

    /**
     * Extract user-friendly error message
     */
    public function getUserFriendlyMessage(TeamleaderException $exception): string
    {
        return match (true) {
            $exception instanceof AuthenticationException => 'Authentication with Teamleader failed. Please reconnect.',
            $exception instanceof AuthorizationException => 'You do not have permission to perform this action.',
            $exception instanceof NotFoundException => 'The requested resource was not found.',
            $exception instanceof ValidationException => 'The provided data is invalid: ' . implode(', ', $exception->getAllErrors()),
            $exception instanceof RateLimitExceededException => 'API rate limit exceeded. Please try again later.',
            $exception instanceof ServerException => 'Teamleader server error. Please try again later.',
            $exception instanceof ConnectionException => 'Connection to Teamleader failed. Please check your internet connection.',
            default => 'An unexpected error occurred: ' . $exception->getMessage()
        };
    }

    /**
     * Log error with appropriate severity level
     */
    private function logError(int $statusCode, string $message, array $context): void
    {
        $sanitizedContext = $this->sanitizeForLog($context);
        $logMessage = "Teamleader API error: {$message}";

        match (true) {
            $statusCode >= 500 => $this->logger->critical($logMessage, $sanitizedContext),
            $statusCode === 429 => $this->logger->warning($logMessage, $sanitizedContext),
            $statusCode === 401 => $this->logger->error($logMessage, $sanitizedContext),
            $statusCode === 403 => $this->logger->warning($logMessage, $sanitizedContext),
            $statusCode === 404 => $this->logger->info($logMessage, $sanitizedContext),
            $statusCode >= 400 => $this->logger->warning($logMessage, $sanitizedContext),
            default => $this->logger->error($logMessage, $sanitizedContext)
        };
    }

    /**
     * Create appropriate exception based on status code
     */
    private function createException(int $statusCode, string $message, array $errors, array $context): TeamleaderException
    {
        return match ($statusCode) {
            401 => new AuthenticationException($message, 401, null, $context, $statusCode, $errors),
            403 => new AuthorizationException($message, 403, null, $context, $statusCode, $errors),
            404 => new NotFoundException($message, 404, null, $context, $statusCode, $errors),
            422 => new ValidationException(
                'Validation failed: ' . implode(', ', $errors),
                422,
                null,
                $context,
                $statusCode,
                $errors
            ),
            429 => new RateLimitExceededException(
                $message,
                $this->extractRetryAfter($context),
                $this->extractResetTime($context),
                $context
            ),
            500, 502, 503, 504 => new ServerException($message, $statusCode, null, $context, $statusCode, $errors),
            default => new TeamleaderException($message, $statusCode, null, $context, $statusCode, $errors)
        };
    }

    /**
     * Calculate retry delay with exponential backoff
     */
    private function calculateRetryDelay(TeamleaderException $exception, int $attempt): int
    {
        if ($exception instanceof RateLimitExceededException) {
            // For rate limits, use the retry-after value
            return $exception->getRetryAfter() * 1000; // Convert seconds to milliseconds
        }

        // Exponential backoff with jitter
        $baseDelay = 1000; // 1 second base
        $exponentialDelay = $baseDelay * pow(2, $attempt - 1);
        $jitter = rand(0, 100); // Add up to 100ms jitter

        return min($exponentialDelay + $jitter, 30000); // Cap at 30 seconds
    }

    /**
     * Extract retry-after value from response headers
     */
    private function extractRetryAfter(array $context): int
    {
        $headers = $context['headers'] ?? [];

        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'retry-after') {
                return (int) (is_array($value) ? $value[0] : $value);
            }
        }

        return 60; // Default to 60 seconds
    }

    /**
     * Extract rate limit reset time from response headers
     */
    private function extractResetTime(array $context): ?string
    {
        $headers = $context['headers'] ?? [];

        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'x-ratelimit-reset') {
                return is_array($value) ? $value[0] : $value;
            }
        }

        return null;
    }

    /**
     * Set whether to throw exceptions
     */
    public function setThrowExceptions(bool $throwExceptions): self
    {
        $this->throwExceptions = $throwExceptions;
        return $this;
    }

    /**
     * Get whether exceptions are thrown
     */
    public function getThrowExceptions(): bool
    {
        return $this->throwExceptions;
    }

    /**
     * Create a simple error result array (for when not throwing exceptions)
     */
    public function createErrorResult(TeamleaderException $exception): array
    {
        return [
            'error' => true,
            'status_code' => $exception->getStatusCode(),
            'message' => $exception->getMessage(),
            'errors' => $exception->getAllErrors(),
            'user_message' => $this->getUserFriendlyMessage($exception),
            'retryable' => $this->isRetryableError($exception)
        ];
    }
}
