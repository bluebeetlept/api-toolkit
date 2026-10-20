<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\OpenApi;

use Illuminate\Support\Str;

final class DocumentBuilder
{
    private readonly SchemaBuilder $schemaBuilder;

    private readonly RequestBodyBuilder $requestBodyBuilder;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $schemas = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $responses = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $paths = [];

    /**
     * @var list<array{name: string, description?: string}>
     */
    private array $tags = [];

    /**
     * @var list<string>
     */
    private array $tagNames = [];

    /**
     * @param list<array{url: string, description?: string}> $servers
     * @param array<string, array<string, mixed>>            $securitySchemes
     * @param list<array<string, list<string>>>              $security
     */
    public function __construct(
        private readonly string $title = 'API',
        private readonly string $version = '1.0.0',
        private readonly string $description = '',
        private readonly array $servers = [],
        private readonly array $securitySchemes = [],
        private readonly array $security = [],
    ) {
        $this->schemaBuilder = new SchemaBuilder();
        $this->requestBodyBuilder = new RequestBodyBuilder();
    }

    /**
     * Build the full OpenAPI document from scanned endpoints.
     *
     * @param list<EndpointDefinition> $endpoints
     *
     * @return array<string, mixed>
     */
    public function build(array $endpoints): array
    {
        $this->schemas = [];
        $this->responses = [];
        $this->paths = [];
        $this->tags = [];
        $this->tagNames = [];

        $this->registerSharedResponses();

        foreach ($endpoints as $endpoint) {
            $this->processEndpoint($endpoint);
        }

        $document = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $this->title,
                'version' => $this->version,
            ],
        ];

        if ($this->description !== '') {
            $document['info']['description'] = $this->description;
        }

        if ($this->servers !== []) {
            $document['servers'] = $this->servers;
        }

        if ($this->tags !== []) {
            $document['tags'] = $this->tags;
        }

        if ($this->security !== []) {
            $document['security'] = $this->security;
        }

        $document['paths'] = $this->paths;

        $components = ['schemas' => $this->schemas];

        if ($this->responses !== []) {
            $components['responses'] = $this->responses;
        }

        if ($this->securitySchemes !== []) {
            $components['securitySchemes'] = $this->securitySchemes;
        }

        $document['components'] = $components;

        return $document;
    }

    private function registerSharedResponses(): void
    {
        $errorSchema = $this->schemaBuilder->buildErrorSchema();

        $this->schemas['JsonApiError'] = $errorSchema;

        $this->responses['UnauthorizedException'] = [
            'description' => 'Unauthorized',
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => ['$ref' => '#/components/schemas/JsonApiError'],
                ],
            ],
        ];

        $this->responses['NotFoundHttpException'] = [
            'description' => 'Resource not found',
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => ['$ref' => '#/components/schemas/JsonApiError'],
                ],
            ],
        ];

        $this->responses['ValidationException'] = [
            'description' => 'Validation error',
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => ['$ref' => '#/components/schemas/JsonApiError'],
                ],
            ],
        ];
    }

    private function processEndpoint(EndpointDefinition $endpoint): void
    {
        $schemaName = $this->schemaBuilder->schemaName($endpoint->resourceClass);

        if (! isset($this->schemas[$schemaName])) {
            $this->schemas[$schemaName] = $this->schemaBuilder->buildResourceSchema($endpoint->resourceClass);
        }

        $this->registerTag($endpoint);

        foreach ($endpoint->httpMethods as $httpMethod) {
            $operation = $this->buildOperation($endpoint, $schemaName, $httpMethod);
            $path = $this->convertLaravelPathToOpenApi($endpoint->path);

            $this->paths[$path] ??= [];
            $this->paths[$path][mb_strtolower($httpMethod)] = $operation;

            $pathParams = $this->extractPathParameters($path, $endpoint);

            if ($pathParams !== [] && ! isset($this->paths[$path]['parameters'])) {
                $this->paths[$path]['parameters'] = $pathParams;
            }
        }
    }

    private function registerTag(EndpointDefinition $endpoint): void
    {
        $tagName = $this->resolveTagName($endpoint);

        if (in_array($tagName, $this->tagNames, true)) {
            return;
        }

        $this->tagNames[] = $tagName;
        $this->tags[] = ['name' => $tagName];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOperation(EndpointDefinition $endpoint, string $schemaName, string $httpMethod): array
    {
        $operation = [];

        if ($endpoint->routeName !== null) {
            $operation['operationId'] = $endpoint->routeName;
        }

        $operation['summary'] = $this->generateSummary($endpoint, $httpMethod);
        $operation['tags'] = [$this->resolveTagName($endpoint)];

        if ($endpoint->isList && mb_strtoupper($httpMethod) === 'GET') {
            $operation['parameters'] = $this->schemaBuilder->buildQueryParameters($endpoint->resourceClass);
        }

        if (in_array(mb_strtoupper($httpMethod), ['POST', 'PUT', 'PATCH'], true)) {
            $requestBody = $endpoint->formRequestClass === null
                ? null
                : $this->requestBodyBuilder->build($endpoint->formRequestClass, $httpMethod);

            if ($requestBody !== null) {
                $operation['requestBody'] = $requestBody;
            }
        }

        $operation['responses'] = $this->buildResponses($endpoint, $schemaName, $httpMethod);

        return $operation;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponses(EndpointDefinition $endpoint, string $schemaName, string $httpMethod): array
    {
        $responses = [];
        $method = mb_strtoupper($httpMethod);

        if ($method === 'DELETE') {
            $responses['204'] = ['description' => 'Resource deleted'];
        } elseif ($endpoint->isList) {
            $collectionSchemaName = $schemaName.'Collection';

            if (! isset($this->schemas[$collectionSchemaName])) {
                $this->schemas[$collectionSchemaName] = $this->schemaBuilder->buildCollectionSchema($endpoint->resourceClass);
            }

            $responses['200'] = [
                'description' => "Paginated set of `{$schemaName}`",
                'content' => [
                    'application/vnd.api+json' => [
                        'schema' => ['$ref' => "#/components/schemas/{$collectionSchemaName}"],
                    ],
                ],
            ];
        } else {
            $singleSchemaName = $schemaName.'Response';

            if (! isset($this->schemas[$singleSchemaName])) {
                $this->schemas[$singleSchemaName] = $this->schemaBuilder->buildSingleSchema($endpoint->resourceClass);
            }

            $statusCode = $method === 'POST' ? '201' : '200';

            $responses[$statusCode] = [
                'description' => "`{$schemaName}`",
                'content' => [
                    'application/vnd.api+json' => [
                        'schema' => ['$ref' => "#/components/schemas/{$singleSchemaName}"],
                    ],
                ],
            ];
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $responses['422'] = ['$ref' => '#/components/responses/ValidationException'];
        }

        $responses['401'] = ['$ref' => '#/components/responses/UnauthorizedException'];

        if (! $endpoint->isList) {
            $responses['404'] = ['$ref' => '#/components/responses/NotFoundHttpException'];
        }

        return $responses;
    }

    private function generateSummary(EndpointDefinition $endpoint, string $httpMethod): string
    {
        $resourceName = $this->schemaBuilder->schemaName($endpoint->resourceClass);
        $method = mb_strtoupper($httpMethod);

        $plural = Str::plural(Str::headline($resourceName));
        $singular = Str::headline($resourceName);

        return match (true) {
            $endpoint->isList && $method === 'GET' => "List {$plural}",
            $method === 'GET' => "Get {$singular}",
            $method === 'POST' => "Create {$singular}",
            $method === 'PUT', $method === 'PATCH' => "Update {$singular}",
            $method === 'DELETE' => "Delete {$singular}",
            default => "{$method} {$singular}",
        };
    }

    private function resolveTagName(EndpointDefinition $endpoint): string
    {
        $resourceName = $this->schemaBuilder->schemaName($endpoint->resourceClass);

        return Str::plural(Str::headline($resourceName));
    }

    private function convertLaravelPathToOpenApi(string $path): string
    {
        return str_replace('?}', '}', $path);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractPathParameters(string $path, EndpointDefinition $endpoint): array
    {
        preg_match_all('/\{(\w+)}/', $path, $matches);

        return array_map(function (string $param) use ($endpoint): array {
            $description = $this->guessParameterDescription($param, $endpoint);

            $parameter = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];

            if ($description !== null) {
                $parameter['description'] = $description;
            }

            return $parameter;
        }, $matches[1]);
    }

    private function guessParameterDescription(string $param, EndpointDefinition $endpoint): string | null
    {
        $resourceName = $this->schemaBuilder->schemaName($endpoint->resourceClass);
        $singular = Str::headline($resourceName);

        $paramClean = Str::headline(Str::snake($param));

        if (mb_strtolower($paramClean) === mb_strtolower($singular)) {
            return "The {$paramClean} ID";
        }

        return "The {$paramClean}";
    }
}
