<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers\Filters;

use Illuminate\Database\Eloquent\Builder;

final readonly class ScopeFilter implements Filter
{
    public function __construct(
        private string | null $scopeName = null,
    ) {
    }

    public function apply(Builder $query, string $field, mixed $value): void
    {
        $scope = $this->scopeName ?? $field;

        $query->{$scope}($value); // @phpstan-ignore method.dynamicName
    }
}
