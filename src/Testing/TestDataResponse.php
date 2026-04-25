<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Testing;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert;

readonly class TestDataResponse
{
    public function __construct(
        private array $data,
    ) {
    }

    public function assertCount(int $count): self
    {
        Assert::assertCount($count, $this->data);

        return $this;
    }

    public function assertSame(string $key, mixed $value): self
    {
        Assert::assertSame($value, $this->data($key));

        return $this;
    }

    public function assertNotSame(string $key, mixed $value): self
    {
        Assert::assertNotSame($value, $this->data($key));

        return $this;
    }

    public function assertTrue(string $key): self
    {
        Assert::assertTrue($this->data($key));

        return $this;
    }

    public function assertFalse(string $field): self
    {
        Assert::assertFalse($this->data($field));

        return $this;
    }

    public function assertNull(string $key): self
    {
        Assert::assertNull($this->data($key));

        return $this;
    }

    public function assertNotNull(string $key): self
    {
        Assert::assertNotNull($this->data($key));

        return $this;
    }

    public function assertEmpty(): self
    {
        Assert::assertEmpty($this->data);

        return $this;
    }

    public function assertNotEmpty(): self
    {
        Assert::assertNotEmpty($this->data);

        return $this;
    }

    /**
     * Scope to a specific item in the array by index.
     */
    public function item(int $index): self
    {
        Assert::assertArrayHasKey($index, $this->data, "Failed asserting that data has item at index [{$index}].");

        return new self($this->data[$index]);
    }

    /**
     * Scope to the attributes key of the current data.
     */
    public function attributes(): self
    {
        return new self($this->data['attributes'] ?? []);
    }

    /**
     * Scope to the relationships key of the current data.
     */
    public function relationships(): self
    {
        return new self($this->data['relationships'] ?? []);
    }

    /**
     * Scope to the meta key of the current data.
     */
    public function meta(): self
    {
        return new self($this->data['meta'] ?? []);
    }

    /**
     * Scope to the links key of the current data.
     */
    public function links(): self
    {
        return new self($this->data['links'] ?? []);
    }

    public function assertHasKey(string $key): self
    {
        Assert::assertTrue(Arr::has($this->data, $key), "Failed asserting that data has key [{$key}].");

        return $this;
    }

    public function assertMissingKey(string $key): self
    {
        Assert::assertFalse(Arr::has($this->data, $key), "Failed asserting that data is missing key [{$key}].");

        return $this;
    }

    private function data(string $key): mixed
    {
        return Arr::get($this->data, $key);
    }
}
