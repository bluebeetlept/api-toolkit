<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers\Filters;

use Illuminate\Database\Eloquent\Builder;

final readonly class OperatorFilter implements Filter
{
    private const array OPERATORS = [
        'eq' => '=',
        'neq' => '!=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
    ];

    public function apply(Builder $query, string $field, mixed $value): void
    {
        if (is_array($value)) {
            $this->applyOperators($query, $field, $value);

            return;
        }

        $query->where($field, '=', $value);
    }

    private function applyOperators(Builder $query, string $field, array $value): void
    {
        foreach ($value as $operator => $operand) {
            if (! is_string($operator) || ! isset(self::OPERATORS[$operator])) {
                continue;
            }

            $query->where($field, self::OPERATORS[$operator], $operand);
        }
    }
}
