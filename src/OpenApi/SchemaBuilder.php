<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\OpenApi;

use Eufaturo\ApiToolkit\Parsers\Filters\DateFilter;
use Eufaturo\ApiToolkit\Parsers\Filters\ExactFilter;
use Eufaturo\ApiToolkit\Parsers\Filters\PartialFilter;
use Eufaturo\ApiToolkit\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

final class SchemaBuilder
{
    /**
     * Build an OpenAPI schema for a JSON:API resource object.
     *
     * @param class-string<resource> $resourceClass
     *
     * @return array<string, mixed>
     */
    public function buildResourceSchema(string $resourceClass): array
    {
        $resource = app($resourceClass);
        $type = $resource->resolveType();
        $schemaName = $this->schemaName($resourceClass);

        $attributesSchema = $this->buildAttributesSchema($resource);
        $relationshipsSchema = $this->buildRelationshipsSchema($resource);

        $properties = [
            'type' => [
                'type' => 'string',
                'example' => $type,
            ],
            'id' => [
                'type' => 'string',
                'example' => '1',
            ],
            'attributes' => $attributesSchema,
            'relationships' => $relationshipsSchema,
            'links' => [
                'type' => 'object',
                'additionalProperties' => ['type' => 'string'],
            ],
            'meta' => [
                'type' => 'object',
                'additionalProperties' => true,
            ],
        ];

        return [
            'type' => 'object',
            'required' => ['type', 'id', 'attributes'],
            'properties' => $properties,
        ];
    }

    /**
     * Build the attributes schema by reading the model's column types and casts.
     *
     * @return array<string, mixed>
     */
    public function buildAttributesSchema(Resource $resource): array
    {
        $schema = $resource->schema();

        if ($schema !== []) {
            return [
                'type' => 'object',
                'properties' => $this->normalizeSchemaProperties($schema),
            ];
        }

        $modelClass = $resource->getModel();

        if ($modelClass === '' || ! is_subclass_of($modelClass, Model::class)) {
            return ['type' => 'object', 'additionalProperties' => true];
        }

        $model = new $modelClass();
        $properties = $this->inferPropertiesFromModel($model);

        if ($properties === []) {
            return ['type' => 'object', 'additionalProperties' => true];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * Build the relationships schema from the resource's relationships.
     *
     * @return array<string, mixed>
     */
    public function buildRelationshipsSchema(Resource $resource): array
    {
        $relationships = $resource->relationships();

        if ($relationships === []) {
            return ['type' => 'object'];
        }

        $properties = [];

        foreach ($relationships as $name => $relatedResourceClass) {
            $relatedResource = app($relatedResourceClass);
            $relatedType = $relatedResource->resolveType();

            $properties[$name] = [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'oneOf' => [
                            [
                                'type' => 'object',
                                'required' => ['type', 'id'],
                                'properties' => [
                                    'type' => ['type' => 'string', 'example' => $relatedType],
                                    'id' => ['type' => 'string'],
                                ],
                            ],
                            ['type' => 'null'],
                        ],
                    ],
                ],
            ];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * Build query parameters from the resource's allowed filters, sorts, and includes.
     *
     * @param class-string<resource> $resourceClass
     *
     * @return list<array<string, mixed>>
     */
    public function buildQueryParameters(string $resourceClass): array
    {
        $resource = app($resourceClass);
        $parameters = [];

        foreach ($resource->allowedFilters() as $field => $filter) {
            $filterType = $this->resolveFilterType($filter);

            $parameters[] = [
                'name' => "filter[{$field}]",
                'in' => 'query',
                'required' => false,
                'schema' => $filterType,
                'description' => "Filter by {$field}",
            ];
        }

        $allowedSorts = $resource->allowedSorts();

        if ($allowedSorts !== []) {
            $sortValues = [];

            foreach ($allowedSorts as $field) {
                $sortValues[] = $field;
                $sortValues[] = '-'.$field;
            }

            $parameters[] = [
                'name' => 'sort',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => $resource->defaultSort() ?? $sortValues[0],
                ],
                'description' => 'Sort by field. Prefix with - for descending. Comma-separated for multiple. Allowed: '.implode(', ', $allowedSorts),
            ];
        }

        $allowedIncludes = $resource->allowedIncludes();

        if ($allowedIncludes !== []) {
            $parameters[] = [
                'name' => 'include',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => $allowedIncludes[0],
                ],
                'description' => 'Include related resources. Comma-separated. Allowed: '.implode(', ', $allowedIncludes),
            ];
        }

        $parameters[] = [
            'name' => 'page[number]',
            'in' => 'query',
            'required' => false,
            'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
            'description' => 'Page number (offset pagination)',
        ];

        $parameters[] = [
            'name' => 'page[size]',
            'in' => 'query',
            'required' => false,
            'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 20],
            'description' => 'Number of items per page',
        ];

        $parameters[] = [
            'name' => 'page[cursor]',
            'in' => 'query',
            'required' => false,
            'schema' => ['type' => 'string'],
            'description' => 'Cursor for cursor-based pagination (alternative to page[number])',
        ];

        return $parameters;
    }

