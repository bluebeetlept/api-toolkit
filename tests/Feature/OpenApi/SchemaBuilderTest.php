<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\OpenApi;

use BlueBeetle\ApiToolkit\OpenApi\SchemaBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithAllCastsModelResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithAllTypesResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithClassStringFilterResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithCustomFilterResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithDateFilterResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithEnumCastModelResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithEnumModelResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithFiltersResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithIntEnumCastModelResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithInternalCastsModelResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithMixedSchemaResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithModelResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithNoCastModelResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithRelationshipsResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithSchemaResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubWithSortsOnlyResource;
use BlueBeetle\ApiToolkit\Tests\TestCase;
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

    #[Test]
    #[TestDox('it falls back to additionalProperties when no schema and no model')]
    public function it_falls_back_without_schema_or_model(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubResource::class);

        $attributes = $builder->buildAttributesSchema($resource);

        $this->assertSame('object', $attributes['type']);
        $this->assertTrue($attributes['additionalProperties']);
    }

    #[Test]
    #[TestDox('it infers attributes schema from model casts')]
    public function it_infers_from_model_casts(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubWithModelResource::class);

        $attributes = $builder->buildAttributesSchema($resource);

        $this->assertSame('object', $attributes['type']);
        $this->assertSame('integer', $attributes['properties']['price_in_cents']['type']);
        $this->assertSame('boolean', $attributes['properties']['featured']['type']);
    }

    #[Test]
    #[TestDox('it returns empty relationships schema when none defined')]
    public function it_returns_empty_relationships(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubResource::class);

        $relationships = $builder->buildRelationshipsSchema($resource);

        $this->assertSame('object', $relationships['type']);
        $this->assertArrayNotHasKey('properties', $relationships);
    }

    #[Test]
    #[TestDox('it builds query parameters without sorts or includes')]
    public function it_builds_params_without_sorts_or_includes(): void
    {
        $builder = new SchemaBuilder();
        $params = $builder->buildQueryParameters(OpenApiStubResource::class);

        $paramNames = array_column($params, 'name');

        // Should still have pagination params
        $this->assertContains('page[number]', $paramNames);
        $this->assertContains('page[size]', $paramNames);
        $this->assertContains('page[cursor]', $paramNames);

        // Should not have sort or include params
        $this->assertNotContains('sort', $paramNames);
        $this->assertNotContains('include', $paramNames);
    }

    #[Test]
    #[TestDox('it resolves DateFilter type')]
    public function it_resolves_date_filter_type(): void
    {
        $builder = new SchemaBuilder();
        $params = $builder->buildQueryParameters(OpenApiStubWithDateFilterResource::class);

        $dateParam = collect($params)->firstWhere('name', 'filter[created_at]');

        $this->assertSame('date', $dateParam['schema']['format']);
        $this->assertSame('string', $dateParam['schema']['type']);
    }

    #[Test]
    #[TestDox('it resolves filter type from class string')]
    public function it_resolves_filter_from_class_string(): void
    {
        $builder = new SchemaBuilder();
        $params = $builder->buildQueryParameters(OpenApiStubWithClassStringFilterResource::class);

        $paramNames = array_column($params, 'name');
        $this->assertContains('filter[status]', $paramNames);
    }

    #[Test]
    #[TestDox('it normalizes schema with array definitions')]
    public function it_normalizes_array_definitions(): void
    {
        $builder = new SchemaBuilder();
        $schema = $builder->buildResourceSchema(OpenApiStubWithMixedSchemaResource::class);

        $attributes = $schema['properties']['attributes'];

        // String shorthand
        $this->assertSame('string', $attributes['properties']['name']['type']);
        // Array definition passed through
        $this->assertSame('string', $attributes['properties']['description']['type']);
        $this->assertTrue($attributes['properties']['description']['nullable']);
    }

    #[Test]
    #[TestDox('it maps all type shorthands to OpenAPI types')]
    public function it_maps_type_shorthands(): void
    {
        $builder = new SchemaBuilder();
        $schema = $builder->buildResourceSchema(OpenApiStubWithAllTypesResource::class);

        $props = $schema['properties']['attributes']['properties'];

        $this->assertSame('string', $props['name']['type']);
        $this->assertSame('integer', $props['count']['type']);
        $this->assertSame('number', $props['price']['type']);
        $this->assertSame('boolean', $props['active']['type']);
        $this->assertSame('array', $props['tags']['type']);
        $this->assertSame('object', $props['metadata']['type']);
        $this->assertSame('date', $props['birthday']['format']);
        $this->assertSame('date-time', $props['created_at']['format']);
    }

    #[Test]
    #[TestDox('it handles sort parameter without default sort')]
    public function it_handles_sort_without_default(): void
    {
        $builder = new SchemaBuilder();
        $params = $builder->buildQueryParameters(OpenApiStubWithSortsOnlyResource::class);

        $sortParam = collect($params)->firstWhere('name', 'sort');

        // When no default sort, uses first allowed sort as example
        $this->assertSame('name', $sortParam['schema']['example']);
        $this->assertStringContainsString('name, created_at', $sortParam['description']);
    }

    #[Test]
    #[TestDox('it infers enum cast from model')]
    public function it_infers_enum_cast(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubWithEnumModelResource::class);

        $attributes = $builder->buildAttributesSchema($resource);

        // The model has enum casts, should be detected
        $this->assertSame('object', $attributes['type']);
    }

    #[Test]
    #[TestDox('it falls back when model has no casts')]
    public function it_falls_back_with_empty_model_casts(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubWithNoCastModelResource::class);

        $attributes = $builder->buildAttributesSchema($resource);

        $this->assertSame('object', $attributes['type']);
        $this->assertTrue($attributes['additionalProperties']);
    }

    #[Test]
    #[TestDox('it maps all cast types to OpenAPI types')]
    public function it_maps_all_cast_types(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubWithAllCastsModelResource::class);

        $attributes = $builder->buildAttributesSchema($resource);
        $props = $attributes['properties'];

        $this->assertSame('integer', $props['count']['type']);
        $this->assertSame('number', $props['price']['type']);
        $this->assertSame('number', $props['precise_price']['type']);
        $this->assertSame('boolean', $props['active']['type']);
        $this->assertSame('string', $props['name']['type']);
        $this->assertSame('object', $props['settings']['type']);
        $this->assertSame('string', $props['birthday']['type']);
        $this->assertSame('date', $props['birthday']['format']);
        $this->assertSame('string', $props['created_at']['type']);
        $this->assertSame('date-time', $props['created_at']['format']);
        $this->assertSame('integer', $props['unix_ts']['type']);
        $this->assertSame('string', $props['secret']['type']);
    }

    #[Test]
    #[TestDox('it maps backed enum cast to OpenAPI enum')]
    public function it_maps_backed_enum(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubWithEnumCastModelResource::class);

        $attributes = $builder->buildAttributesSchema($resource);
        $props = $attributes['properties'];

        $this->assertSame('string', $props['status']['type']);
        $this->assertContains('active', $props['status']['enum']);
        $this->assertContains('inactive', $props['status']['enum']);
    }

    #[Test]
    #[TestDox('it maps integer backed enum cast')]
    public function it_maps_integer_backed_enum(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubWithIntEnumCastModelResource::class);

        $attributes = $builder->buildAttributesSchema($resource);
        $props = $attributes['properties'];

        $this->assertSame('integer', $props['priority']['type']);
        $this->assertContains(1, $props['priority']['enum']);
    }

    #[Test]
    #[TestDox('it resolves default filter type')]
    public function it_resolves_default_filter_type(): void
    {
        $builder = new SchemaBuilder();
        $params = $builder->buildQueryParameters(OpenApiStubWithCustomFilterResource::class);

        $param = collect($params)->firstWhere('name', 'filter[custom]');
        $this->assertSame('string', $param['schema']['type']);
    }

    #[Test]
    #[TestDox('it skips id, password, remember_token from model casts')]
    public function it_skips_internal_columns(): void
    {
        $builder = new SchemaBuilder();
        $resource = app(OpenApiStubWithInternalCastsModelResource::class);

        $attributes = $builder->buildAttributesSchema($resource);
        $props = $attributes['properties'] ?? [];

        $this->assertArrayNotHasKey('id', $props);
        $this->assertArrayNotHasKey('password', $props);
        $this->assertArrayNotHasKey('remember_token', $props);
        $this->assertArrayHasKey('name', $props);
    }
}
