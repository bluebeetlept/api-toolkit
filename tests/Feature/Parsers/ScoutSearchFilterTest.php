<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\ScoutSearchFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\SearchableProduct;

beforeEach(function () {
    $this->app->register(\Laravel\Scout\ScoutServiceProvider::class);
    $this->app['config']->set('scout.driver', 'collection');
});

it('constrains query using scout search results', function () {
    $p1 = SearchableProduct::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);
    $p2 = SearchableProduct::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01']);
    $p3 = SearchableProduct::create(['public_id' => 'p3', 'name' => 'Widget Pro', 'code' => 'W02']);

    $filter = new ScoutSearchFilter();
    $query = SearchableProduct::query();
    $filter->apply($query, 'search', 'Widget');

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('public_id')->all())->toContain('p1')->toContain('p3');
});

it('returns no results when scout finds nothing', function () {
    SearchableProduct::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new ScoutSearchFilter();
    $query = SearchableProduct::query();
    $filter->apply($query, 'search', 'zzzznonexistent');

    expect($query->get())->toHaveCount(0);
});

it('preserves other query constraints', function () {
    SearchableProduct::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'status' => 'active']);
    SearchableProduct::create(['public_id' => 'p2', 'name' => 'Widget Pro', 'code' => 'W02', 'status' => 'inactive']);

    $filter = new ScoutSearchFilter();
    $query = SearchableProduct::where('status', 'active');
    $filter->apply($query, 'search', 'Widget');

    $results = $query->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->public_id)->toBe('p1');
});

it('does nothing with empty value', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new ScoutSearchFilter();
    $query = Product::query();
    $filter->apply($query, 'search', '');

    expect($query->get())->toHaveCount(1);
});

it('does nothing with non-string value', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new ScoutSearchFilter();
    $query = Product::query();
    $filter->apply($query, 'search', ['array']);

    expect($query->get())->toHaveCount(1);
});

it('does nothing when model does not have search method', function () {
    Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

    $filter = new ScoutSearchFilter();
    $query = Product::query();
    $filter->apply($query, 'search', 'widget');

    expect($query->get())->toHaveCount(1);
});

it('works through the query builder', function () {
    SearchableProduct::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);
    SearchableProduct::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01']);

    $request = \Illuminate\Http\Request::create('/', 'GET', [
        'filter' => ['search' => 'Widget'],
    ]);

    $result = \BlueBeetle\ApiToolkit\QueryBuilder::for(SearchableProduct::class, $request)
        ->allowedFilters(['search' => new ScoutSearchFilter()])
        ->apply()
        ->getQuery()
        ->get()
    ;

    expect($result)->toHaveCount(1);
    expect($result->first()->name)->toBe('Widget');
});
