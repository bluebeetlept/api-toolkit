<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Resources;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Http\SuccessResponse;
use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Http\Request;

afterEach(function () {
    Resource::resetResolvers();
});

it('registers a resource map', function () {
    Resource::map([
        Product::class => ProductResource::class,
    ]);

    expect(Resource::resolveResourceClass(Product::class))->toBe(ProductResource::class);
});

it('resolves resource class from model instance', function () {
    Resource::map([
        Product::class => ProductResource::class,
    ]);

    $product = new Product();

    expect(Resource::resolveResourceClass($product))->toBe(ProductResource::class);
});

it('returns null for unmapped models', function () {
    expect(Resource::resolveResourceClass(Product::class))->toBeNull();
});

it('resets resource map with resetResolvers', function () {
    Resource::map([
        Product::class => ProductResource::class,
    ]);

    Resource::resetResolvers();

    expect(Resource::resolveResourceClass(Product::class))->toBeNull();
});

it('returns null for non-object non-string values', function () {
    expect(Resource::resolveResourceClass(['raw' => 'data']))->toBeNull();
    expect(Resource::resolveResourceClass(123))->toBeNull();
    expect(Resource::resolveResourceClass(null))->toBeNull();
});

it('merges multiple map calls', function () {
    Resource::map([
        Product::class => ProductResource::class,
    ]);

    Resource::map([
        'App\Models\Category' => 'App\Http\Resources\CategoryResource',
    ]);

    expect(Resource::resolveResourceClass(Product::class))->toBe(ProductResource::class);
    expect(Resource::resolveResourceClass('App\Models\Category'))->toBe('App\Http\Resources\CategoryResource');
});

it('auto-resolves resource in SuccessResponse for single model', function () {
    Resource::map([
        Product::class => ProductResource::class,
    ]);

    $product = Product::create([
        'public_id' => 'p1',
        'name' => 'Widget',
        'code' => 'W01',
        'price_in_cents' => 1000,
        'featured' => false,
    ]);

    $response = new Response();
    $result = $response->success($product)->respond();
    $data = json_decode($result->getContent(), true);

    expect($data['data']['type'])->toBe('products');
    expect($data['data']['attributes']['name'])->toBe('Widget');
});

it('auto-resolves resource in SuccessResponse for collection', function () {
    Resource::map([
        Product::class => ProductResource::class,
    ]);

    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000, 'featured' => false]);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'price_in_cents' => 2000, 'featured' => false]);

    $response = new Response();
    $result = $response->success(Product::all())->respond();
    $data = json_decode($result->getContent(), true);

    expect($data['data'])->toHaveCount(2);
    expect($data['data'][0]['type'])->toBe('products');
});

it('auto-resolves resource in QueryBuilder', function () {
    Resource::map([
        Product::class => ProductResource::class,
    ]);

    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000, 'featured' => false]);

    $request = Request::create('/');

    $result = QueryBuilder::for(Product::class, $request)->paginate();

    expect($result)->toBeInstanceOf(SuccessResponse::class);

    $array = $result->toArray();
    expect($array['data'])->toHaveCount(1);
    expect($array['data'][0]['type'])->toBe('products');
});

it('explicit resource class takes precedence over map', function () {
    Resource::map([
        Product::class => ProductResource::class,
    ]);

    $product = Product::create([
        'public_id' => 'p1',
        'name' => 'Widget',
        'code' => 'W01',
        'price_in_cents' => 1000,
        'featured' => false,
    ]);

    $response = new Response();
    $result = $response->success($product, ProductResource::class)->respond();
    $data = json_decode($result->getContent(), true);

    expect($data['data']['type'])->toBe('products');
});

it('returns raw data when model is not mapped and no resource provided', function () {
    $response = new Response();
    $result = $response->success(['raw' => 'data'])->respond();
    $data = json_decode($result->getContent(), true);

    expect($data['data'])->toBe(['raw' => 'data']);
});
