<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Http;

use Illuminate\Http\JsonResponse;

final class ErrorResponse implements \Illuminate\Contracts\Support\Responsable
{
    /**
     * @var array<string, mixed>
     */
    private array $meta = [];

    /**
     * @var array<string, mixed>
     */
    private array $source = [];

    private string | null $code = null;

    public function __construct(
        private readonly string $title,
        private readonly string | null $detail = null,
        private readonly int $status = 400,
    ) {
    }

    public function code(string $code): self
    {
        $this->code = $code;

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
     * @param array<string, mixed> $source
     */
    public function source(array $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function toResponse($request): JsonResponse
    {
        return $this->respond();
    }

    public function respond(int | null $status = null, array $headers = []): JsonResponse
    {
        return new JsonResponse(
            data: $this->toArray(),
            status: $status ?? $this->status,
            headers: array_merge(['Content-Type' => 'application/vnd.api+json'], $headers),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $error = [
            'status' => (string) $this->status,
            'title' => $this->title,
        ];

        if ($this->code !== null) {
            $error['code'] = $this->code;
        }

        if ($this->detail !== null) {
            $error['detail'] = $this->detail;
        }

        if ($this->source !== []) {
            $error['source'] = $this->source;
        }

        if ($this->meta !== []) {
            $error['meta'] = $this->meta;
        }

        return ['errors' => [$error]];
    }
}
