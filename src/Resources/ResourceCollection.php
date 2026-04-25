<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Resources;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final readonly class ResourceCollection
{
    /**
     * @param class-string<resource> $resourceClass
     */
    public function __construct(
        private iterable $data,
        private string $resourceClass,
        private Request | null $request = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $resource = app($this->resourceClass)->withRequest($this->request);
        $items = $this->resolveItems();

        $result = [
            'data' => $items->map(fn (mixed $item): array => $resource->toArray($item))->all(),
        ];

        $included = $this->resolveIncluded($items, $resource);

        if ($included !== []) {
            $result['included'] = $included;
        }

        $paginationMeta = $this->resolvePaginationMeta();

        if ($paginationMeta !== []) {
            $result['meta'] = $paginationMeta;
            $result['links'] = $this->resolvePaginationLinks();
        }

        return $result;
    }

    private function resolveItems(): Collection
    {
        if ($this->data instanceof LengthAwarePaginator || $this->data instanceof CursorPaginator) {
            return Collection::make($this->data->items());
        }

        return Collection::make($this->data);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveIncluded(Collection $items, Resource $resource): array
    {
        $included = [];
        $seen = [];

        foreach ($resource->relationships() as $name => $relatedResourceClass) {
            $relatedResource = app($relatedResourceClass)->withRequest($this->request);

            foreach ($items as $item) {
                if (! $item instanceof \Illuminate\Database\Eloquent\Model || ! $item->relationLoaded($name)) {
                    continue;
                }

                $related = $item->getRelation($name);

                if ($related === null) {
                    continue;
                }

                $relatedItems = $related instanceof \Illuminate\Database\Eloquent\Collection
                    ? $related
                    : Collection::make([$related]);

                foreach ($relatedItems as $relatedItem) {
                    $type = $relatedResource->resolveType($relatedItem);
                    $id = $relatedResource->resolveId($relatedItem);
                    $key = $type.':'.$id;

                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $included[] = $relatedResource->toArray($relatedItem);
                }
            }
        }

        return $included;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePaginationMeta(): array
    {
        if ($this->data instanceof LengthAwarePaginator) {
            return [
                'page' => [
                    'currentPage' => $this->data->currentPage(),
                    'lastPage' => $this->data->lastPage(),
                    'perPage' => $this->data->perPage(),
                    'total' => $this->data->total(),
                ],
            ];
        }

        if ($this->data instanceof CursorPaginator) {
            return [
                'page' => [
                    'perPage' => $this->data->perPage(),
                    'hasMore' => $this->data->hasMorePages(),
                ],
            ];
        }

        return [];
    }

    /**
     * @return array<string, string|null>
     */
    private function resolvePaginationLinks(): array
    {
        if ($this->data instanceof LengthAwarePaginator) {
            return [
                'first' => $this->data->url(1),
                'last' => $this->data->url($this->data->lastPage()),
                'prev' => $this->data->previousPageUrl(),
                'next' => $this->data->nextPageUrl(),
            ];
        }

        if ($this->data instanceof CursorPaginator) {
            return [
                'prev' => $this->data->previousPageUrl(),
                'next' => $this->data->nextPageUrl(),
            ];
        }

        return [];
    }
}
