<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Bruno;

use BlueBeetle\ApiToolkit\Bruno\BrunoCollectionBuilder;
use BlueBeetle\ApiToolkit\OpenApi\EndpointDefinition;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;

it('builds collection json', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $json = json_decode($builder->buildCollectionJson(), true);

    expect($json['version'])->toBe('1');
    expect($json['name'])->toBe('Test API');
    expect($json['type'])->toBe('collection');
});

it('builds collection bru with bearer auth', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $content = $builder->buildCollectionBru();

    expect($content)->toContain('mode: bearer');
    expect($content)->toContain('token: {{apiToken}}');
});

it('builds environment bru', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $content = $builder->buildEnvironmentBru('Local', 'http://localhost', 'sk_test_123');

    expect($content)->toContain('host: http://localhost');
    expect($content)->toContain('apiToken: sk_test_123');
});

it('builds endpoint files grouped by resource', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/products',
            httpMethods: ['GET'],
            resourceClass: ProductResource::class,
            isList: true,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'index',
            formRequestClass: null,
            routeName: 'api.v1.products.index',
        ),
        new EndpointDefinition(
            path: '/api/v1/products/{product}',
            httpMethods: ['GET'],
            resourceClass: ProductResource::class,
            isList: false,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'show',
            formRequestClass: null,
            routeName: 'api.v1.products.show',
        ),
    ];

    $folders = $builder->buildEndpoints($endpoints);

    expect($folders)->toHaveKey('Products');
    expect($folders['Products'])->toHaveKey('List');
    expect($folders['Products'])->toHaveKey('View');
});

it('converts route params to bruno variables', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/products/{product}',
            httpMethods: ['GET'],
            resourceClass: ProductResource::class,
            isList: false,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'show',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $folders = $builder->buildEndpoints($endpoints);

    expect($folders['Products']['View'])->toContain('{{productId}}');
});

it('generates correct method names', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/products',
            httpMethods: ['GET', 'POST'],
            resourceClass: ProductResource::class,
            isList: true,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'index',
            formRequestClass: null,
            routeName: null,
        ),
        new EndpointDefinition(
            path: '/api/v1/products/{product}',
            httpMethods: ['PUT', 'DELETE'],
            resourceClass: ProductResource::class,
            isList: false,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'update',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $folders = $builder->buildEndpoints($endpoints);

    expect($folders['Products'])->toHaveKey('List');
    expect($folders['Products'])->toHaveKey('Create');
    expect($folders['Products'])->toHaveKey('Update');
    expect($folders['Products'])->toHaveKey('Delete');
});

it('adds post-response script for list endpoints', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/products',
            httpMethods: ['GET'],
            resourceClass: ProductResource::class,
            isList: true,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'index',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $folders = $builder->buildEndpoints($endpoints);

    expect($folders['Products']['List'])->toContain('script:post-response');
    expect($folders['Products']['List'])->toContain('productId');
});

it('adds post-response script for create endpoints', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/products',
            httpMethods: ['POST'],
            resourceClass: ProductResource::class,
            isList: false,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'store',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $folders = $builder->buildEndpoints($endpoints);

    expect($folders['Products']['Create'])->toContain('script:post-response');
    expect($folders['Products']['Create'])->toContain('productId');
});

it('does not add post-response script for view endpoints', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API');

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/products/{product}',
            httpMethods: ['GET'],
            resourceClass: ProductResource::class,
            isList: false,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'show',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $folders = $builder->buildEndpoints($endpoints);

    expect($folders['Products']['View'])->not->toContain('script:post-response');
});

it('uses custom base url', function () {
    $builder = new BrunoCollectionBuilder(name: 'Test API', baseUrl: 'https://api.example.com');

    $endpoints = [
        new EndpointDefinition(
            path: '/api/v1/products',
            httpMethods: ['GET'],
            resourceClass: ProductResource::class,
            isList: true,
            controllerClass: 'App\Controllers\ProductController',
            methodName: 'index',
            formRequestClass: null,
            routeName: null,
        ),
    ];

    $folders = $builder->buildEndpoints($endpoints);

    expect($folders['Products']['List'])->toContain('https://api.example.com/api/v1/products');
});
