<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers\Filters;

use Illuminate\Database\Eloquent\Builder;

final readonly class ScoutSearchFilter implements Filter
{
    public function apply(Builder $query, string $field, mixed $value): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $model = $query->getModel();

        if (! method_exists($model, 'search')) {
            return;
        }

        $ids = $model::search($value)->keys()->all();

        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($model->getQualifiedKeyName(), $ids);
    }
}
