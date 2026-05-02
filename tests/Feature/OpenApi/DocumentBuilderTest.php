<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\OpenApi;

use BlueBeetle\ApiToolkit\OpenApi\DocumentBuilder;
use BlueBeetle\ApiToolkit\OpenApi\EndpointDefinition;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubEmptyFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubMinMaxFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubNestedFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubNoRulesMethodRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubObjectRuleFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubOptionalFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubPipeRulesFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubThrowingFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubTypedFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\OpenApiStubResource;

function makeListEndpoint(): EndpointDefinition
{
    return new EndpointDefinition(
        path: '/api/v1/stubs',
        httpMethods: ['GET'],
        resourceClass: OpenApiStubResource::class,
        isList: true,
        controllerClass: 'App\Controllers\StubController',
        methodName: 'index',
        formRequestClass: null,
        routeName: null,
    );
}

it('builds a valid OpenAPI 3.1 document', function () {
    $builder = new DocumentBuilder(
        title: 'Test API',
        version: '2.0.0',
        description: 'A test API',
    );

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: true,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'index',
            formRequestClass: null,
            routeName: 'api.v1.stubs.index',
        ),
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'show',
            formRequestClass: null,
            routeName: 'api.v1.stubs.show',
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['openapi'])->toBe('3.1.0');
    expect($document['info']['title'])->toBe('Test API');
    expect($document['info']['version'])->toBe('2.0.0');
    expect($document['info']['description'])->toBe('A test API');
});

it('generates paths for list endpoints', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: true,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'index',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths'])->toHaveKey('/api/v1/stubs');
    expect($document['paths']['/api/v1/stubs'])->toHaveKey('get');
    expect($document['paths']['/api/v1/stubs']['get'])->toHaveKey('parameters');
});

it('generates paths for single resource endpoints', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'show',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $path = $document['paths']['/api/v1/stubs/{stub}'];

    expect($path)->toHaveKey('get');
    expect($path)->toHaveKey('parameters');
    expect($path['parameters'][0]['name'])->toBe('stub');
    expect($path['parameters'][0]['in'])->toBe('path');
});

it('registers resource schemas in components', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: true,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'index',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['components']['schemas'])->toHaveKey('OpenApiStub');
    expect($document['components']['schemas'])->toHaveKey('OpenApiStubCollection');
    expect($document['components']['schemas'])->toHaveKey('JsonApiError');
});

it('generates correct tags', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: true,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'index',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs']['get']['tags'])->toBe(['Open Api Stubs']);
});

it('handles POST endpoints with 201 status', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $responses = $document['paths']['/api/v1/stubs']['post']['responses'];

    expect($responses)->toHaveKey('201');
    expect($responses)->toHaveKey('422');
});

it('handles DELETE endpoints with 204 status', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['DELETE'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'destroy',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $responses = $document['paths']['/api/v1/stubs/{stub}']['delete']['responses'];

    expect($responses)->toHaveKey('204');
});

it('omits description when empty', function () {
    $builder = new DocumentBuilder(title: 'API', version: '1.0.0', description: '');

    $document = $builder->build([makeListEndpoint()]);

    expect($document['info'])->not->toHaveKey('description');
});

it('omits servers when empty', function () {
    $builder = new DocumentBuilder(servers: []);

    $document = $builder->build([makeListEndpoint()]);

    expect($document)->not->toHaveKey('servers');
});

it('omits security when empty', function () {
    $builder = new DocumentBuilder(security: []);

    $document = $builder->build([makeListEndpoint()]);

    expect($document)->not->toHaveKey('security');
});

it('omits securitySchemes when empty', function () {
    $builder = new DocumentBuilder(securitySchemes: []);

    $document = $builder->build([makeListEndpoint()]);

    expect($document['components'])->not->toHaveKey('securitySchemes');
});

it('does not duplicate tags for same resource', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        makeListEndpoint(),
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'show',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $tagNames = array_column($document['tags'], 'name');
    expect($tagNames)->toHaveCount(1);
    expect($tagNames[0])->toBe('Open Api Stubs');
});

it('omits operationId when route has no name', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: true,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'index',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs']['get'])->not->toHaveKey('operationId');
});

it('generates PUT/PATCH summary', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['PUT', 'PATCH'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'update',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs/{stub}']['put']['summary'])->toBe('Update Open Api Stub');
    expect($document['paths']['/api/v1/stubs/{stub}']['patch']['summary'])->toBe('Update Open Api Stub');
});

it('generates DELETE summary', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['DELETE'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'destroy',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs/{stub}']['delete']['summary'])->toBe('Delete Open Api Stub');
});

it('converts optional path parameters', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub?}',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'show',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths'])->toHaveKey('/api/v1/stubs/{stub}');
});

