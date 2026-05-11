<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers\Filters;

use Illuminate\Database\Eloquent\Builder;

final readonly class ColumnFilter implements Filter
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
        if (! is_array($value)) {
            $this->applyScalar($query, $field, $value);

            return;
        }

        foreach ($value as $operator => $operand) {
            if (! is_string($operator)) {
                continue;
            }

            match ($operator) {
                'eq', 'neq', 'gt', 'gte', 'lt', 'lte' => $query->where($field, self::OPERATORS[$operator], $operand),
                'in' => $query->whereIn($field, $this->toList($operand)),
                'not_in' => $query->whereNotIn($field, $this->toList($operand)),
                'null' => $this->applyNull($query, $field, $operand),
                'like' => $query->where($field, 'LIKE', '%'.$operand.'%'),
                default => null,
            };
        }
    }

    private function applyScalar(Builder $query, string $field, mixed $value): void
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return;
        }

        $query->where($field, '=', $value);
    }

    private function applyNull(Builder $query, string $field, mixed $value): void
    {
        $isNull = $this->resolveBool($value);

        if ($isNull === null) {
            return;
        }

        $isNull ? $query->whereNull($field) : $query->whereNotNull($field);
    }

    private function resolveBool(mixed $value): bool | null
    {
        if (is_bool($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return match (mb_strtolower($value)) {
            'true', '1', 'yes' => true,
            'false', '0', 'no' => false,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function toList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        return array_map('trim', explode(',', $value));
    }
}
