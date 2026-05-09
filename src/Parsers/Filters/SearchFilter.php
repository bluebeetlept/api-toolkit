<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers\Filters;

use Illuminate\Database\Eloquent\Builder;

final readonly class SearchFilter implements Filter
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        private array $columns,
    ) {
    }

    public function apply(Builder $query, string $field, mixed $value): void
    {
        if ($this->columns === [] || ! is_string($value) || $value === '') {
            return;
        }

        $query->where(function (Builder $query) use ($value): void {
            foreach ($this->columns as $column) {
                $query->orWhere($column, 'LIKE', '%'.$value.'%');
            }
        });
    }
}
