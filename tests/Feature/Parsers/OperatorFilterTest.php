<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\OperatorFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;

it('filters with eq operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'price_in_cents' => 2000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['eq' => 1000]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

it('filters with neq operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'price_in_cents' => 2000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['neq' => 1000]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p2');
});

it('filters with gt operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['gt' => 1000]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p3');
});

it('filters with gte operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['gte' => 1000]);

    expect($query->get())->toHaveCount(2);
});

it('filters with lt operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['lt' => 1000]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

it('filters with lte operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['lte' => 1000]);

    expect($query->get())->toHaveCount(2);
});

it('combines multiple operators as range', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['gte' => 500, 'lte' => 1000]);

    expect($query->get())->toHaveCount(2);
});

it('falls back to exact match for scalar values', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'price_in_cents' => 2000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', '1000');

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

it('ignores unknown operators', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000]);

    $filter = new OperatorFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['invalid' => 500]);

    expect($query->get())->toHaveCount(1);
});

it('works through the query builder', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $request = \Illuminate\Http\Request::create('/', 'GET', [
        'filter' => ['price_in_cents' => ['gte' => 1000, 'lt' => 5000]],
    ]);

    $result = \BlueBeetle\ApiToolkit\QueryBuilder::for(Product::class, $request)
        ->allowedFilters(['price_in_cents' => new OperatorFilter()])
        ->apply()
        ->getQuery()
        ->get()
    ;

    expect($result)->toHaveCount(1);
    expect($result->first()->name)->toBe('Mid');
});
