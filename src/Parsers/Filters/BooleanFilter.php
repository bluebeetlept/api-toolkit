<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers\Filters;

use Illuminate\Database\Eloquent\Builder;

final readonly class BooleanFilter implements Filter
{
    public function apply(Builder $query, string $field, mixed $value): void
    {
        $resolved = $this->resolve($value);

        if ($resolved === null) {
            return;
        }

        $query->where($field, $resolved);
    }

    private function resolve(mixed $value): bool | null
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
}
