<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Testing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\StringContains;

class TestResponse extends \Illuminate\Testing\TestResponse
{
    /**
     * Get the JSON:API errors array as a TestDataResponse.
     */
    public function errors(): TestDataResponse
    {
        return new TestDataResponse($this->json('errors'));
    }

    /**
     * Get the JSON:API data object/array as a TestDataResponse.
     */
    public function data(): TestDataResponse
    {
        return new TestDataResponse($this->json('data'));
    }

    /**
     * Get the attributes of a single JSON:API resource as a TestDataResponse.
     * Shortcut for data.attributes.
     */
    public function attributes(): TestDataResponse
    {
        return new TestDataResponse($this->json('data.attributes') ?? []);
    }

    /**
     * Get the JSON:API included array as a TestDataResponse.
     */
    public function included(): TestDataResponse
    {
        return new TestDataResponse($this->json('included') ?? []);
    }

    /**
     * Get the JSON:API meta object as a TestDataResponse.
     */
    public function meta(): TestDataResponse
    {
        return new TestDataResponse($this->json('meta') ?? []);
    }

    /**
     * Assert the first error has the given title.
     */
    public function assertErrorTitle(string $title): self
    {
        $this->assertJsonPath('errors.0.title', $title);

        return $this;
    }

    /**
     * Assert the first error has the given detail.
     */
    public function assertErrorDetail(string $detail): self
    {
        $this->assertJsonPath('errors.0.detail', $detail);

        return $this;
    }

    /**
     * Assert the first error detail contains the given string.
     */
    public function assertErrorDetailContains(string $needle): self
    {
        Assert::assertThat($this->json('errors.0.detail'), new StringContains($needle));

        return $this;
    }

    /**
     * Assert the first error has the given code.
     */
    public function assertErrorCode(string $code): self
    {
        $this->assertJsonPath('errors.0.code', $code);

        return $this;
    }

    /**
     * Assert a validation error exists for the given field with the given message.
     */
    public function assertValidationError(string $field, string $message): self
    {
        $errors = $this->json('errors') ?? [];
        $pointer = '/'.str_replace('.', '/', $field);

        $found = false;

        foreach ($errors as $error) {
            if (($error['source']['pointer'] ?? '') === $pointer && ($error['detail'] ?? '') === $message) {
                $found = true;

                break;
            }
        }

        Assert::assertTrue($found, "No validation error found for field [{$field}] with message [{$message}].");

        return $this;
    }

    /**
     * Assert the resource type in data.
     */
    public function assertResourceType(string $type): self
    {
        $this->assertJsonPath('data.type', $type);

        return $this;
    }

    /**
     * Assert the resource id in data.
     */
    public function assertResourceId(string $id): self
    {
        $this->assertJsonPath('data.id', $id);

        return $this;
    }
}
