<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Http;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Resources\ResourceCollection;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SuccessResponse implements \Illuminate\Contracts\Support\Responsable
{
    /**
     * @var array<string, mixed>
     */
    private array $meta = [];

    /**
     * @var array<string, string>
     */
    private array $links = [];

    private Request | null $request = null;

    private int $status = 200;

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @param class-string<resource>|null $resource
     */
    public function __construct(
        private readonly mixed $data = null,
        private readonly string | null $resource = null,
    ) {
    }

    public function withRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function meta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * @param array<string, string> $links
     */
    public function links(array $links): self
    {
        $this->links = array_merge($this->links, $links);

        return $this;
    }

    public function withStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function toResponse($request): JsonResponse
    {
        return $this->respond($this->status, $this->headers);
    }

    public function respond(int $status = 200, array $headers = []): JsonResponse
    {
        $body = $this->toArray();

        return new JsonResponse(
            data: $body,
            status: $status,
            headers: array_merge(['Content-Type' => 'application/vnd.api+json'], $headers),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->data === null) {
            return $this->envelope(['data' => null]);
        }

        $resource = $this->resource ?? $this->resolveResourceFromData();

        if ($resource === null) {
            if ($this->isPaginator()) {
                return $this->envelope($this->buildRawCollectionResponse());
            }

            return $this->envelope(['data' => $this->data]);
        }

        if ($this->isCollection()) {
            $collection = new ResourceCollection(
                data: $this->data,
                resourceClass: $resource,
                request: $this->request,
            );

            return $this->envelope($collection->toArray());
        }

        $resourceInstance = app($resource)->withRequest($this->request);

        $result = ['data' => $resourceInstance->toArray($this->data)];

        return $this->envelope($result);
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function envelope(array $result): array
    {
        if ($this->meta !== []) {
            $result['meta'] = array_merge($result['meta'] ?? [], $this->meta);
        }

        if ($this->links !== []) {
            $result['links'] = array_merge($result['links'] ?? [], $this->links);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRawCollectionResponse(): array
    {
        $result = [];

        if ($this->data instanceof LengthAwarePaginator) {
            $result['data'] = array_values($this->data->items());
            $result['meta'] = [
                'page' => [
                    'currentPage' => $this->data->currentPage(),
                    'lastPage' => $this->data->lastPage(),
                    'perPage' => $this->data->perPage(),
                    'total' => $this->data->total(),
                ],
            ];
            $result['links'] = [
                'first' => $this->data->url(1),
                'last' => $this->data->url($this->data->lastPage()),
                'prev' => $this->data->previousPageUrl(),
                'next' => $this->data->nextPageUrl(),
            ];
        } elseif ($this->data instanceof CursorPaginator) {
            $result['data'] = array_values($this->data->items());
            $result['meta'] = [
                'page' => [
                    'perPage' => $this->data->perPage(),
                    'hasMore' => $this->data->hasMorePages(),
                ],
            ];
            $result['links'] = [
                'prev' => $this->data->previousPageUrl(),
                'next' => $this->data->nextPageUrl(),
            ];
        } else {
            $items = $this->data instanceof Collection || $this->data instanceof EloquentCollection
                ? $this->data->all()
                : $this->data;

            $result['data'] = array_values((array) $items);
        }

        return $result;
    }

    /**
     * @return class-string<resource>|null
     */
    private function resolveResourceFromData(): string | null
    {
        $item = $this->data;

        if ($item instanceof LengthAwarePaginator || $item instanceof CursorPaginator) {
            $items = $item->items();
            $item = $items[0] ?? null;
        } elseif ($item instanceof Collection || $item instanceof EloquentCollection) {
            $item = $item->first();
        } elseif (is_array($item)) {
            $item = $item[0] ?? null;
        }

        if ($item === null) {
            return null;
        }

        return Resource::resolveResourceClass($item);
    }

    private function isCollection(): bool
    {
        return $this->isPaginator()
            || $this->data instanceof Collection
            || $this->data instanceof EloquentCollection
            || is_array($this->data);
    }

    private function isPaginator(): bool
    {
        return $this->data instanceof LengthAwarePaginator
            || $this->data instanceof CursorPaginator;
    }
}
