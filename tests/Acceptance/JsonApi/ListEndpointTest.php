<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\JsonApi;

use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Category;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Tag;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/api/v1/products', function () {
        return QueryBuilder::for(Product::class, request())
            ->fromResource(ProductResource::class)
            ->paginate()
        ;
    });
});

it('returns a valid JSON:API collection response', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01']);

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/vnd.api+json');

    $response->assertJsonStructure([
        'data' => [
            '*' => ['type', 'id', 'attributes', 'relationships', 'links', 'meta'],
        ],
        'meta' => ['page'],
        'links',
    ]);

    $response->assertJsonPath('data.0.type', 'products');
    $response->assertJsonPath('data.0.id', 'p1');
    $response->assertJsonPath('data.0.attributes.name', 'Widget');
    $response->assertJsonPath('data.0.attributes.code', 'W01');
});

it('includes pagination meta and links', function () {
    for ($i = 1; $i <= 25; $i++) {
        Product::create(['public_id' => "p{$i}", 'name' => "Product {$i}", 'code' => "C{$i}"]);
    }

    $response = $this->getJson('/api/v1/products?page[size]=10&page[number]=2');

    $response->assertStatus(200);
    $response->assertJsonPath('meta.page.currentPage', 2);
    $response->assertJsonPath('meta.page.perPage', 10);
    $response->assertJsonPath('meta.page.total', 25);
    $response->assertJsonPath('meta.page.lastPage', 3);

    $json = $response->json();
    expect($json['data'])->toHaveCount(10);
    expect($json['links']['prev'])->not->toBeNull();
    expect($json['links']['next'])->not->toBeNull();
});

it('applies filters', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'status' => 'active']);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'status' => 'inactive']);
    Product::create(['public_id' => 'p3', 'name' => 'Widget Pro', 'code' => 'W02', 'status' => 'active']);

    $response = $this->getJson('/api/v1/products?filter[status]=active');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);

    $response = $this->getJson('/api/v1/products?filter[name]=Widget');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});

it('applies sorting', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Banana', 'code' => 'B01']);
    Product::create(['public_id' => 'p2', 'name' => 'Apple', 'code' => 'A01']);
    Product::create(['public_id' => 'p3', 'name' => 'Cherry', 'code' => 'C01']);

    $response = $this->getJson('/api/v1/products?sort=name');

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.name'))->toBe('Apple');
    expect($response->json('data.1.attributes.name'))->toBe('Banana');
    expect($response->json('data.2.attributes.name'))->toBe('Cherry');

    $response = $this->getJson('/api/v1/products?sort=-name');

    expect($response->json('data.0.attributes.name'))->toBe('Cherry');
});

it('includes relationships when requested', function () {
    $category = Category::create(['public_id' => 'c1', 'name' => 'Electronics', 'slug' => 'electronics']);
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'category_id' => $category->id]);

    $response = $this->getJson('/api/v1/products?include=category');

    $response->assertStatus(200);

    $response->assertJsonPath('data.0.relationships.category.data.type', 'categories');
    $response->assertJsonPath('data.0.relationships.category.data.id', 'c1');

    $response->assertJsonStructure([
        'included' => [
            ['type', 'id', 'attributes'],
        ],
    ]);

    $response->assertJsonPath('included.0.type', 'categories');
    $response->assertJsonPath('included.0.id', 'c1');
    $response->assertJsonPath('included.0.attributes.name', 'Electronics');
});

it('includes self link and custom links', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200);
    $response->assertJsonPath('data.0.links.self', '/api/v1/products/p1');
    $response->assertJsonPath('data.0.links.category', '/api/v1/products/p1/category');
});

it('includes resource meta', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'status' => 'active']);

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200);
    $response->assertJsonPath('data.0.meta.is_available', true);
});

it('returns empty data array when no results', function () {
    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200);
    $response->assertJsonPath('data', []);
    $response->assertJsonPath('meta.page.total', 0);
});

it('deduplicates included resources', function () {
    $category = Category::create(['public_id' => 'c1', 'name' => 'Electronics', 'slug' => 'electronics']);
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'category_id' => $category->id]);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'category_id' => $category->id]);

    $response = $this->getJson('/api/v1/products?include=category');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('included'))->toHaveCount(1);
});

it('handles many-to-many relationships', function () {
    $tag1 = Tag::create(['public_id' => 't1', 'name' => 'Sale']);
    $tag2 = Tag::create(['public_id' => 't2', 'name' => 'New']);
    $product = Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);
    $product->tags()->attach([$tag1->id, $tag2->id]);

    $response = $this->getJson('/api/v1/products?include=tags');

    $response->assertStatus(200);
    expect($response->json('data.0.relationships.tags.data'))->toHaveCount(2);
    expect($response->json('included'))->toHaveCount(2);
});