it('generates path parameter description matching resource name', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'show',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $params = $document['paths']['/api/v1/stubs/{stub}']['parameters'];
    expect($params[0])->toHaveKey('description');
});

it('generates non-matching path parameter description', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{category_id}',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'show',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $params = $document['paths']['/api/v1/stubs/{category_id}']['parameters'];
    expect($params[0]['description'])->toContain('Category Id');
});

it('builds request body from form request rules', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $requestBody = $document['paths']['/api/v1/stubs']['post']['requestBody'];

    expect($requestBody['required'])->toBeTrue();
    $schema = $requestBody['content']['application/json']['schema'];
    expect($schema['type'])->toBe('object');
    expect($schema['properties'])->toHaveKey('name');
    expect($schema['properties'])->toHaveKey('email');
    expect($schema['required'])->toContain('name');
});

it('maps form rules to OpenAPI types', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubTypedFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $schema = $document['paths']['/api/v1/stubs']['post']['requestBody']['content']['application/json']['schema'];
    $props = $schema['properties'];

    expect($props['age']['type'])->toBe('integer');
    expect($props['active']['type'])->toBe('boolean');
    expect($props['email']['type'])->toBe('string');
    expect($props['email']['format'])->toBe('email');
    expect($props['website']['type'])->toBe('string');
    expect($props['website']['format'])->toBe('uri');
    expect($props['birthday']['type'])->toBe('string');
    expect($props['birthday']['format'])->toBe('date');
    expect($props['tags']['type'])->toBe('array');
    expect($props['description']['nullable'])->toBeTrue();
});

it('maps min and max rules to schema constraints', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubMinMaxFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $props = $document['paths']['/api/v1/stubs']['post']['requestBody']['content']['application/json']['schema']['properties'];

    expect($props['name']['minLength'])->toBe(3);
    expect($props['name']['maxLength'])->toBe(255);
});

it('skips nested dot-notation rules in request body', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubNestedFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $props = $document['paths']['/api/v1/stubs']['post']['requestBody']['content']['application/json']['schema']['properties'];

    expect($props)->toHaveKey('items');
    expect($props)->not->toHaveKey('items.name');
});

it('omits request body when form request has no rules', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubEmptyFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs']['post'])->not->toHaveKey('requestBody');
});

it('includes 422 for PUT/PATCH endpoints', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['PUT'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'update',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $responses = $document['paths']['/api/v1/stubs/{stub}']['put']['responses'];

    expect($responses)->toHaveKey('422');
    expect($responses)->toHaveKey('401');
    expect($responses)->toHaveKey('404');
});

it('does not include 404 response for list endpoints', function () {
    $builder = new DocumentBuilder();

    $document = $builder->build([makeListEndpoint()]);

    $responses = $document['paths']['/api/v1/stubs']['get']['responses'];

    expect($responses)->not->toHaveKey('404');
});

it('handles rules as pipe-delimited strings', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubPipeRulesFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $props = $document['paths']['/api/v1/stubs']['post']['requestBody']['content']['application/json']['schema']['properties'];

    expect($props['age']['type'])->toBe('integer');
});

it('generates list GET summary', function () {
    $builder = new DocumentBuilder();

    $document = $builder->build([makeListEndpoint()]);

    expect($document['paths']['/api/v1/stubs']['get']['summary'])->toBe('List Open Api Stubs');
});

it('generates single GET summary', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs/{stub}',
            httpMethods: ['GET'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'show',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs/{stub}']['get']['summary'])->toBe('Get Open Api Stub');
});

it('skips non-string rules in rulesToOpenApiType', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubObjectRuleFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $props = $document['paths']['/api/v1/stubs']['post']['requestBody']['content']['application/json']['schema']['properties'];
    expect($props['name']['type'])->toBe('string');
});

it('generates default summary for unknown HTTP method', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['OPTIONS'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'options',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs']['options']['summary'])->toBe('OPTIONS Open Api Stub');
});

it('handles formRequest that throws on instantiation', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubThrowingFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs']['post'])->not->toHaveKey('requestBody');
});

it('handles formRequest without rules method', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubNoRulesMethodRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs']['post'])->not->toHaveKey('requestBody');
});

it('generates POST summary', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    expect($document['paths']['/api/v1/stubs']['post']['summary'])->toBe('Create Open Api Stub');
});

it('omits required array when no fields are required', function () {
    $builder = new DocumentBuilder();

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/stubs',
            httpMethods: ['POST'],
            resourceClass: OpenApiStubResource::class,
            isList: false,
            controllerClass: 'App\Controllers\StubController',
            methodName: 'store',
            formRequestClass: DocBuilderStubOptionalFormRequest::class,
            routeName: null,
        ),
    ];

    $document = $builder->build($endpoints);

    $schema = $document['paths']['/api/v1/stubs']['post']['requestBody']['content']['application/json']['schema'];
    expect($schema)->not->toHaveKey('required');
});