    /**
     * Build a JSON:API collection response schema.
     *
     * @param class-string<resource> $resourceClass
     *
     * @return array<string, mixed>
     */
    public function buildCollectionSchema(string $resourceClass): array
    {
        $schemaName = $this->schemaName($resourceClass);

        return [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => [
                    'type' => 'array',
                    'items' => ['$ref' => "#/components/schemas/{$schemaName}"],
                ],
                'included' => [
                    'type' => 'array',
                    'items' => ['type' => 'object'],
                ],
                'meta' => [
                    'type' => 'object',
                    'properties' => [
                        'page' => [
                            'type' => 'object',
                            'properties' => [
                                'currentPage' => ['type' => 'integer'],
                                'lastPage' => ['type' => 'integer'],
                                'perPage' => ['type' => 'integer'],
                                'total' => ['type' => 'integer'],
                            ],
                        ],
                    ],
                ],
                'links' => [
                    'type' => 'object',
                    'properties' => [
                        'first' => ['type' => 'string', 'nullable' => true],
                        'last' => ['type' => 'string', 'nullable' => true],
                        'prev' => ['type' => 'string', 'nullable' => true],
                        'next' => ['type' => 'string', 'nullable' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build a JSON:API single resource response schema.
     *
     * @param class-string<resource> $resourceClass
     *
     * @return array<string, mixed>
     */
    public function buildSingleSchema(string $resourceClass): array
    {
        $schemaName = $this->schemaName($resourceClass);

        return [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => ['$ref' => "#/components/schemas/{$schemaName}"],
            ],
        ];
    }

    /**
     * Build a JSON:API error response schema.
     *
     * @return array<string, mixed>
     */
    public function buildErrorSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['errors'],
            'properties' => [
                'errors' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['status', 'title'],
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => '400'],
                            'code' => ['type' => 'string', 'example' => 'invalid_request_error'],
                            'title' => ['type' => 'string', 'example' => 'Bad Request'],
                            'detail' => ['type' => 'string'],
                            'source' => [
                                'type' => 'object',
                                'properties' => [
                                    'pointer' => ['type' => 'string', 'example' => '/name'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param class-string<resource> $resourceClass
     */
    public function schemaName(string $resourceClass): string
    {
        return str_replace('Resource', '', class_basename($resourceClass));
    }

    /**
     * Normalize a user-defined schema array into OpenAPI property definitions.
     *
     * @param array<string, array<string, mixed>|string> $schema
     *
     * @return array<string, array<string, mixed>>
     */
    private function normalizeSchemaProperties(array $schema): array
    {
        $properties = [];

        foreach ($schema as $field => $definition) {
            if (is_string($definition)) {
                $properties[$field] = $this->typeToOpenApi($definition);
            } else {
                $properties[$field] = $definition;
            }
        }

        return $properties;
    }

    /**
     * Infer OpenAPI properties from an Eloquent model's casts and columns.
     *
     * @return array<string, array<string, mixed>>
     */
    private function inferPropertiesFromModel(Model $model): array
    {
        $properties = [];
        $casts = $model->getCasts();

        foreach ($casts as $column => $cast) {
            if (in_array($column, ['id', 'password', 'remember_token'], true)) {
                continue;
            }

            $properties[$column] = $this->castToOpenApi($cast);
        }

        return $properties;
    }

    /**
     * Convert a Laravel cast type to an OpenAPI type.
     *
     * @return array<string, mixed>
     */
    private function castToOpenApi(string $cast): array
    {
        return match (true) {
            $cast === 'int'   || $cast === 'integer' => ['type' => 'integer'],
            $cast === 'float' || $cast === 'double' || $cast === 'decimal' => ['type' => 'number'],
            str_starts_with($cast, 'decimal:') => ['type' => 'number'],
            $cast === 'bool' || $cast === 'boolean' => ['type' => 'boolean'],
            $cast === 'string' => ['type' => 'string'],
            $cast === 'array' || $cast === 'json' || $cast === 'collection' => ['type' => 'object'],
            $cast === 'date' => ['type' => 'string', 'format' => 'date'],
            $cast === 'datetime' || $cast === 'immutable_date' || $cast === 'immutable_datetime' => ['type' => 'string', 'format' => 'date-time'],
            $cast === 'timestamp' => ['type' => 'integer'],
            $cast === 'encrypted' => ['type' => 'string'],
            enum_exists($cast) => $this->enumToOpenApi($cast),
            default => ['type' => 'string'],
        };
    }

    /**
     * Convert a shorthand type string to an OpenAPI type.
     *
     * @return array<string, mixed>
     */
    private function typeToOpenApi(string $type): array
    {
        return match ($type) {
            'string' => ['type' => 'string'],
            'integer', 'int' => ['type' => 'integer'],
            'number', 'float', 'double' => ['type' => 'number'],
            'boolean', 'bool' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            'object' => ['type' => 'object'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'datetime' => ['type' => 'string', 'format' => 'date-time'],
            default => ['type' => 'string'],
        };
    }

    /**
     * Convert a PHP enum to an OpenAPI type with enum values.
     *
     * @param class-string $enumClass
     *
     * @return array<string, mixed>
     */
    private function enumToOpenApi(string $enumClass): array
    {
        if (! enum_exists($enumClass)) {
            return ['type' => 'string'];
        }

        $cases = $enumClass::cases();
        $values = array_map(fn ($case) => $case->value ?? $case->name, $cases);

        $type = isset($cases[0]->value) && is_int($cases[0]->value) ? 'integer' : 'string';

        return [
            'type' => $type,
            'enum' => $values,
        ];
    }

    /**
     * Resolve the OpenAPI type for a filter.
     *
     * @return array<string, mixed>
     */
    private function resolveFilterType(mixed $filter): array
    {
        if (is_string($filter)) {
            $filter = new $filter();
        }

        return match (true) {
            $filter instanceof DateFilter => ['type' => 'string', 'format' => 'date'],
            $filter instanceof ExactFilter => ['type' => 'string'],
            $filter instanceof PartialFilter => ['type' => 'string'],
            default => ['type' => 'string'],
        };
    }
}
