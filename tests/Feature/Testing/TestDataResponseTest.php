<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Testing;

use BlueBeetle\ApiToolkit\Testing\TestDataResponse;
use PHPUnit\Framework\AssertionFailedError;

it('asserts count', function () {
    $response = new TestDataResponse(['a', 'b', 'c']);

    $response->assertCount(3);
});

it('fails assertCount with wrong count', function () {
    $response = new TestDataResponse(['a', 'b']);
    $response->assertCount(5);
})->throws(AssertionFailedError::class);

it('asserts same value', function () {
    $response = new TestDataResponse(['name' => 'Widget', 'code' => 'W01']);

    $response->assertSame('name', 'Widget');
    $response->assertSame('code', 'W01');
});

it('asserts not same value', function () {
    $response = new TestDataResponse(['name' => 'Widget']);

    $response->assertNotSame('name', 'Gadget');
});

it('asserts true', function () {
    $response = new TestDataResponse(['active' => true]);

    $response->assertTrue('active');
});

it('asserts false', function () {
    $response = new TestDataResponse(['archived' => false]);

    $response->assertFalse('archived');
});

it('asserts null', function () {
    $response = new TestDataResponse(['description' => null]);

    $response->assertNull('description');
});

it('asserts not null', function () {
    $response = new TestDataResponse(['name' => 'Widget']);

    $response->assertNotNull('name');
});

it('asserts empty', function () {
    $response = new TestDataResponse([]);

    $response->assertEmpty();
});

it('asserts not empty', function () {
    $response = new TestDataResponse(['a']);

    $response->assertNotEmpty();
});

it('scopes to an item by index', function () {
    $response = new TestDataResponse([
        ['type' => 'products', 'id' => '1'],
        ['type' => 'products', 'id' => '2'],
    ]);

    $response->item(0)->assertSame('id', '1');
    $response->item(1)->assertSame('id', '2');
});

it('fails scoping to non-existent item index', function () {
    $response = new TestDataResponse([['id' => '1']]);
    $response->item(5);
})->throws(AssertionFailedError::class);

it('scopes to attributes', function () {
    $response = new TestDataResponse([
        'attributes' => ['name' => 'Widget', 'code' => 'W01'],
    ]);

    $response->attributes()->assertSame('name', 'Widget');
});

it('scopes to relationships', function () {
    $response = new TestDataResponse([
        'relationships' => [
            'category' => ['data' => ['type' => 'categories', 'id' => '1']],
        ],
    ]);

    $response->relationships()->assertHasKey('category');
});

it('scopes to meta', function () {
    $response = new TestDataResponse([
        'meta' => ['version' => 1],
    ]);

    $response->meta()->assertSame('version', 1);
});

it('scopes to links', function () {
    $response = new TestDataResponse([
        'links' => ['self' => '/products/1'],
    ]);

    $response->links()->assertSame('self', '/products/1');
});

it('asserts has key', function () {
    $response = new TestDataResponse(['name' => 'Widget']);

    $response->assertHasKey('name');
});

it('fails assertHasKey for missing key', function () {
    $response = new TestDataResponse(['name' => 'Widget']);
    $response->assertHasKey('missing');
})->throws(AssertionFailedError::class);

it('asserts missing key', function () {
    $response = new TestDataResponse(['name' => 'Widget']);

    $response->assertMissingKey('email');
});

it('fails assertMissingKey when key exists', function () {
    $response = new TestDataResponse(['name' => 'Widget']);
    $response->assertMissingKey('name');
})->throws(AssertionFailedError::class);

it('supports dot notation for nested keys', function () {
    $response = new TestDataResponse([
        'nested' => ['deep' => ['value' => 'found']],
    ]);

    $response->assertSame('nested.deep.value', 'found');
    $response->assertHasKey('nested.deep.value');
    $response->assertMissingKey('nested.deep.missing');
});

it('returns self from all assertions for chaining', function () {
    $response = new TestDataResponse(['name' => 'Widget', 'active' => true]);

    $result = $response
        ->assertSame('name', 'Widget')
        ->assertTrue('active')
        ->assertNotEmpty()
        ->assertHasKey('name')
    ;

    expect($result)->toBeInstanceOf(TestDataResponse::class);
});

it('returns empty data when scoping to missing attributes', function () {
    $response = new TestDataResponse([]);

    $response->attributes()->assertEmpty();
    $response->relationships()->assertEmpty();
    $response->meta()->assertEmpty();
    $response->links()->assertEmpty();
});
