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
        $requested = $this->requested($request);

        if ($requested === []) {
            return [];
        }

        if ($this->allowed === []) {
            return $requested;
        }

        return array_values(
            array_filter($requested, fn (string $name): bool => in_array($name, $this->allowed, true)),
        );
    }

    /**
     * The requested includes outside the allowed ones; with none allowed, every requested include.
     *
     * @return list<string>
     */
    public function unknown(Request $request): array
    {
        return array_values(array_diff($this->requested($request), $this->allowed));
    }

    /**
     * @return list<string>
     */
    private function requested(Request $request): array
    {
        $include = $request->query('include', '');

        if ($include === '' || $include === null) {
            return [];
        }

        $names = is_array($include) ? $include : explode(',', $include);

        return array_values(array_filter(array_map(static fn (mixed $name): string => mb_trim((string) $name), $names), static fn (string $name): bool => $name !== ''));
    }
}
