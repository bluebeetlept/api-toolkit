<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\SortParser;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Http\Request;

it('sorts ascending by default', function () {
    $parser = new SortParser(allowed: ['name']);

    $request = Request::create('/', 'GET', ['sort' => 'name']);
    $query = Product::query();

    $parser->apply($request, $query);

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->toHaveCount(1);
    expect($orders[0]['column'])->toBe('name');
    expect($orders[0]['direction'])->toBe('asc');
});

it('sorts descending with dash prefix', function () {
    $parser = new SortParser(allowed: ['created_at']);

    $request = Request::create('/', 'GET', ['sort' => '-created_at']);
    $query = Product::query();

    $parser->apply($request, $query);

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->toHaveCount(1);
    expect($orders[0]['column'])->toBe('created_at');
    expect($orders[0]['direction'])->toBe('desc');
});

it('handles multiple sort fields', function () {
    $parser = new SortParser(allowed: ['name', 'created_at']);

    $request = Request::create('/', 'GET', ['sort' => '-created_at,name']);
    $query = Product::query();

    $parser->apply($request, $query);

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->toHaveCount(2);
    expect($orders[0]['column'])->toBe('created_at');
    expect($orders[0]['direction'])->toBe('desc');
    expect($orders[1]['column'])->toBe('name');
    expect($orders[1]['direction'])->toBe('asc');
});

it('ignores disallowed sort fields', function () {
    $parser = new SortParser(allowed: ['name']);

    $request = Request::create('/', 'GET', ['sort' => 'secret_column']);
    $query = Product::query();

    $parser->apply($request, $query);

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->toBeEmpty();
});

it('applies default sort when no sort parameter', function () {
    $parser = new SortParser(allowed: ['created_at'], default: '-created_at');

    $request = Request::create('/');
    $query = Product::query();

    $parser->apply($request, $query);

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->toHaveCount(1);
    expect($orders[0]['column'])->toBe('created_at');
    expect($orders[0]['direction'])->toBe('desc');
});

it('does nothing when no sort and no default', function () {
    $parser = new SortParser(allowed: ['name']);

    $request = Request::create('/');
    $query = Product::query();

    $parser->apply($request, $query);

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->toBeEmpty();
});

it('skips empty sort fields from trailing commas', function () {
    $parser = new SortParser(allowed: ['name', 'code']);

    $request = Request::create('/', 'GET', ['sort' => 'name,,code']);
    $query = Product::query();

    $parser->apply($request, $query);

    $orders = $query->getQuery()->orders ?? [];
    expect($orders)->toHaveCount(2);
    expect($orders[0]['column'])->toBe('name');
    expect($orders[1]['column'])->toBe('code');
});
