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

it('builds a resource schema with type and id', function () {
    $builder = new SchemaBuilder();
    $schema = $builder->buildResourceSchema(OpenApiStubResource::class);

    expect($schema['type'])->toBe('object');
    expect($schema['required'])->toContain('type');
    expect($schema['required'])->toContain('id');
    expect($schema['required'])->toContain('attributes');
    expect($schema['properties']['type']['type'])->toBe('string');
    expect($schema['properties']['type']['example'])->toBe('stubs');
});

it('builds attributes from schema method', function () {
    $builder = new SchemaBuilder();
    $schema = $builder->buildResourceSchema(OpenApiStubWithSchemaResource::class);

    $attributes = $schema['properties']['attributes'];

    expect($attributes['properties']['name']['type'])->toBe('string');
    expect($attributes['properties']['quantity']['type'])->toBe('integer');
    expect($attributes['properties']['active']['type'])->toBe('boolean');
});

it('builds relationships schema', function () {
    $builder = new SchemaBuilder();
    $schema = $builder->buildResourceSchema(OpenApiStubWithRelationshipsResource::class);

    $relationships = $schema['properties']['relationships'];

    expect($relationships['properties'])->toHaveKey('category');
    expect($relationships['properties']['category']['properties']['data']['oneOf'][0]['properties']['type']['example'])->toBe('categories');
});

it('builds query parameters from resource', function () {
    $builder = new SchemaBuilder();
    $params = $builder->buildQueryParameters(OpenApiStubWithFiltersResource::class);

    $paramNames = array_column($params, 'name');

    expect($paramNames)->toContain('filter[name]');
    expect($paramNames)->toContain('filter[status]');
    expect($paramNames)->toContain('sort');
    expect($paramNames)->toContain('include');
    expect($paramNames)->toContain('page[number]');
    expect($paramNames)->toContain('page[size]');
    expect($paramNames)->toContain('page[cursor]');
});

it('builds collection schema', function () {
    $builder = new SchemaBuilder();
    $schema = $builder->buildCollectionSchema(OpenApiStubResource::class);

    expect($schema['properties']['data']['type'])->toBe('array');
    expect($schema['properties']['data']['items']['$ref'])->toBe('#/components/schemas/OpenApiStub');
    expect($schema['properties'])->toHaveKey('meta');
    expect($schema['properties'])->toHaveKey('links');
});

it('builds single resource response schema', function () {
    $builder = new SchemaBuilder();
    $schema = $builder->buildSingleSchema(OpenApiStubResource::class);

    expect($schema['properties']['data']['$ref'])->toBe('#/components/schemas/OpenApiStub');
});

it('builds error schema', function () {
    $builder = new SchemaBuilder();
    $schema = $builder->buildErrorSchema();

    expect($schema['properties']['errors']['type'])->toBe('array');
    expect($schema['properties']['errors']['items']['properties'])->toHaveKey('status');
    expect($schema['properties']['errors']['items']['properties'])->toHaveKey('title');
    expect($schema['properties']['errors']['items']['properties'])->toHaveKey('detail');
});

it('generates schema name from resource class', function () {
    $builder = new SchemaBuilder();

    expect($builder->schemaName(OpenApiStubResource::class))->toBe('OpenApiStub');
    expect($builder->schemaName(OpenApiStubWithSchemaResource::class))->toBe('OpenApiStubWithSchema');
});

it('falls back to additionalProperties when no schema and no model', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubResource::class);

    $attributes = $builder->buildAttributesSchema($resource);

    expect($attributes['type'])->toBe('object');
    expect($attributes['additionalProperties'])->toBeTrue();
});

it('infers attributes schema from model casts', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubWithModelResource::class);

    $attributes = $builder->buildAttributesSchema($resource);

    expect($attributes['type'])->toBe('object');
    expect($attributes['properties']['price_in_cents']['type'])->toBe('integer');
    expect($attributes['properties']['featured']['type'])->toBe('boolean');
});

it('returns empty relationships schema when none defined', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubResource::class);

    $relationships = $builder->buildRelationshipsSchema($resource);

    expect($relationships['type'])->toBe('object');
    expect($relationships)->not->toHaveKey('properties');
});

it('builds query parameters without sorts or includes', function () {
    $builder = new SchemaBuilder();
    $params = $builder->buildQueryParameters(OpenApiStubResource::class);

    $paramNames = array_column($params, 'name');

    expect($paramNames)->toContain('page[number]');
    expect($paramNames)->toContain('page[size]');
    expect($paramNames)->toContain('page[cursor]');

    expect($paramNames)->not->toContain('sort');
    expect($paramNames)->not->toContain('include');
});

