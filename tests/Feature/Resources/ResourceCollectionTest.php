<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Resources;

use BlueBeetle\ApiToolkit\Resources\ResourceCollection;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\StubResource;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use stdClass;

function makeItem(string $id, string $name): stdClass
{
    $item = new stdClass();
    $item->id = $id;
    $item->name = $name;

    return $item;
}

it('serializes a collection of items', function () {
    $items = Collection::make([
        makeItem('1', 'Widget'),
        makeItem('2', 'Gadget'),
    ]);

    $collection = new ResourceCollection($items, StubResource::class);
    $result = $collection->toArray();

    expect($result['data'])->toHaveCount(2);
    expect($result['data'][0]['type'])->toBe('items');
    expect($result['data'][0]['id'])->toBe('1');
    expect($result['data'][0]['attributes']['name'])->toBe('Widget');
    expect($result['data'][1]['id'])->toBe('2');
});

it('includes pagination meta and links for offset pagination', function () {
    $items = [
        makeItem('1', 'Widget'),
        makeItem('2', 'Gadget'),
    ];

    $paginator = new LengthAwarePaginator(
        items: $items,
        total: 50,
        perPage: 20,
        currentPage: 2,
    );

    $collection = new ResourceCollection($paginator, StubResource::class);
    $result = $collection->toArray();

    expect($result['data'])->toHaveCount(2);
    expect($result['meta']['page']['currentPage'])->toBe(2);
    expect($result['meta']['page']['lastPage'])->toBe(3);
    expect($result['meta']['page']['perPage'])->toBe(20);
    expect($result['meta']['page']['total'])->toBe(50);
    expect($result['links'])->toHaveKey('first');
    expect($result['links'])->toHaveKey('last');
    expect($result['links'])->toHaveKey('prev');
    expect($result['links'])->toHaveKey('next');
});

it('handles empty collections', function () {
    $collection = new ResourceCollection(Collection::make(), StubResource::class);
    $result = $collection->toArray();

    expect($result['data'])->toBe([]);
    expect($result)->not->toHaveKey('meta');
});

it('handles arrays', function () {
    $items = [
        makeItem('1', 'Widget'),
    ];

    $collection = new ResourceCollection($items, StubResource::class);
    $result = $collection->toArray();

    expect($result['data'])->toHaveCount(1);
    expect($result['data'][0]['id'])->toBe('1');
});

it('includes cursor pagination meta and links', function () {
    $items = [
        makeItem('1', 'Widget'),
        makeItem('2', 'Gadget'),
    ];

    $paginator = new CursorPaginator(
        items: $items,
        perPage: 2,
        cursor: null,
        options: ['parameters' => ['cursor']],
    );

    $collection = new ResourceCollection($paginator, StubResource::class);
    $result = $collection->toArray();

    expect($result['data'])->toHaveCount(2);
    expect($result['meta'])->toHaveKey('page');
    expect($result['meta']['page']['perPage'])->toBe(2);
    expect($result['meta']['page'])->toHaveKey('hasMore');
    expect($result['links'])->toHaveKey('prev');
    expect($result['links'])->toHaveKey('next');
});

it('excludes offset-specific meta for cursor pagination', function () {
    $items = [makeItem('1', 'Widget')];

    $paginator = new CursorPaginator(
        items: $items,
        perPage: 10,
        cursor: null,
    );

    $collection = new ResourceCollection($paginator, StubResource::class);
    $result = $collection->toArray();

    expect($result['meta']['page'])->not->toHaveKey('currentPage');
    expect($result['meta']['page'])->not->toHaveKey('lastPage');
    expect($result['meta']['page'])->not->toHaveKey('total');
});

it('skips null relations in included', function () {
    $product = Product::create([
        'public_id' => 'prod-1',
        'name' => 'Widget',
        'code' => 'W01',
        'price_in_cents' => 1000,
        'featured' => false,
        'category_id' => null,
    ]);

    $product->load('category');

    $collection = new ResourceCollection(
        data: Collection::make([$product]),
        resourceClass: ProductResource::class,
    );

    $result = $collection->toArray();

    expect($result['data'])->toHaveCount(1);
    expect($result)->not->toHaveKey('included');
});
