<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\OpenApi;

use BlueBeetle\ApiToolkit\OpenApi\RouteScanner;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubAtMethodController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubBuiltinParamController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubCreateController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubCursorController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubNoResourceController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\ScannerStubShowController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\ScannerStubCreateRequest;
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

it('detects cursorPaginate as list endpoint', function () {
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