it('resolves DateFilter type', function () {
    $builder = new SchemaBuilder();
    $params = $builder->buildQueryParameters(OpenApiStubWithDateFilterResource::class);

    $dateParam = collect($params)->firstWhere('name', 'filter[created_at]');

    expect($dateParam['schema']['format'])->toBe('date');
    expect($dateParam['schema']['type'])->toBe('string');
});

it('resolves filter type from class string', function () {
    $builder = new SchemaBuilder();
    $params = $builder->buildQueryParameters(OpenApiStubWithClassStringFilterResource::class);

    $paramNames = array_column($params, 'name');
    expect($paramNames)->toContain('filter[status]');
});

it('normalizes schema with array definitions', function () {
    $builder = new SchemaBuilder();
    $schema = $builder->buildResourceSchema(OpenApiStubWithMixedSchemaResource::class);

    $attributes = $schema['properties']['attributes'];

    expect($attributes['properties']['name']['type'])->toBe('string');
    expect($attributes['properties']['description']['type'])->toBe('string');
    expect($attributes['properties']['description']['nullable'])->toBeTrue();
});

it('maps all type shorthands to OpenAPI types', function () {
    $builder = new SchemaBuilder();
    $schema = $builder->buildResourceSchema(OpenApiStubWithAllTypesResource::class);

    $props = $schema['properties']['attributes']['properties'];

    expect($props['name']['type'])->toBe('string');
    expect($props['count']['type'])->toBe('integer');
    expect($props['price']['type'])->toBe('number');
    expect($props['active']['type'])->toBe('boolean');
    expect($props['tags']['type'])->toBe('array');
    expect($props['metadata']['type'])->toBe('object');
    expect($props['birthday']['format'])->toBe('date');
    expect($props['created_at']['format'])->toBe('date-time');
});

it('handles sort parameter without default sort', function () {
    $builder = new SchemaBuilder();
    $params = $builder->buildQueryParameters(OpenApiStubWithSortsOnlyResource::class);

    $sortParam = collect($params)->firstWhere('name', 'sort');

    expect($sortParam['schema']['example'])->toBe('name');
    expect($sortParam['description'])->toContain('name, created_at');
});

it('infers enum cast from model', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubWithEnumModelResource::class);

    $attributes = $builder->buildAttributesSchema($resource);

    expect($attributes['type'])->toBe('object');
});

it('falls back when model has no casts', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubWithNoCastModelResource::class);

    $attributes = $builder->buildAttributesSchema($resource);

    expect($attributes['type'])->toBe('object');
    expect($attributes['additionalProperties'])->toBeTrue();
});

it('maps all cast types to OpenAPI types', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubWithAllCastsModelResource::class);

    $attributes = $builder->buildAttributesSchema($resource);
    $props = $attributes['properties'];

    expect($props['count']['type'])->toBe('integer');
    expect($props['price']['type'])->toBe('number');
    expect($props['precise_price']['type'])->toBe('number');
    expect($props['active']['type'])->toBe('boolean');
    expect($props['name']['type'])->toBe('string');
    expect($props['settings']['type'])->toBe('object');
    expect($props['birthday']['type'])->toBe('string');
    expect($props['birthday']['format'])->toBe('date');
    expect($props['created_at']['type'])->toBe('string');
    expect($props['created_at']['format'])->toBe('date-time');
    expect($props['unix_ts']['type'])->toBe('integer');
    expect($props['secret']['type'])->toBe('string');
});

it('maps backed enum cast to OpenAPI enum', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubWithEnumCastModelResource::class);

    $attributes = $builder->buildAttributesSchema($resource);
    $props = $attributes['properties'];

    expect($props['status']['type'])->toBe('string');
    expect($props['status']['enum'])->toContain('active');
    expect($props['status']['enum'])->toContain('inactive');
});

it('maps integer backed enum cast', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubWithIntEnumCastModelResource::class);

    $attributes = $builder->buildAttributesSchema($resource);
    $props = $attributes['properties'];

    expect($props['priority']['type'])->toBe('integer');
    expect($props['priority']['enum'])->toContain(1);
});

it('resolves default filter type', function () {
    $builder = new SchemaBuilder();
    $params = $builder->buildQueryParameters(OpenApiStubWithCustomFilterResource::class);

    $param = collect($params)->firstWhere('name', 'filter[custom]');
    expect($param['schema']['type'])->toBe('string');
});

it('skips id, password, remember_token from model casts', function () {
    $builder = new SchemaBuilder();
    $resource = app(OpenApiStubWithInternalCastsModelResource::class);

    $attributes = $builder->buildAttributesSchema($resource);
    $props = $attributes['properties'] ?? [];

    expect($props)->not->toHaveKey('id');
    expect($props)->not->toHaveKey('password');
    expect($props)->not->toHaveKey('remember_token');
    expect($props)->toHaveKey('name');
});
