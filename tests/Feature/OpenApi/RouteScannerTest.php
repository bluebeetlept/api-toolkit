<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\OpenApi;

use BlueBeetle\ApiToolkit\OpenApi\RouteScanner;
use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubAtMethodController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubBoundModelController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubBuiltinParamController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubCreateController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubCursorController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubDeclaredClassController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubDeclaredController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubFilteredListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubMappedListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubNoResourceController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubPageController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubQueryListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubShowController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\ScannerStubCreateRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\CategoryResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Support\Facades\Route;

it('scans routes with resource usage', function () {
    Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke'])
        ->name('api.v1.products.index')
    ;

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints)->not->toBeEmpty();
    expect($endpoints[0]->path)->toBe('/api/v1/products');
    expect($endpoints[0]->resourceClass)->toBe(ProductResource::class);
    expect($endpoints[0]->isList)->toBeTrue();
    expect($endpoints[0]->routeName)->toBe('api.v1.products.index');
});

it('ignores closure routes', function () {
    Route::get('/api/v1/health', fn () => response()->json(['ok' => true]));

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    $endpointPaths = array_map(fn ($e) => $e->path, $endpoints);
    expect($endpointPaths)->not->toContain('/api/v1/health');
});

it('ignores routes without resource class', function () {
    Route::get('/api/v1/health', [ScannerStubNoResourceController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    $endpointPaths = array_map(fn ($e) => $e->path, $endpoints);
    expect($endpointPaths)->not->toContain('/api/v1/health');
});

it('detects single resource endpoints', function () {
    Route::get('/api/v1/products/{product}', [ScannerStubShowController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    $productEndpoint = collect($endpoints)->firstWhere('path', '/api/v1/products/{product}');

    expect($productEndpoint)->not->toBeNull();
    expect($productEndpoint->isList)->toBeFalse();
});

it('detects form request class from type hints', function () {
    Route::post('/api/v1/products', [ScannerStubCreateController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    $createEndpoint = collect($endpoints)->firstWhere('path', '/api/v1/products');

    expect($createEndpoint)->not->toBeNull();
    expect($createEndpoint->formRequestClass)->toBe(ScannerStubCreateRequest::class);
});

it('returns null form request when none type-hinted', function () {
    Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints[0]->formRequestClass)->toBeNull();
});

it('extracts HTTP methods excluding HEAD', function () {
    Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints[0]->httpMethods)->toContain('GET');
    expect($endpoints[0]->httpMethods)->not->toContain('HEAD');
});

it('returns empty array when no toolkit routes exist', function () {
    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints)->toBe([]);
});

it('detects cursor as list endpoint', function () {
    Route::get('/api/v1/products', [ScannerStubCursorController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints[0]->isList)->toBeTrue();
});

it('resolves controller class and method name', function () {
    Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints[0]->controllerClass)->toBe(ScannerStubListController::class);
    expect($endpoints[0]->methodName)->toBe('__invoke');
});

it('handles controller@method format', function () {
    Route::get('/api/v1/products', ScannerStubAtMethodController::class.'@index');

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints)->not->toBeEmpty();
    expect($endpoints[0]->methodName)->toBe('index');
});

it('skips parameters with builtin types', function () {
    Route::get('/api/v1/products', [ScannerStubBuiltinParamController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints)->not->toBeEmpty();
    expect($endpoints[0]->formRequestClass)->toBeNull();
});

it('handles invokable controller string format', function () {
    Route::get('/api/v1/products', ScannerStubListController::class);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints)->not->toBeEmpty();
    expect($endpoints[0]->methodName)->toBe('__invoke');
});

it('ignores non-existent controller classes', function () {
    Route::get('/api/v1/fake', 'App\NonExistent\FakeController@index');

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    $endpointPaths = array_map(fn ($e) => $e->path, $endpoints);
    expect($endpointPaths)->not->toContain('/api/v1/fake');
});

it('ignores controllers where method does not exist', function () {
    Route::get('/api/v1/products', ScannerStubListController::class.'@nonExistentMethod');

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints)->toBeEmpty();
});

it('resolves resource class from same namespace when no use statement', function () {
    Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke']);

    $scanner = app(RouteScanner::class);
    $endpoints = $scanner->scan();

    expect($endpoints)->not->toBeEmpty();
    expect($endpoints[0]->resourceClass)->toBe(ProductResource::class);
});

it('finds a list endpoint\'s resource through the model it queries', function () {
    Resource::map([Product::class => ProductResource::class]);

    Route::get('/api/v1/mapped-products', ScannerStubMappedListController::class);

    $endpoint = collect(app(RouteScanner::class)->scan())->firstWhere('path', '/api/v1/mapped-products');

    expect($endpoint->resourceClass)->toBe(ProductResource::class)
        ->and($endpoint->isList)->toBeTrue()
    ;

    Resource::resetResolvers();
});

it('uses the resource an endpoint declares', function () {
    Route::post('/api/v1/declared-products', [ScannerStubDeclaredController::class, 'store']);

    $endpoint = collect(app(RouteScanner::class)->scan())->firstWhere('path', '/api/v1/declared-products');

    expect($endpoint->resourceClass)->toBe(ProductResource::class)
        ->and($endpoint->formRequestClass)->toBe(ScannerStubCreateRequest::class)
    ;
});

it('uses the resource a controller declares for its endpoints', function () {
    Route::post('/api/v1/declared-class-products', ScannerStubDeclaredClassController::class);

    $endpoint = collect(app(RouteScanner::class)->scan())->firstWhere('path', '/api/v1/declared-class-products');

    expect($endpoint->resourceClass)->toBe(ProductResource::class);
});

it('prefers the declared resource over the record the endpoint is bound to', function () {
    Resource::map([Product::class => ProductResource::class]);

    Route::get('/api/v1/declared-products/{product}/category', [ScannerStubDeclaredController::class, 'category']);

    $endpoint = collect(app(RouteScanner::class)->scan())->firstWhere('path', '/api/v1/declared-products/{product}/category');

    expect($endpoint->resourceClass)->toBe(CategoryResource::class);

    Resource::resetResolvers();
});

it('finds an endpoint\'s resource through the record it is bound to', function () {
    Resource::map([Product::class => ProductResource::class]);

    Route::get('/api/v1/mapped-products/{product}', ScannerStubBoundModelController::class);

    $endpoint = collect(app(RouteScanner::class)->scan())->firstWhere('path', '/api/v1/mapped-products/{product}');

    expect($endpoint->resourceClass)->toBe(ProductResource::class);

    Resource::resetResolvers();
});

it('skips an endpoint whose models have no resource', function () {
    Route::get('/api/v1/mapped-products/{product}', ScannerStubBoundModelController::class);

    $paths = array_map(fn ($endpoint) => $endpoint->path, app(RouteScanner::class)->scan());

    expect($paths)->not->toContain('/api/v1/mapped-products/{product}');
});

it('leaves a bound record alone on a controller that does not answer through the toolkit', function () {
    Resource::map([Product::class => ProductResource::class]);

    Route::get('/products/{product}/edit', ScannerStubPageController::class);

    $paths = array_map(fn ($endpoint) => $endpoint->path, app(RouteScanner::class)->scan());

    expect($paths)->not->toContain('/products/{product}/edit');

    Resource::resetResolvers();
});

it('finds a list endpoint\'s resource through the model query it paginates', function () {
    Resource::map([Product::class => ProductResource::class]);

    Route::get('/api/v1/latest-products', ScannerStubQueryListController::class);

    $endpoint = collect(app(RouteScanner::class)->scan())->firstWhere('path', '/api/v1/latest-products');

    expect($endpoint->resourceClass)->toBe(ProductResource::class)
        ->and($endpoint->isList)->toBeTrue()
    ;

    Resource::resetResolvers();
});

it('documents only the routes the configured paths cover', function () {
    config(['api-toolkit.openapi.paths' => ['api/v1/*']]);

    Route::get('/api/v1/products', ScannerStubListController::class);
    Route::get('/internal/products', ScannerStubListController::class);

    $paths = array_map(fn ($endpoint) => $endpoint->path, app(RouteScanner::class)->scan());

    expect($paths)->toContain('/api/v1/products')
        ->not->toContain('/internal/products')
    ;
});

it('finds a list endpoint\'s model even when another class is named before it', function () {
    Resource::map([Product::class => ProductResource::class]);

    Route::get('/api/v1/filtered-products', ScannerStubFilteredListController::class);

    $endpoint = collect(app(RouteScanner::class)->scan())->firstWhere('path', '/api/v1/filtered-products');

    expect($endpoint->resourceClass)->toBe(ProductResource::class);

    Resource::resetResolvers();
});
