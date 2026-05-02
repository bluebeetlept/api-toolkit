<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Filters;

use BlueBeetle\ApiToolkit\Parsers\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class OpenApiStubCustomFilter implements Filter
{
    public function apply(Builder $query, string $field, mixed $value): void
    {
        $query->where($field, $value);
    }
}
