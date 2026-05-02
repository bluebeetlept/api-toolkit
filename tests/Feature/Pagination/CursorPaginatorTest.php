<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Pagination;

use BlueBeetle\ApiToolkit\Pagination\CursorPaginator;
use BlueBeetle\ApiToolkit\Parsers\PageParser;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Http\Request;

function createProducts(int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        Product::create([
            'public_id' => "prod-{$i}",
            'name' => "Product {$i}",
            'code' => "P{$i}",
            'price_in_cents' => $i * 1000,
            'featured' => false,
        ]);
    }
}

it('cursor paginates with default page size', function () {
    createProducts(5);

    $request = Request::create('/');
    $paginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));

    $result = $paginator->paginate($request, Product::query());

    expect($result)->toBeInstanceOf(CursorPaginatorContract::class);
    expect($result->perPage())->toBe(20);
    expect($result->items())->toHaveCount(5);
});

it('respects custom page size from request', function () {
    createProducts(5);

    $request = Request::create('/', 'GET', ['page' => ['size' => 2]]);
    $paginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));

    $result = $paginator->paginate($request, Product::query());

    expect($result->perPage())->toBe(2);
    expect($result->items())->toHaveCount(2);
    expect($result->hasMorePages())->toBeTrue();
});

it('paginates with a cursor value', function () {
    createProducts(5);

    $initialRequest = Request::create('/', 'GET', ['page' => ['size' => 2]]);
    $initialPaginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));
    $initialResult = $initialPaginator->paginate($initialRequest, Product::query());

    $nextCursor = $initialResult->nextCursor()?->encode();
    expect($nextCursor)->not->toBeNull();

    $request = Request::create('/', 'GET', ['page' => ['size' => 2, 'cursor' => $nextCursor]]);
    $paginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));

    $result = $paginator->paginate($request, Product::query());

    expect($result->items())->toHaveCount(2);
});

it('handles null cursor gracefully', function () {
    createProducts(3);

    $request = Request::create('/', 'GET', ['page' => ['size' => 10]]);
    $paginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));

    $result = $paginator->paginate($request, Product::query());

    expect($result->items())->toHaveCount(3);
    expect($result->hasMorePages())->toBeFalse();
});
