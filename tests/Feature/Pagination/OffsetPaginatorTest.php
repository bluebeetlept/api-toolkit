<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Pagination;

use BlueBeetle\ApiToolkit\Pagination\OffsetPaginator;
use BlueBeetle\ApiToolkit\Parsers\PageParser;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

function createOffsetProducts(int $count): void
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

it('paginates with default page size and page number', function () {
    createOffsetProducts(5);

    $request = Request::create('/');
    $paginator = new OffsetPaginator(new PageParser(defaultSize: 20, maxSize: 100));

    $result = $paginator->paginate($request, Product::query());

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->perPage())->toBe(20);
    expect($result->currentPage())->toBe(1);
    expect($result->total())->toBe(5);
});

it('respects custom page size from request', function () {
    createOffsetProducts(5);

    $request = Request::create('/', 'GET', ['page' => ['size' => 2]]);
    $paginator = new OffsetPaginator(new PageParser(defaultSize: 20, maxSize: 100));

    $result = $paginator->paginate($request, Product::query());

    expect($result->perPage())->toBe(2);
    expect($result->items())->toHaveCount(2);
});

it('respects page number from request', function () {
    createOffsetProducts(5);

    $request = Request::create('/', 'GET', ['page' => ['size' => 2, 'number' => 2]]);
    $paginator = new OffsetPaginator(new PageParser(defaultSize: 20, maxSize: 100));

    $result = $paginator->paginate($request, Product::query());

    expect($result->currentPage())->toBe(2);
    expect($result->items())->toHaveCount(2);
});

it('uses custom PageParser configuration', function () {
    createOffsetProducts(5);

    $request = Request::create('/');
    $paginator = new OffsetPaginator(new PageParser(defaultSize: 3, maxSize: 10));

    $result = $paginator->paginate($request, Product::query());

    expect($result->perPage())->toBe(3);
});
