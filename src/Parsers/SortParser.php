<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Parsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class SortParser
{
    /**
     * @param list<string> $allowed
     * @param string|null  $default Default sort field (prefix with - for desc, e.g. "-created_at")
     */
    public function __construct(
        private array $allowed = [],
        private string | null $default = null,
    ) {
    }

    public function apply(Request $request, Builder $query): Builder
    {
        $sort = $request->query('sort', $this->default);

        if ($sort === null || $sort === '') {
            return $query;
        }

        $fields = explode(',', $sort);

        foreach ($fields as $field) {
            $field = mb_trim($field);

            if ($field === '') {
                continue;
            }

            $direction = 'asc';

            if (str_starts_with($field, '-')) {
                $direction = 'desc';
                $field = mb_substr($field, 1);
            }

            if ($this->allowed !== [] && ! in_array($field, $this->allowed, true)) {
                continue;
            }

            $query->orderBy($field, $direction);
        }

        return $query;
    }
}
