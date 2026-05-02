<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature;

use BlueBeetle\ApiToolkit\Http\SuccessResponse;
use BlueBeetle\ApiToolkit\Parsers\Filters\ExactFilter;
use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\StubModel;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\StubQueryResource;
use Illuminate\Http\Request;

it('creates a query builder from a model class', function () {
    $request = Request::create('/');

    $builder = QueryBuilder::for(StubModel::class, $request);

    expect($builder)->toBeInstanceOf(QueryBuilder::class);
});

it('creates a query builder from an existing builder', function () {
    $request = Request::create('/');
    $eloquentBuilder = StubModel::query();

    $builder = QueryBuilder::for($eloquentBuilder, $request);

    expect($builder)->toBeInstanceOf(QueryBuilder::class);
});

it('applies filters from resource', function () {
    $request = Request::create('/', 'GET', ['filter' => ['name' => 'Widget']]);

    $builder = QueryBuilder::for(StubModel::class, $request)
        ->fromResource(StubQueryResource::class)
    ;

    $query = $builder->apply()->getQuery();
    $sql = $query->toSql();

    expect(mb_strtolower($sql))->toContain('like');
});

it('overrides resource filters with explicit filters', function () {
    $request = Request::create('/', 'GET', ['filter' => ['name' => 'Widget']]);

    $builder = QueryBuilder::for(StubModel::class, $request)
        ->fromResource(StubQueryResource::class)
        ->allowedFilters(['name' => new ExactFilter()])
    ;

    $query = $builder->apply()->getQuery();
    $sql = $query->toSql();

    expect(mb_strtolower($sql))->not->toContain('like');
});

it('applies sorts from resource', function () {
    $request = Request::create('/');

    $builder = QueryBuilder::for(StubModel::class, $request)
        ->fromResource(StubQueryResource::class)
    ;

    $query = $builder->apply()->getQuery();

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->not->toBeEmpty();
    expect($orders[0]['column'])->toBe('created_at');
    expect($orders[0]['direction'])->toBe('desc');
});

it('overrides default sort', function () {
    $request = Request::create('/');

    $builder = QueryBuilder::for(StubModel::class, $request)
        ->fromResource(StubQueryResource::class)
        ->defaultSort('name')
    ;

    $query = $builder->apply()->getQuery();

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->not->toBeEmpty();
    expect($orders[0]['column'])->toBe('name');
    expect($orders[0]['direction'])->toBe('asc');
});

it('applies includes from request', function () {
    $request = Request::create('/', 'GET', ['include' => 'category']);

    $builder = QueryBuilder::for(StubModel::class, $request)
        ->fromResource(StubQueryResource::class)
    ;

    $query = $builder->apply()->getQuery();

    $eagerLoads = $query->getEagerLoads();
    expect($eagerLoads)->toHaveKey('category');
});

it('works without a resource', function () {
    $request = Request::create('/', 'GET', ['filter' => ['status' => 'active']]);

    $builder = QueryBuilder::for(StubModel::class, $request)
        ->allowedFilters(['status' => new ExactFilter()])
        ->allowedSorts(['name', 'created_at'])
        ->defaultSort('-created_at')
    ;

    $query = $builder->apply()->getQuery();
    $sql = $query->toSql();

    expect($sql)->toContain('status');
});

it('ignores disallowed includes', function () {
    $request = Request::create('/', 'GET', ['include' => 'category,secret']);

    $builder = QueryBuilder::for(StubModel::class, $request)
        ->allowedIncludes(['category'])
    ;

    $query = $builder->apply()->getQuery();

    $eagerLoads = $query->getEagerLoads();
    expect($eagerLoads)->toHaveKey('category');
    expect($eagerLoads)->not->toHaveKey('secret');
});

it('does nothing with no configuration', function () {
    $request = Request::create('/', 'GET', [
        'filter' => ['status' => 'active'],
        'sort' => 'name',
        'include' => 'category',
    ]);

    $builder = QueryBuilder::for(StubModel::class, $request);
    $query = $builder->getQuery();

    expect($query->toSql())->not->toContain('status');
    expect($query->getQuery()->orders ?? [])->toBeEmpty();
    expect($query->getEagerLoads())->toBeEmpty();
});

it('returns a SuccessResponse from paginate', function () {
    Product::create([
        'public_id' => 'prod-1',
        'name' => 'Widget',
        'code' => 'W01',
        'price_in_cents' => 1000,
        'featured' => false,
    ]);

    $request = Request::create('/', 'GET', ['page' => ['size' => 10]]);

    $result = QueryBuilder::for(Product::class, $request)
        ->fromResource(ProductResource::class)
        ->paginate()
    ;

    expect($result)->toBeInstanceOf(SuccessResponse::class);

    $array = $result->toArray();
    expect($array)->toHaveKey('data');
    expect($array)->toHaveKey('meta');
    expect($array)->toHaveKey('links');
});

it('returns a SuccessResponse from cursorPaginate', function () {
    Product::create([
        'public_id' => 'prod-1',
        'name' => 'Widget',
        'code' => 'W01',
        'price_in_cents' => 1000,
        'featured' => false,
    ]);

    $request = Request::create('/', 'GET', ['page' => ['size' => 10]]);

    $result = QueryBuilder::for(Product::class, $request)
        ->fromResource(ProductResource::class)
        ->cursorPaginate()
    ;

    expect($result)->toBeInstanceOf(SuccessResponse::class);

    $array = $result->toArray();
    expect($array)->toHaveKey('data');
    expect($array)->toHaveKey('meta');
});

it('returns a SuccessResponse from get', function () {
    Product::create([
        'public_id' => 'prod-1',
        'name' => 'Widget',
        'code' => 'W01',
        'price_in_cents' => 1000,
        'featured' => false,
    ]);

    $request = Request::create('/');

    $result = QueryBuilder::for(Product::class, $request)
        ->fromResource(ProductResource::class)
        ->get()
    ;

    expect($result)->toBeInstanceOf(SuccessResponse::class);

    $array = $result->toArray();
    expect($array)->toHaveKey('data');
});

it('returns the builder for further chaining from apply', function () {
    $request = Request::create('/', 'GET', ['filter' => ['status' => 'active']]);

    $builder = QueryBuilder::for(StubModel::class, $request)
        ->allowedFilters(['status' => new ExactFilter()])
        ->apply()
    ;

    expect($builder)->toBeInstanceOf(QueryBuilder::class);

    $query = $builder->getQuery();
    expect($query->toSql())->toContain('status');
});

it('applies filters and sorts before paginating', function () {
    Product::create([
        'public_id' => 'prod-1',
        'name' => 'Widget',
        'code' => 'W01',
        'price_in_cents' => 1000,
        'featured' => false,
        'status' => 'active',
    ]);

    Product::create([
        'public_id' => 'prod-2',
        'name' => 'Gadget',
        'code' => 'G01',
        'price_in_cents' => 2000,
        'featured' => false,
        'status' => 'archived',
    ]);

    $request = Request::create('/', 'GET', [
        'filter' => ['status' => 'active'],
        'page' => ['size' => 10],
    ]);

    $result = QueryBuilder::for(Product::class, $request)
        ->fromResource(ProductResource::class)
        ->allowedFilters(['status' => new ExactFilter()])
        ->paginate()
    ;

    $array = $result->toArray();
    expect($array['data'])->toHaveCount(1);
});
