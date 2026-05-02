<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\FilterParser;
use BlueBeetle\ApiToolkit\Parsers\Filters\ExactFilter;
use BlueBeetle\ApiToolkit\Parsers\Filters\PartialFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Http\Request;

it('applies exact filter', function () {
    $parser = new FilterParser([
        'status' => new ExactFilter(),
    ]);

    $request = Request::create('/', 'GET', ['filter' => ['status' => 'active']]);
    $query = Product::query();

    $parser->apply($request, $query);

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('status');
    expect($sql)->toContain('=');
    expect($bindings)->toContain('active');
});

it('applies exact filter with array value as whereIn', function () {
    $parser = new FilterParser([
        'status' => new ExactFilter(),
    ]);

    $request = Request::create('/', 'GET', ['filter' => ['status' => ['active', 'pending']]]);
    $query = Product::query();

    $parser->apply($request, $query);

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('status');
    expect(mb_strtolower($sql))->toContain('in');
    expect($bindings)->toContain('active');
    expect($bindings)->toContain('pending');
});

it('applies partial filter', function () {
    $parser = new FilterParser([
        'name' => new PartialFilter(),
    ]);

    $request = Request::create('/', 'GET', ['filter' => ['name' => 'wid']]);
    $query = Product::query();

    $parser->apply($request, $query);

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('name');
    expect(mb_strtolower($sql))->toContain('like');
    expect($bindings)->toContain('%wid%');
});

it('ignores unknown filter fields', function () {
    $parser = new FilterParser([
        'status' => new ExactFilter(),
    ]);

    $request = Request::create('/', 'GET', ['filter' => ['unknown' => 'value']]);
    $query = Product::query();

    $parser->apply($request, $query);

    $sql = $query->toSql();

    expect($sql)->not->toContain('unknown');
});

it('handles missing filter parameter', function () {
    $parser = new FilterParser([
        'status' => new ExactFilter(),
    ]);

    $request = Request::create('/');
    $query = Product::query();

    $parser->apply($request, $query);

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($bindings)->toBeEmpty();
});

it('returns allowed fields', function () {
    $parser = new FilterParser([
        'status' => new ExactFilter(),
        'name' => new PartialFilter(),
    ]);

    expect($parser->allowedFields())->toBe(['status', 'name']);
});

it('resolves filter from class string', function () {
    $parser = new FilterParser([
        'status' => ExactFilter::class,
    ]);

    $request = Request::create('/', 'GET', ['filter' => ['status' => 'active']]);
    $query = Product::query();

    $parser->apply($request, $query);

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('status');
    expect($bindings)->toContain('active');
});

it('handles non-array filter parameter', function () {
    $parser = new FilterParser([
        'status' => new ExactFilter(),
    ]);

    $request = Request::create('/', 'GET', ['filter' => 'not-an-array']);
    $query = Product::query();

    $parser->apply($request, $query);

    expect($query->getBindings())->toBeEmpty();
});
