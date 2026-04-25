<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Http;

use Eufaturo\ApiToolkit\Resources\Resource;
use Eufaturo\ApiToolkit\Resources\ResourceCollection;
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

    public function toResponse($request): JsonResponse
    {
        return $this->respond();
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

        if ($this->resource === null) {
            return $this->envelope(['data' => $this->data]);
        }

        if ($this->isCollection()) {
            $collection = new ResourceCollection(
                data: $this->data,
                resourceClass: $this->resource,
                request: $this->request,
            );

            return $this->envelope($collection->toArray());
        }

        $resource = app($this->resource)->withRequest($this->request);

        $result = ['data' => $resource->toArray($this->data)];

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

    private function isCollection(): bool
    {
        return $this->data instanceof LengthAwarePaginator
            || $this->data instanceof CursorPaginator
            || $this->data instanceof Collection
            || $this->data instanceof EloquentCollection
            || is_array($this->data);
    }
}
