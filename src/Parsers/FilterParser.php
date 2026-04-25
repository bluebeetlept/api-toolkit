<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class FilterParser
{
    /**
     * @param array<string, class-string<Filter>|Filter> $allowed
     */
    public function __construct(
        private array $allowed = [],
    ) {
    }

    public function apply(Request $request, Builder $query): Builder
    {
        $filters = $request->query('filter', []);

        if (! is_array($filters)) {
            return $query;
        }

        foreach ($filters as $field => $value) {
            if (! isset($this->allowed[$field])) {
                continue;
            }

            $filter = $this->resolveFilter($this->allowed[$field]);

            $filter->apply($query, $field, $value);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    public function allowedFields(): array
    {
        return array_keys($this->allowed);
    }

    private function resolveFilter(Filter | string $filter): Filter
    {
        if ($filter instanceof Filter) {
            return $filter;
        }

        return new $filter();
    }
}
