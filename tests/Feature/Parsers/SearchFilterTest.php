<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\SearchFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;

it('searches across multiple columns with OR', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'description' => 'A fine product']);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'description' => 'A widget alternative']);
    Product::create(['public_id' => 'p3', 'name' => 'Doohickey', 'code' => 'D01', 'description' => 'Something else']);

    $filter = new SearchFilter(['name', 'description']);
    $query = Product::query();
    $filter->apply($query, 'search', 'widget');

    expect($query->get())->toHaveCount(2);
});

it('searches a single column', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01']);

    $filter = new SearchFilter(['name']);
    $query = Product::query();
    $filter->apply($query, 'search', 'Widget');

    expect($query->get())->toHaveCount(1);
    expect($query->first()->name)->toBe('Widget');
});

it('is case insensitive', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new SearchFilter(['name']);
    $query = Product::query();
    $filter->apply($query, 'search', 'WIDGET');

    expect($query->get())->toHaveCount(1);
});

it('does nothing with empty value', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new SearchFilter(['name']);
    $query = Product::query();
    $filter->apply($query, 'search', '');

    expect($query->get())->toHaveCount(1);
});

it('does nothing with non-string value', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new SearchFilter(['name']);
    $query = Product::query();
    $filter->apply($query, 'search', ['not', 'a', 'string']);

    expect($query->get())->toHaveCount(1);
});

it('does nothing with empty columns', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new SearchFilter([]);
    $query = Product::query();
    $filter->apply($query, 'search', 'widget');

    expect($query->get())->toHaveCount(1);
});

it('wraps conditions in a group to preserve other wheres', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'status' => 'active']);
    Product::create(['public_id' => 'p2', 'name' => 'Widget Pro', 'code' => 'W02', 'status' => 'inactive']);
    Product::create(['public_id' => 'p3', 'name' => 'Gadget', 'code' => 'G01', 'status' => 'active']);

    $filter = new SearchFilter(['name', 'code']);
    $query = Product::where('status', 'active');
    $filter->apply($query, 'search', 'widget');

    $results = $query->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->public_id)->toBe('p1');
});

it('works through the query builder', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);
    Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01']);
    Product::create(['public_id' => 'p3', 'name' => 'Thingamajig', 'code' => 'WIDG']);

    $request = \Illuminate\Http\Request::create('/', 'GET', [
        'filter' => ['search' => 'widg'],
    ]);

    $result = \BlueBeetle\ApiToolkit\QueryBuilder::for(Product::class, $request)
        ->allowedFilters(['search' => new SearchFilter(['name', 'code'])])
        ->apply()
        ->getQuery()
        ->get()
    ;

    expect($result)->toHaveCount(2);
});
