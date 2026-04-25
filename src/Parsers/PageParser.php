<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers;

use Illuminate\Http\Request;

final readonly class PageParser
{
    public function __construct(
        private int $defaultSize = 20,
        private int $maxSize = 100,
    ) {
    }

    /**
     * Determine if the request uses cursor-based pagination.
     */
    public function isCursor(Request $request): bool
    {
        $page = $request->query('page', []);

        if (! is_array($page)) {
            return false;
        }

        return isset($page['cursor']);
    }

    /**
     * Get the page size from the request.
     */
    public function getSize(Request $request): int
    {
        $page = $request->query('page', []);

        if (! is_array($page)) {
            return $this->defaultSize;
        }

        $size = (int) ($page['size'] ?? $this->defaultSize);

        return min(max($size, 1), $this->maxSize);
    }

    /**
     * Get the page number for offset pagination.
     */
    public function getNumber(Request $request): int
    {
        $page = $request->query('page', []);

        if (! is_array($page)) {
            return 1;
        }

        return max((int) ($page['number'] ?? 1), 1);
    }

    /**
     * Get the cursor value for cursor pagination.
     */
    public function getCursor(Request $request): string | null
    {
        $page = $request->query('page', []);

        if (! is_array($page)) {
            return null;
        }

        return $page['cursor'] ?? null;
    }
}
