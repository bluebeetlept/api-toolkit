<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Testing;

use BlueBeetle\ApiToolkit\Testing\TestDataResponse;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class TestDataResponseTest extends TestCase
{
    #[Test]
    #[TestDox('it asserts count')]
    public function it_asserts_count(): void
    {
        $response = new TestDataResponse(['a', 'b', 'c']);

        $response->assertCount(3);
    }

    #[Test]
    #[TestDox('it fails assertCount with wrong count')]
    public function it_fails_assert_count(): void
    {
        $this->expectException(AssertionFailedError::class);

        $response = new TestDataResponse(['a', 'b']);
        $response->assertCount(5);
    }

    #[Test]
    #[TestDox('it asserts same value')]
    public function it_asserts_same(): void
    {
        $response = new TestDataResponse(['name' => 'Widget', 'code' => 'W01']);

        $response->assertSame('name', 'Widget');
        $response->assertSame('code', 'W01');
    }

    #[Test]
    #[TestDox('it asserts not same value')]
    public function it_asserts_not_same(): void
    {
        $response = new TestDataResponse(['name' => 'Widget']);

        $response->assertNotSame('name', 'Gadget');
    }

    #[Test]
    #[TestDox('it asserts true')]
    public function it_asserts_true(): void
    {
        $response = new TestDataResponse(['active' => true]);

        $response->assertTrue('active');
    }

    #[Test]
    #[TestDox('it asserts false')]
    public function it_asserts_false(): void
    {
        $response = new TestDataResponse(['archived' => false]);

        $response->assertFalse('archived');
    }

    #[Test]
    #[TestDox('it asserts null')]
    public function it_asserts_null(): void
    {
        $response = new TestDataResponse(['description' => null]);

        $response->assertNull('description');
    }

    #[Test]
    #[TestDox('it asserts not null')]
    public function it_asserts_not_null(): void
    {
        $response = new TestDataResponse(['name' => 'Widget']);

        $response->assertNotNull('name');
    }

    #[Test]
    #[TestDox('it asserts empty')]
    public function it_asserts_empty(): void
    {
        $response = new TestDataResponse([]);

        $response->assertEmpty();
    }

    #[Test]
    #[TestDox('it asserts not empty')]
    public function it_asserts_not_empty(): void
    {
        $response = new TestDataResponse(['a']);

        $response->assertNotEmpty();
    }

    #[Test]
    #[TestDox('it scopes to an item by index')]
    public function it_scopes_to_item(): void
    {
        $response = new TestDataResponse([
            ['type' => 'products', 'id' => '1'],
            ['type' => 'products', 'id' => '2'],
        ]);

        $response->item(0)->assertSame('id', '1');
        $response->item(1)->assertSame('id', '2');
    }

    #[Test]
    #[TestDox('it fails scoping to non-existent item index')]
    public function it_fails_on_invalid_item_index(): void
    {
        $this->expectException(AssertionFailedError::class);

        $response = new TestDataResponse([['id' => '1']]);
        $response->item(5);
    }

    #[Test]
    #[TestDox('it scopes to attributes')]
    public function it_scopes_to_attributes(): void
    {
        $response = new TestDataResponse([
            'attributes' => ['name' => 'Widget', 'code' => 'W01'],
        ]);

        $response->attributes()->assertSame('name', 'Widget');
    }

    #[Test]
    #[TestDox('it scopes to relationships')]
    public function it_scopes_to_relationships(): void
    {
        $response = new TestDataResponse([
            'relationships' => [
                'category' => ['data' => ['type' => 'categories', 'id' => '1']],
            ],
        ]);

        $response->relationships()->assertHasKey('category');
    }

    #[Test]
    #[TestDox('it scopes to meta')]
    public function it_scopes_to_meta(): void
    {
        $response = new TestDataResponse([
            'meta' => ['version' => 1],
        ]);

        $response->meta()->assertSame('version', 1);
    }

    #[Test]
    #[TestDox('it scopes to links')]
    public function it_scopes_to_links(): void
    {
        $response = new TestDataResponse([
            'links' => ['self' => '/products/1'],
        ]);

        $response->links()->assertSame('self', '/products/1');
    }

    #[Test]
    #[TestDox('it asserts has key')]
    public function it_asserts_has_key(): void
    {
        $response = new TestDataResponse(['name' => 'Widget']);

        $response->assertHasKey('name');
    }

    #[Test]
    #[TestDox('it fails assertHasKey for missing key')]
    public function it_fails_assert_has_key(): void
    {
        $this->expectException(AssertionFailedError::class);

        $response = new TestDataResponse(['name' => 'Widget']);
        $response->assertHasKey('missing');
    }

    #[Test]
    #[TestDox('it asserts missing key')]
    public function it_asserts_missing_key(): void
    {
        $response = new TestDataResponse(['name' => 'Widget']);

        $response->assertMissingKey('email');
    }

    #[Test]
    #[TestDox('it fails assertMissingKey when key exists')]
    public function it_fails_assert_missing_key(): void
    {
        $this->expectException(AssertionFailedError::class);

        $response = new TestDataResponse(['name' => 'Widget']);
        $response->assertMissingKey('name');
    }

    #[Test]
    #[TestDox('it supports dot notation for nested keys')]
    public function it_supports_dot_notation(): void
    {
        $response = new TestDataResponse([
            'nested' => ['deep' => ['value' => 'found']],
        ]);

        $response->assertSame('nested.deep.value', 'found');
        $response->assertHasKey('nested.deep.value');
        $response->assertMissingKey('nested.deep.missing');
    }

    #[Test]
    #[TestDox('it returns self from all assertions for chaining')]
    public function it_supports_chaining(): void
    {
        $response = new TestDataResponse(['name' => 'Widget', 'active' => true]);

        $result = $response
            ->assertSame('name', 'Widget')
            ->assertTrue('active')
            ->assertNotEmpty()
            ->assertHasKey('name')
        ;

        $this->assertInstanceOf(TestDataResponse::class, $result);
    }

    #[Test]
    #[TestDox('it returns empty data when scoping to missing attributes')]
    public function it_handles_missing_scope_keys(): void
    {
        $response = new TestDataResponse([]);

        $response->attributes()->assertEmpty();
        $response->relationships()->assertEmpty();
        $response->meta()->assertEmpty();
        $response->links()->assertEmpty();
    }
}
