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
        $page = $this->pageArray($request);

        return isset($page['cursor']);
    }

    /**
     * Get the page size from the request.
     */
    public function getSize(Request $request): int
    {
        $page = $this->pageArray($request);
        $size = (int) ($page['size'] ?? $this->defaultSize);

        return min(max($size, 1), $this->maxSize);
    }

    /**
     * Get the page number for offset pagination.
     */
    public function getNumber(Request $request): int
    {
        $page = $this->pageArray($request);

        return max((int) ($page['number'] ?? 1), 1);
    }

    /**
     * Get the cursor value for cursor pagination.
     */
    public function getCursor(Request $request): string | null
    {
        $page = $this->pageArray($request);

        return $page['cursor'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function pageArray(Request $request): array
    {
        $page = $request->query('page', []);

        return is_array($page) ? $page : [];
    }
}
