<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\ColumnFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;

// Scalar (exact match)

it('filters by exact scalar value', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'status' => 'active']);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'status' => 'draft']);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'status', 'active');

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

// Comparison operators

it('filters with eq operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'price_in_cents' => 2000]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['eq' => 1000]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

it('filters with neq operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'price_in_cents' => 2000]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['neq' => 1000]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p2');
});

it('filters with range operators', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['gte' => 500, 'lte' => 1000]);

    expect($query->get())->toHaveCount(2);
});

it('filters with gt and lt operators', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['gt' => 500, 'lt' => 5000]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p2');
});

// IN / NOT IN

it('filters with in operator using comma-separated string', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Active', 'code' => 'A01', 'status' => 'active']);
    Product::create(['public_id' => 'p2', 'name' => 'Archived', 'code' => 'A02', 'status' => 'archived']);
    Product::create(['public_id' => 'p3', 'name' => 'Draft', 'code' => 'D01', 'status' => 'draft']);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'status', ['in' => 'active,archived']);

    expect($query->get())->toHaveCount(2);
});

it('filters with in operator using array', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Active', 'code' => 'A01', 'status' => 'active']);
    Product::create(['public_id' => 'p2', 'name' => 'Draft', 'code' => 'D01', 'status' => 'draft']);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'status', ['in' => ['active']]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

it('filters with not_in operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Active', 'code' => 'A01', 'status' => 'active']);
    Product::create(['public_id' => 'p2', 'name' => 'Archived', 'code' => 'A02', 'status' => 'archived']);
    Product::create(['public_id' => 'p3', 'name' => 'Draft', 'code' => 'D01', 'status' => 'draft']);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'status', ['not_in' => 'archived,draft']);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

// NULL

it('filters where field is null', function () {
    Product::create(['public_id' => 'p1', 'name' => 'With', 'code' => 'C01', 'category_id' => 1]);
    Product::create(['public_id' => 'p2', 'name' => 'Without', 'code' => 'C02', 'category_id' => null]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'category_id', ['null' => 'true']);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p2');
});

it('filters where field is not null', function () {
    Product::create(['public_id' => 'p1', 'name' => 'With', 'code' => 'C01', 'category_id' => 1]);
    Product::create(['public_id' => 'p2', 'name' => 'Without', 'code' => 'C02', 'category_id' => null]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'category_id', ['null' => 'false']);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

// LIKE

it('filters with like operator', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget Pro', 'code' => 'W01']);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01']);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'name', ['like' => 'widget']);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

// Combined operators on same field

it('combines multiple operators on same field', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['gte' => 1000, 'lte' => 3000]);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p2');
});

it('applies filters across different fields', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap', 'code' => 'C01', 'price_in_cents' => 500, 'category_id' => 1]);
    Product::create(['public_id' => 'p2', 'name' => 'Mid', 'code' => 'M01', 'price_in_cents' => 1000, 'category_id' => null]);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive', 'code' => 'E01', 'price_in_cents' => 5000, 'category_id' => 1]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['gte' => 1000]);
    $filter->apply($query, 'category_id', ['null' => 'false']);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p3');
});

// Edge cases

it('ignores unknown operators', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'price_in_cents' => 1000]);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'price_in_cents', ['invalid' => 500]);

    expect($query->get())->toHaveCount(1);
});

it('ignores non-string non-numeric scalar values', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new ColumnFilter();
    $query = Product::query();
    $filter->apply($query, 'name', true);

    expect($query->get())->toHaveCount(1);
});

// QueryBuilder integration

it('works through the query builder with mixed operators', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Cheap Active', 'code' => 'C01', 'price_in_cents' => 500, 'status' => 'active']);
    Product::create(['public_id' => 'p2', 'name' => 'Mid Active', 'code' => 'M01', 'price_in_cents' => 1500, 'status' => 'active']);
    Product::create(['public_id' => 'p3', 'name' => 'Expensive Draft', 'code' => 'E01', 'price_in_cents' => 5000, 'status' => 'draft']);

    $request = \Illuminate\Http\Request::create('/', 'GET', [
        'filter' => [
            'price_in_cents' => ['gte' => 1000, 'lte' => 5000],
            'status' => 'active',
        ],
    ]);

    $result = \BlueBeetle\ApiToolkit\QueryBuilder::for(Product::class, $request)
        ->allowedFilters([
            'price_in_cents' => new ColumnFilter(),
            'status' => new ColumnFilter(),
        ])
        ->apply()
        ->getQuery()
        ->get()
    ;

    expect($result)->toHaveCount(1);
    expect($result->first()->public_id)->toBe('p2');
});
