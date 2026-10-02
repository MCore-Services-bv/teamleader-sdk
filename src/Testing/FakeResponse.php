<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Testing;

/**
 * A response for Teamleader::fake(): a success, or a refusal the fake turns
 * into exactly what the real client would give — an exception, or an error
 * array when throw_exceptions is off.
 *
 *     Teamleader::fake([
 *         'deals.create' => Teamleader::response(['data' => ['id' => 'deal-1']]),
 *         'deals.info'   => Teamleader::response()->status(404)->message('Deal not found'),
 *         'deals.update' => Teamleader::response()->status(422)->errors(['title is required']),
 *     ]);
 */
final class FakeResponse
{
    private ?string $message = null;

    /** @var list<string> */
    private array $errors = [];

    /**
     * @param  array<string, mixed>  $body  For a success: the response, usually ['data' => …]
     * @param  array<string, mixed>  $headers
     */
    public function __construct(
        private array $body = [],
        private int $status = 200,
        private array $headers = [],
    ) {}

    public static function make(array $body = [], int $status = 200, array $headers = []): self
    {
        return new self($body, $status, $headers);
    }

    public function status(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param  list<string>  $errors
     */
    public function errors(array $errors): self
    {
        $this->errors = array_values(array_map('strval', $errors));

        return $this;
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function headers(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isError(): bool
    {
        return $this->status >= 400;
    }

    /**
     * The array the real client returns: the body plus `headers` for a
     * success, the SDK's error shape for a refusal.
     *
     * @return array<string, mixed>
     */
    public function toResult(): array
    {
        if (! $this->isError()) {
            return $this->body + ['headers' => $this->headers];
        }

        $message = $this->message ?? $this->errors[0] ?? "Fake Teamleader error (HTTP {$this->status})";

        return [
            'error' => true,
            'status_code' => $this->status,
            'message' => $message,
            'errors' => $this->errors !== [] ? $this->errors : [$message],
            'headers' => $this->headers,
        ];
    }
}
