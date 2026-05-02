<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\OpenApi;

use BlueBeetle\ApiToolkit\OpenApi\DocumentBuilder;
use BlueBeetle\ApiToolkit\OpenApi\RouteScanner;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ProductCreateController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ProductListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ProductViewController;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/api/v1/products', [ProductListController::class, '__invoke'])
        ->name('api.v1.products.index')
    ;

    Route::get('/api/v1/products/{product}', [ProductViewController::class, '__invoke'])
        ->name('api.v1.products.show')
    ;

    Route::post('/api/v1/products', [ProductCreateController::class, '__invoke'])
        ->name('api.v1.products.store')
    ;

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    $builder = new DocumentBuilder(
        title: 'Test API',
        version: '1.0.0',
        description: 'A test API',
        servers: [['url' => 'https://api.example.com']],
        securitySchemes: [
            'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
        ],
        security: [['bearerAuth' => []]],
    );

    $this->document = $builder->build($endpoints);
});

it('generates a valid OpenAPI 3.1 document', function () {
    expect($this->document['openapi'])->toBe('3.1.0');
    expect($this->document['info']['title'])->toBe('Test API');
    expect($this->document['info']['version'])->toBe('1.0.0');
    expect($this->document['info']['description'])->toBe('A test API');
});

it('includes servers', function () {
    expect($this->document['servers'][0]['url'])->toBe('https://api.example.com');
});

it('includes security schemes', function () {
    expect($this->document['components']['securitySchemes'])->toHaveKey('bearerAuth');
    expect($this->document['components']['securitySchemes']['bearerAuth']['type'])->toBe('http');
    expect($this->document['security'])->toBe([['bearerAuth' => []]]);
});

it('includes top-level tags', function () {
    $tagNames = array_column($this->document['tags'], 'name');

    expect($tagNames)->toContain('Products');
});

it('generates the Product schema with attributes', function () {
    $schema = $this->document['components']['schemas']['Product'];

    expect($schema['type'])->toBe('object');
    expect($schema['required'])->toContain('type');
    expect($schema['required'])->toContain('id');
    expect($schema['properties']['type']['example'])->toBe('products');

    $attributes = $schema['properties']['attributes'];
    expect($attributes['properties']['name']['type'])->toBe('string');
    expect($attributes['properties']['code']['type'])->toBe('string');
    expect($attributes['properties']['price_in_cents']['type'])->toBe('integer');
    expect($attributes['properties']['featured']['type'])->toBe('boolean');
    expect($attributes['properties']['status']['enum'])->toBe(['active', 'inactive']);
});

it('generates relationship schemas', function () {
    $schema = $this->document['components']['schemas']['Product'];
    $relationships = $schema['properties']['relationships'];

    expect($relationships['properties'])->toHaveKey('category');
    expect($relationships['properties'])->toHaveKey('tags');
});

it('generates list endpoint path with query params', function () {
    $path = $this->document['paths']['/api/v1/products']['get'];

    expect($path['operationId'])->toBe('api.v1.products.index');
    expect($path['tags'])->toContain('Products');

    $paramNames = array_column($path['parameters'], 'name');
    expect($paramNames)->toContain('filter[name]');
    expect($paramNames)->toContain('filter[status]');
    expect($paramNames)->toContain('sort');
    expect($paramNames)->toContain('include');
    expect($paramNames)->toContain('page[number]');
    expect($paramNames)->toContain('page[size]');
    expect($paramNames)->toContain('page[cursor]');

    expect($path['responses'])->toHaveKey('200');
    expect($path['responses'])->toHaveKey('401');
});

it('generates single endpoint path', function () {
    $pathItem = $this->document['paths']['/api/v1/products/{product}'];
    $path = $pathItem['get'];

    expect($path['operationId'])->toBe('api.v1.products.show');

    expect($path['responses'])->toHaveKey('200');
    expect($path['responses'])->toHaveKey('404');
    expect($path['responses'])->toHaveKey('401');

    expect($pathItem['parameters'][0]['name'])->toBe('product');
    expect($pathItem['parameters'][0]['in'])->toBe('path');
    expect($pathItem['parameters'][0]['required'])->toBeTrue();
});

it('generates POST endpoint with 201 response', function () {
    $path = $this->document['paths']['/api/v1/products']['post'];

    expect($path['operationId'])->toBe('api.v1.products.store');
    expect($path['responses'])->toHaveKey('201');
    expect($path['responses'])->toHaveKey('422');
});

it('uses shared error response refs', function () {
    $path = $this->document['paths']['/api/v1/products']['get'];

    expect($path['responses']['401']['$ref'])->toBe('#/components/responses/UnauthorizedException');

    expect($this->document['components']['responses'])->toHaveKey('UnauthorizedException');
    expect($this->document['components']['responses'])->toHaveKey('NotFoundHttpException');
    expect($this->document['components']['responses'])->toHaveKey('ValidationException');
});

it('generates collection and single response schemas', function () {
    $schemas = $this->document['components']['schemas'];

    expect($schemas)->toHaveKey('Product');
    expect($schemas)->toHaveKey('ProductCollection');
    expect($schemas)->toHaveKey('ProductResponse');
    expect($schemas)->toHaveKey('JsonApiError');

    expect($schemas['ProductCollection']['properties']['data']['type'])->toBe('array');
    expect($schemas['ProductCollection']['properties']['data']['items']['$ref'])->toBe('#/components/schemas/Product');

    expect($schemas['ProductResponse']['properties']['data']['$ref'])->toBe('#/components/schemas/Product');
});

it('generates valid JSON output', function () {
    $json = json_encode($this->document, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    expect($json)->not->toBeFalse();
    $this->assertJson($json);

    $decoded = json_decode($json, true);
    expect($decoded['openapi'])->toBe('3.1.0');
});
