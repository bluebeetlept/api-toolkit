<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers\Filters;

use Illuminate\Database\Eloquent\Builder;

final readonly class DateFilter implements Filter
{
    public function apply(Builder $query, string $field, mixed $value): void
    {
        if (! is_array($value)) {
            $query->whereDate($field, '=', $value);

            return;
        }

        if (isset($value['from'])) {
            $query->whereDate($field, '>=', $value['from']);
        }

        if (isset($value['to'])) {
            $query->whereDate($field, '<=', $value['to']);
        }
    }
}
