<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\OpenApi;

use BlueBeetle\ApiToolkit\OpenApi\DocumentBuilder;
use BlueBeetle\ApiToolkit\OpenApi\EndpointDefinition;
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
}
