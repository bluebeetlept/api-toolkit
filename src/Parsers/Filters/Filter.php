<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers\Filters;

use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    public function apply(Builder $query, string $field, mixed $value): void;
}
