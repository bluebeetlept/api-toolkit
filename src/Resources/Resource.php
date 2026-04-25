<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Resources;

use BadMethodCallException;
use Closure;
use BlueBeetle\ApiToolkit\Parsers\Filters\Filter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @template TModel
 *
 * Child classes should implement the following methods with their own type hints:
 *
 * - attributes(MyModel $model): array       (required)
 * - self(MyModel $model): string|null        (optional)
 * - links(MyModel $model): array             (optional)
 * - meta(MyModel $model): array              (optional)
 */
class Resource
{
    /** @var (Closure(mixed, Request|null): string)|null */
    private static Closure | null $idResolver = null;

    /** @var (Closure(mixed, Request|null): string)|null */
    private static Closure | null $typeResolver = null;

    /**
     * The model class this resource represents.
     * When set to an Eloquent model, the JSON:API type is derived from its table name.
     *
     * @var class-string<TModel>
     */
    protected string $model = '';

    /**
     * Override the JSON:API type. When empty, derived from the model's table name.
     */
    protected string $type = '';

    /**
     * The current request instance, available in all methods via $this->request.
     */
    protected Request | null $request = null;

    /**
     * Register a global callback to resolve the resource ID.
     *
     * @param Closure(mixed, Request|null): string $callback
     */
    public static function resolveIdUsing(Closure $callback): void
    {
        self::$idResolver = $callback;
    }

    /**
     * Register a global callback to resolve the resource type.
     *
     * @param Closure(mixed, Request|null): string $callback
     */
    public static function resolveTypeUsing(Closure $callback): void
    {
        self::$typeResolver = $callback;
    }

    /**
     * Reset global resolvers (useful for testing).
     */
    public static function resetResolvers(): void
    {
        self::$idResolver = null;
        self::$typeResolver = null;
    }

    public static function make(mixed ...$data): array | null
    {
        return app(static::class)->toArray(...$data);
    }

    public function withRequest(Request | null $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param TModel|null $model
     */
    public function toArray($model = null): array | null
    {
        if ($model === null) {
            return null;
        }

        if (! method_exists($this, 'attributes')) {
            throw new BadMethodCallException(
                static::class.' must implement an attributes() method.',
            );
        }

        return [
            'type' => $this->resolveType($model),
            'id' => $this->resolveId($model),
            'attributes' => $this->attributes($model),
            'relationships' => $this->resolveRelationships($model),
            'links' => $this->resolveLinks($model),
            'meta' => $this->resolveMeta($model),
        ];
    }

    /**
     * Define the OpenAPI schema for this resource's attributes.
     * Override to provide explicit type definitions for OpenAPI generation.
     * When empty, types are inferred from the model's casts and columns.
     *
     * @return array<string, array<string, mixed>|string>
     */
    public function schema(): array
    {
        return [];
    }

    /**
     * Define the resource relationships.
     * Maps relationship name to Resource class.
     *
     * @return array<string, class-string<resource>>
     */
    public function relationships(): array
    {
        return [];
    }

    /**
     * Define the allowed filters for this resource.
     *
     * @return array<string, class-string<Filter>|Filter>
     */
    public function allowedFilters(): array
    {
        return [];
    }

    /**
     * Define the allowed sort fields for this resource.
     *
     * @return list<string>
     */
    public function allowedSorts(): array
    {
        return [];
    }

    /**
     * Define the default sort for this resource.
     */
    public function defaultSort(): string | null
    {
        return null;
    }

    /**
     * Define the allowed includes for this resource.
     * Defaults to the keys of relationships() if not overridden.
     *
     * @return list<string>
     */
    public function allowedIncludes(): array
    {
        return array_keys($this->relationships());
    }

    /**
     * @return class-string<TModel>|string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    public function resolveType($model = null): string
    {
        if ($this->type !== '') {
            return $this->type;
        }

        if (self::$typeResolver !== null && $model !== null) {
            return (self::$typeResolver)($model, $this->request);
        }

        if ($this->model !== '') {
            return (new $this->model())->getTable();
        }

        if ($model instanceof Model) {
            return $model->getTable();
        }

        if ($model !== null) {
            return Str::snake(class_basename($model), '-');
        }

        return '';
    }

    public function resolveId($model): string
    {
        if (self::$idResolver !== null) {
            return (self::$idResolver)($model, $this->request);
        }

        if ($model instanceof Model) {
            return (string) ($model->public_id ?? $model->getKey());
        }

        if (is_object($model) && property_exists($model, 'id')) {
            return (string) $model->id;
        }

        if (is_object($model) && property_exists($model, 'public_id')) {
            return (string) $model->public_id;
        }

        return '';
    }

    /**
     * @param mixed $model
     *
     * @return array<string, string>
     */
    private function resolveLinks($model): array
    {
        $links = [];

        if (method_exists($this, 'self')) {
            $self = $this->self($model);

            if ($self !== null) {
                $links['self'] = $self;
            }
        }

        if (method_exists($this, 'links')) {
            $links = array_merge($links, $this->links($model));
        }

        return $links;
    }

    /**
     * @param mixed $model
     *
     * @return array<string, mixed>
     */
    private function resolveMeta($model): array
    {
        if (method_exists($this, 'meta')) {
            return $this->meta($model);
        }

        return [];
    }

    /**
     * @param mixed $model
     *
     * @return array<string, array{data: array|null}>
     */
    private function resolveRelationships($model): array
    {
        $resolved = [];

        foreach ($this->relationships() as $name => $resourceClass) {
            if (! $model instanceof Model || ! $model->relationLoaded($name)) {
                continue;
            }

            $related = $model->getRelation($name);

            if ($related === null) {
                $resolved[$name] = ['data' => null];

                continue;
            }

            if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                $resource = app($resourceClass)->withRequest($this->request);
                $resolved[$name] = [
                    'data' => $related->map(fn (mixed $item): array => [
                        'type' => $resource->resolveType($item),
                        'id' => $resource->resolveId($item),
                    ])->all(),
                ];

                continue;
            }

            $resource = app($resourceClass)->withRequest($this->request);
            $resolved[$name] = [
                'data' => [
                    'type' => $resource->resolveType($related),
                    'id' => $resource->resolveId($related),
                ],
            ];
        }

        return $resolved;
    }
}
