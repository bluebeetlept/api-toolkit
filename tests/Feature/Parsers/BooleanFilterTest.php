<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\BooleanFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;

it('filters by true string value', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);
    Product::create(['public_id' => 'p2', 'name' => 'Regular', 'code' => 'R01', 'featured' => false]);

    $filter = new BooleanFilter();
    $query = Product::query();
    $filter->apply($query, 'featured', 'true');

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

it('filters by false string value', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);
    Product::create(['public_id' => 'p2', 'name' => 'Regular', 'code' => 'R01', 'featured' => false]);

    $filter = new BooleanFilter();
    $query = Product::query();
    $filter->apply($query, 'featured', 'false');

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p2');
});

it('accepts 1 and 0 as boolean values', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);
    Product::create(['public_id' => 'p2', 'name' => 'Regular', 'code' => 'R01', 'featured' => false]);

    $filter = new BooleanFilter();

    $query = Product::query();
    $filter->apply($query, 'featured', '1');
    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');

    $query = Product::query();
    $filter->apply($query, 'featured', '0');
    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p2');
});

it('accepts yes and no as boolean values', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);
    Product::create(['public_id' => 'p2', 'name' => 'Regular', 'code' => 'R01', 'featured' => false]);

    $filter = new BooleanFilter();

    $query = Product::query();
    $filter->apply($query, 'featured', 'yes');
    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');

    $query = Product::query();
    $filter->apply($query, 'featured', 'no');
    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p2');
});

it('is case insensitive', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);
    Product::create(['public_id' => 'p2', 'name' => 'Regular', 'code' => 'R01', 'featured' => false]);

    $filter = new BooleanFilter();
    $query = Product::query();
    $filter->apply($query, 'featured', 'TRUE');

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

it('accepts actual boolean values', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);
    Product::create(['public_id' => 'p2', 'name' => 'Regular', 'code' => 'R01', 'featured' => false]);

    $filter = new BooleanFilter();
    $query = Product::query();
    $filter->apply($query, 'featured', true);

    expect($query->get())->toHaveCount(1);
    expect($query->first()->public_id)->toBe('p1');
});

it('ignores invalid values', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);
    Product::create(['public_id' => 'p2', 'name' => 'Regular', 'code' => 'R01', 'featured' => false]);

    $filter = new BooleanFilter();
    $query = Product::query();
    $filter->apply($query, 'featured', 'invalid');

    expect($query->get())->toHaveCount(2);
});

it('ignores non-string non-boolean values', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);

    $filter = new BooleanFilter();
    $query = Product::query();
    $filter->apply($query, 'featured', ['array']);

    expect($query->get())->toHaveCount(1);
});

it('works through the query builder', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Featured', 'code' => 'F01', 'featured' => true]);
    Product::create(['public_id' => 'p2', 'name' => 'Regular', 'code' => 'R01', 'featured' => false]);

    $request = \Illuminate\Http\Request::create('/', 'GET', [
        'filter' => ['featured' => 'true'],
    ]);

    $result = \BlueBeetle\ApiToolkit\QueryBuilder::for(Product::class, $request)
        ->allowedFilters(['featured' => new BooleanFilter()])
        ->apply()
        ->getQuery()
        ->get()
    ;

    expect($result)->toHaveCount(1);
    expect($result->first()->name)->toBe('Featured');
});
