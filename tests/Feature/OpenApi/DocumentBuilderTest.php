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
use BlueBeetle\ApiToolkit\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class DocumentBuilderTest extends TestCase
{
    #[Test]
    #[TestDox('it builds a valid OpenAPI 3.1 document')]
    public function it_builds_valid_document(): void
    {
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

        $this->assertSame('3.1.0', $document['openapi']);
        $this->assertSame('Test API', $document['info']['title']);
        $this->assertSame('2.0.0', $document['info']['version']);
        $this->assertSame('A test API', $document['info']['description']);
    }

    #[Test]
    #[TestDox('it generates paths for list endpoints')]
    public function it_generates_list_paths(): void
    {
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

        $this->assertArrayHasKey('/api/v1/stubs', $document['paths']);
        $this->assertArrayHasKey('get', $document['paths']['/api/v1/stubs']);
        $this->assertArrayHasKey('parameters', $document['paths']['/api/v1/stubs']['get']);
    }

    #[Test]
    #[TestDox('it generates paths for single resource endpoints')]
    public function it_generates_single_paths(): void
    {
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

        $this->assertArrayHasKey('get', $path);
        $this->assertArrayHasKey('parameters', $path);
        $this->assertSame('stub', $path['parameters'][0]['name']);
        $this->assertSame('path', $path['parameters'][0]['in']);
    }

    #[Test]
    #[TestDox('it registers resource schemas in components')]
    public function it_registers_schemas(): void
    {
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

        $this->assertArrayHasKey('OpenApiStub', $document['components']['schemas']);
        $this->assertArrayHasKey('OpenApiStubCollection', $document['components']['schemas']);
        $this->assertArrayHasKey('JsonApiError', $document['components']['schemas']);
    }

    #[Test]
    #[TestDox('it generates correct tags')]
    public function it_generates_tags(): void
    {
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

        $this->assertSame(['Open Api Stubs'], $document['paths']['/api/v1/stubs']['get']['tags']);
    }

    #[Test]
    #[TestDox('it handles POST endpoints with 201 status')]
    public function it_handles_post_endpoints(): void
    {
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

        $this->assertArrayHasKey('201', $responses);
        $this->assertArrayHasKey('422', $responses);
    }

    #[Test]
    #[TestDox('it handles DELETE endpoints with 204 status')]
    public function it_handles_delete_endpoints(): void
    {
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

        $this->assertArrayHasKey('204', $responses);
    }

    #[Test]
    #[TestDox('it omits description when empty')]
    public function it_omits_empty_description(): void
    {
        $builder = new DocumentBuilder(title: 'API', version: '1.0.0', description: '');

        $document = $builder->build([$this->makeListEndpoint()]);

        $this->assertArrayNotHasKey('description', $document['info']);
    }

    #[Test]
    #[TestDox('it omits servers when empty')]
    public function it_omits_empty_servers(): void
    {
        $builder = new DocumentBuilder(servers: []);

        $document = $builder->build([$this->makeListEndpoint()]);

        $this->assertArrayNotHasKey('servers', $document);
    }

    #[Test]
    #[TestDox('it omits security when empty')]
    public function it_omits_empty_security(): void
    {
        $builder = new DocumentBuilder(security: []);

        $document = $builder->build([$this->makeListEndpoint()]);

        $this->assertArrayNotHasKey('security', $document);
    }

    #[Test]
    #[TestDox('it omits securitySchemes when empty')]
    public function it_omits_empty_security_schemes(): void
    {
        $builder = new DocumentBuilder(securitySchemes: []);

        $document = $builder->build([$this->makeListEndpoint()]);

        $this->assertArrayNotHasKey('securitySchemes', $document['components']);
    }

    #[Test]
    #[TestDox('it does not duplicate tags for same resource')]
    public function it_deduplicates_tags(): void
    {
        $builder = new DocumentBuilder();

        $endpoints = [
            $this->makeListEndpoint(),
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
        $this->assertCount(1, $tagNames);
        $this->assertSame('Open Api Stubs', $tagNames[0]);
    }

    #[Test]
    #[TestDox('it omits operationId when route has no name')]
    public function it_omits_operation_id_without_route_name(): void
    {
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

        $this->assertArrayNotHasKey('operationId', $document['paths']['/api/v1/stubs']['get']);
    }

    #[Test]
    #[TestDox('it generates PUT/PATCH summary')]
    public function it_generates_put_patch_summary(): void
    {
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

        $this->assertSame('Update Open Api Stub', $document['paths']['/api/v1/stubs/{stub}']['put']['summary']);
        $this->assertSame('Update Open Api Stub', $document['paths']['/api/v1/stubs/{stub}']['patch']['summary']);
    }

    #[Test]
    #[TestDox('it generates DELETE summary')]
    public function it_generates_delete_summary(): void
    {
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

        $this->assertSame('Delete Open Api Stub', $document['paths']['/api/v1/stubs/{stub}']['delete']['summary']);
    }

    #[Test]
    #[TestDox('it converts optional path parameters')]
    public function it_converts_optional_path_params(): void
    {
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

        // Optional param should have ? removed
        $this->assertArrayHasKey('/api/v1/stubs/{stub}', $document['paths']);
    }

    #[Test]
    #[TestDox('it generates path parameter description matching resource name')]
    public function it_generates_matching_param_description(): void
    {
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
        // 'stub' matches resource name 'OpenApiStub' headline → should get "The Stub ID" or similar
        $this->assertArrayHasKey('description', $params[0]);
    }

    #[Test]
    #[TestDox('it generates non-matching path parameter description')]
    public function it_generates_non_matching_param_description(): void
    {
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
        $this->assertStringContainsString('Category Id', $params[0]['description']);
    }

    #[Test]
    #[TestDox('it builds request body from form request rules')]
    public function it_builds_request_body(): void
    {
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

        $this->assertTrue($requestBody['required']);
        $schema = $requestBody['content']['application/json']['schema'];
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
        $this->assertContains('name', $schema['required']);
    }

    #[Test]
    #[TestDox('it maps form rules to OpenAPI types')]
    public function it_maps_rules_to_openapi_types(): void
    {
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

        $this->assertSame('integer', $props['age']['type']);
        $this->assertSame('boolean', $props['active']['type']);
        $this->assertSame('string', $props['email']['type']);
        $this->assertSame('email', $props['email']['format']);
        $this->assertSame('string', $props['website']['type']);
        $this->assertSame('uri', $props['website']['format']);
        $this->assertSame('string', $props['birthday']['type']);
        $this->assertSame('date', $props['birthday']['format']);
        $this->assertSame('array', $props['tags']['type']);
        $this->assertTrue($props['description']['nullable']);
    }

    #[Test]
    #[TestDox('it maps min and max rules to schema constraints')]
    public function it_maps_min_max_rules(): void
    {
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

        $this->assertSame(3, $props['name']['minLength']);
        $this->assertSame(255, $props['name']['maxLength']);
    }

    #[Test]
    #[TestDox('it skips nested dot-notation rules in request body')]
    public function it_skips_nested_rules(): void
    {
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

        $this->assertArrayHasKey('items', $props);
        $this->assertArrayNotHasKey('items.name', $props);
    }

    #[Test]
    #[TestDox('it omits request body when form request has no rules')]
    public function it_omits_empty_request_body(): void
    {
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

        $this->assertArrayNotHasKey('requestBody', $document['paths']['/api/v1/stubs']['post']);
    }

    #[Test]
    #[TestDox('it includes 422 for PUT/PATCH endpoints')]
    public function it_includes_validation_error_for_put_patch(): void
    {
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

        $this->assertArrayHasKey('422', $responses);
        $this->assertArrayHasKey('401', $responses);
        $this->assertArrayHasKey('404', $responses);
    }

    #[Test]
    #[TestDox('list endpoints do not include 404 response')]
    public function it_omits_404_for_list(): void
    {
        $builder = new DocumentBuilder();

        $document = $builder->build([$this->makeListEndpoint()]);

        $responses = $document['paths']['/api/v1/stubs']['get']['responses'];

        $this->assertArrayNotHasKey('404', $responses);
    }

    #[Test]
    #[TestDox('it handles rules as pipe-delimited strings')]
    public function it_handles_pipe_delimited_rules(): void
    {
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

        $this->assertSame('integer', $props['age']['type']);
    }

    #[Test]
    #[TestDox('it generates list GET summary')]
    public function it_generates_list_get_summary(): void
    {
        $builder = new DocumentBuilder();

        $document = $builder->build([$this->makeListEndpoint()]);

        $this->assertSame('List Open Api Stubs', $document['paths']['/api/v1/stubs']['get']['summary']);
    }

    #[Test]
    #[TestDox('it generates single GET summary')]
    public function it_generates_single_get_summary(): void
    {
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

        $this->assertSame('Get Open Api Stub', $document['paths']['/api/v1/stubs/{stub}']['get']['summary']);
    }

    #[Test]
    #[TestDox('it skips non-string rules in rulesToOpenApiType')]
    public function it_skips_non_string_rules(): void
    {
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
        // Should still have string type (non-string rule objects are skipped)
        $this->assertSame('string', $props['name']['type']);
    }

    #[Test]
    #[TestDox('it generates default summary for unknown HTTP method')]
    public function it_generates_default_summary(): void
    {
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

        $this->assertSame('OPTIONS Open Api Stub', $document['paths']['/api/v1/stubs']['options']['summary']);
    }

    #[Test]
    #[TestDox('it handles formRequest that throws on instantiation')]
    public function it_handles_throwing_form_request(): void
    {
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

        $this->assertArrayNotHasKey('requestBody', $document['paths']['/api/v1/stubs']['post']);
    }

    #[Test]
    #[TestDox('it handles formRequest without rules method')]
    public function it_handles_no_rules_method(): void
    {
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

        $this->assertArrayNotHasKey('requestBody', $document['paths']['/api/v1/stubs']['post']);
    }

    #[Test]
    #[TestDox('it generates POST summary')]
    public function it_generates_post_summary(): void
    {
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

        $this->assertSame('Create Open Api Stub', $document['paths']['/api/v1/stubs']['post']['summary']);
    }

    #[Test]
    #[TestDox('it omits required array when no fields are required')]
    public function it_omits_required_when_none(): void
    {
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
        $this->assertArrayNotHasKey('required', $schema);
    }

    private function makeListEndpoint(): EndpointDefinition
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
}
