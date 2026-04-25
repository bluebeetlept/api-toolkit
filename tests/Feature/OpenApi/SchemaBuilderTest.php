<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Feature\OpenApi;

use Eufaturo\ApiToolkit\OpenApi\SchemaBuilder;
use Eufaturo\ApiToolkit\Parsers\Filters\ExactFilter;
use Eufaturo\ApiToolkit\Parsers\Filters\PartialFilter;
use Eufaturo\ApiToolkit\Resources\Resource;
use Eufaturo\ApiToolkit\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class SchemaBuilderTest extends TestCase
{
    #[Test]
    #[TestDox('it builds a resource schema with type and id')]
    public function it_builds_resource_schema(): void
    {
        $builder = new SchemaBuilder();
        $schema = $builder->buildResourceSchema(OpenApiStubResource::class);

        $this->assertSame('object', $schema['type']);
        $this->assertContains('type', $schema['required']);
        $this->assertContains('id', $schema['required']);
        $this->assertContains('attributes', $schema['required']);
        $this->assertSame('string', $schema['properties']['type']['type']);
        $this->assertSame('stubs', $schema['properties']['type']['example']);
    }

    #[Test]
    #[TestDox('it builds attributes from schema method')]
    public function it_builds_attributes_from_schema(): void
    {
        $builder = new SchemaBuilder();
        $schema = $builder->buildResourceSchema(OpenApiStubWithSchemaResource::class);

        $attributes = $schema['properties']['attributes'];

        $this->assertSame('string', $attributes['properties']['name']['type']);
        $this->assertSame('integer', $attributes['properties']['quantity']['type']);
        $this->assertSame('boolean', $attributes['properties']['active']['type']);
    }

    #[Test]
    #[TestDox('it builds relationships schema')]
    public function it_builds_relationships_schema(): void
    {
        $builder = new SchemaBuilder();
        $schema = $builder->buildResourceSchema(OpenApiStubWithRelationshipsResource::class);

        $relationships = $schema['properties']['relationships'];

        $this->assertArrayHasKey('category', $relationships['properties']);
        $this->assertSame('categories', $relationships['properties']['category']['properties']['data']['oneOf'][0]['properties']['type']['example']);
    }

    #[Test]
    #[TestDox('it builds query parameters from resource')]
    public function it_builds_query_parameters(): void
    {
        $builder = new SchemaBuilder();
        $params = $builder->buildQueryParameters(OpenApiStubWithFiltersResource::class);

        $paramNames = array_column($params, 'name');

        $this->assertContains('filter[name]', $paramNames);
        $this->assertContains('filter[status]', $paramNames);
        $this->assertContains('sort', $paramNames);
        $this->assertContains('include', $paramNames);
        $this->assertContains('page[number]', $paramNames);
        $this->assertContains('page[size]', $paramNames);
        $this->assertContains('page[cursor]', $paramNames);
    }

    #[Test]
    #[TestDox('it builds collection schema')]
    public function it_builds_collection_schema(): void
    {
        $builder = new SchemaBuilder();
        $schema = $builder->buildCollectionSchema(OpenApiStubResource::class);

        $this->assertSame('array', $schema['properties']['data']['type']);
        $this->assertSame('#/components/schemas/OpenApiStub', $schema['properties']['data']['items']['$ref']);
        $this->assertArrayHasKey('meta', $schema['properties']);
        $this->assertArrayHasKey('links', $schema['properties']);
    }

    #[Test]
    #[TestDox('it builds single resource response schema')]
    public function it_builds_single_schema(): void
    {
        $builder = new SchemaBuilder();
        $schema = $builder->buildSingleSchema(OpenApiStubResource::class);

        $this->assertSame('#/components/schemas/OpenApiStub', $schema['properties']['data']['$ref']);
    }

    #[Test]
    #[TestDox('it builds error schema')]
    public function it_builds_error_schema(): void
    {
        $builder = new SchemaBuilder();
        $schema = $builder->buildErrorSchema();

        $this->assertSame('array', $schema['properties']['errors']['type']);
        $this->assertArrayHasKey('status', $schema['properties']['errors']['items']['properties']);
        $this->assertArrayHasKey('title', $schema['properties']['errors']['items']['properties']);
        $this->assertArrayHasKey('detail', $schema['properties']['errors']['items']['properties']);
    }

    #[Test]
    #[TestDox('it generates schema name from resource class')]
    public function it_generates_schema_name(): void
    {
        $builder = new SchemaBuilder();

        $this->assertSame('OpenApiStub', $builder->schemaName(OpenApiStubResource::class));
        $this->assertSame('OpenApiStubWithSchema', $builder->schemaName(OpenApiStubWithSchemaResource::class));
    }
}

class OpenApiStubResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }
}

class OpenApiStubWithSchemaResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }

    public function schema(): array
    {
        return [
            'name' => 'string',
            'quantity' => 'integer',
            'active' => 'boolean',
        ];
    }
}

class OpenApiStubCategoryResource extends Resource
{
    protected string $type = 'categories';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }
}

class OpenApiStubWithRelationshipsResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }

    public function relationships(): array
    {
        return [
            'category' => OpenApiStubCategoryResource::class,
        ];
    }
}

class OpenApiStubWithFiltersResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }

    public function allowedFilters(): array
    {
        return [
            'name' => new PartialFilter(),
            'status' => new ExactFilter(),
        ];
    }

    public function allowedSorts(): array
    {
        return ['name', 'created_at'];
    }

    public function defaultSort(): string | null
    {
        return '-created_at';
    }

    public function relationships(): array
    {
        return [
            'category' => OpenApiStubCategoryResource::class,
        ];
    }
}
