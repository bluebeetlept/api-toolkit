<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\ScopeFilter;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

it('delegates to eloquent scope using field name', function () {
    $filter = new ScopeFilter();

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('active')->once()->with('yes');

    $filter->apply($query, 'active', 'yes');
});

it('delegates to custom scope name', function () {
    $filter = new ScopeFilter(scopeName: 'whereActive');

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereActive')->once()->with('yes');

    $filter->apply($query, 'status', 'yes');
});
