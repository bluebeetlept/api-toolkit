<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class IncludeParser
{
    /**
     * @param list<string> $allowed
     */
    public function __construct(
        private array $allowed = [],
    ) {
    }

    public function apply(Request $request, Builder $query): Builder
    {
        $includes = $this->parse($request);

        if ($includes === []) {
            return $query;
        }

        $query->with($includes);

        return $query;
    }

    /**
     * @return list<string>
     */
    public function parse(Request $request): array
    {
        $include = $request->query('include', '');

        if ($include === '' || $include === null) {
            return [];
        }

        $requested = array_map('trim', explode(',', $include));

        if ($this->allowed === []) {
            return $requested;
        }

        return array_values(
            array_filter($requested, fn (string $name): bool => in_array($name, $this->allowed, true)),
        );
    }
}
