<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\DateFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;

it('filters by exact date', function () {
    $filter = new DateFilter();

    $query = Product::query();
    $filter->apply($query, 'created_at', '2025-01-15');

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('created_at');
    expect($bindings)->toContain('2025-01-15');
});

it('filters by date range with from', function () {
    $filter = new DateFilter();

    $query = Product::query();
    $filter->apply($query, 'created_at', ['from' => '2025-01-01']);

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('created_at');
    expect($sql)->toContain('>=');
    expect($bindings)->toContain('2025-01-01');
});

it('filters by date range with from and to', function () {
    $filter = new DateFilter();

    $query = Product::query();
    $filter->apply($query, 'created_at', ['from' => '2025-01-01', 'to' => '2025-12-31']);

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('created_at');
    expect($bindings)->toContain('2025-01-01');
    expect($bindings)->toContain('2025-12-31');
});
