<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Testing;

use OutOfBoundsException;

/**
 * Successive responses for one endpoint:
 *
 *     Teamleader::fake([
 *         'contacts.list' => Teamleader::sequence()
 *             ->push(['data' => [['id' => 'c1'], ['id' => 'c2']]])
 *             ->push(['data' => []]),
 *     ]);
 *
 * An exhausted sequence throws, so a test notices an extra request — unless
 * whenEmpty() says what to answer from then on.
 */
final class ResponseSequence
{
    /** @var list<mixed> */
    private array $responses;

    private mixed $whenEmpty = null;

    private bool $hasWhenEmpty = false;

    /**
     * @param  list<array|FakeResponse|\Closure|string>  $responses
     */
    public function __construct(array $responses = [])
    {
        $this->responses = array_values($responses);
    }

    public function push(array|FakeResponse|\Closure|string $response): self
    {
        $this->responses[] = $response;

        return $this;
    }

    /** Answer this once the sequence is used up, instead of throwing */
    public function whenEmpty(array|FakeResponse|\Closure|string $response): self
    {
        $this->whenEmpty = $response;
        $this->hasWhenEmpty = true;

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->responses === [];
    }

    public function next(string $endpoint): mixed
    {
        if ($this->responses !== []) {
            return array_shift($this->responses);
        }

        if ($this->hasWhenEmpty) {
            return $this->whenEmpty;
        }

        throw new OutOfBoundsException(
            "The fake response sequence for {$endpoint} is used up. Push more responses, "
            .'or call whenEmpty() to answer every later request the same way.'
        );
    }
}
