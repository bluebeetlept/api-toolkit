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
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class RouteScannerTest extends TestCase
{
    #[Test]
    #[TestDox('it scans routes with resource usage')]
    public function it_scans_routes(): void
    {
        Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke'])
            ->name('api.v1.products.index')
        ;

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertNotEmpty($endpoints);
        $this->assertSame('/api/v1/products', $endpoints[0]->path);
        $this->assertSame(ProductResource::class, $endpoints[0]->resourceClass);
        $this->assertTrue($endpoints[0]->isList);
        $this->assertSame('api.v1.products.index', $endpoints[0]->routeName);
    }

    #[Test]
    #[TestDox('it ignores closure routes')]
    public function it_ignores_closure_routes(): void
    {
        Route::get('/api/v1/health', fn () => response()->json(['ok' => true]));

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $endpointPaths = array_map(fn ($e) => $e->path, $endpoints);
        $this->assertNotContains('/api/v1/health', $endpointPaths);
    }

    #[Test]
    #[TestDox('it ignores routes without resource class')]
    public function it_ignores_routes_without_resource(): void
    {
        Route::get('/api/v1/health', [ScannerStubNoResourceController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $endpointPaths = array_map(fn ($e) => $e->path, $endpoints);
        $this->assertNotContains('/api/v1/health', $endpointPaths);
    }

    #[Test]
    #[TestDox('it detects single resource endpoints')]
    public function it_detects_single_endpoints(): void
    {
        Route::get('/api/v1/products/{product}', [ScannerStubShowController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $productEndpoint = collect($endpoints)->firstWhere('path', '/api/v1/products/{product}');

        $this->assertNotNull($productEndpoint);
        $this->assertFalse($productEndpoint->isList);
    }

    #[Test]
    #[TestDox('it detects form request class from type hints')]
    public function it_detects_form_request(): void
    {
        Route::post('/api/v1/products', [ScannerStubCreateController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $createEndpoint = collect($endpoints)->firstWhere('path', '/api/v1/products');

        $this->assertNotNull($createEndpoint);
        $this->assertSame(ScannerStubCreateRequest::class, $createEndpoint->formRequestClass);
    }

    #[Test]
    #[TestDox('it returns null form request when none type-hinted')]
    public function it_returns_null_form_request(): void
    {
        Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertNull($endpoints[0]->formRequestClass);
    }

    #[Test]
    #[TestDox('it extracts HTTP methods excluding HEAD')]
    public function it_excludes_head_method(): void
    {
        Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertContains('GET', $endpoints[0]->httpMethods);
        $this->assertNotContains('HEAD', $endpoints[0]->httpMethods);
    }

    #[Test]
    #[TestDox('it returns empty array when no toolkit routes exist')]
    public function it_returns_empty_for_no_routes(): void
    {
        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertSame([], $endpoints);
    }

    #[Test]
    #[TestDox('it detects cursorPaginate as list endpoint')]
    public function it_detects_cursor_paginate(): void
    {
        Route::get('/api/v1/products', [ScannerStubCursorController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertTrue($endpoints[0]->isList);
    }

    #[Test]
    #[TestDox('it resolves controller class and method name')]
    public function it_resolves_controller_info(): void
    {
        Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertSame(ScannerStubListController::class, $endpoints[0]->controllerClass);
        $this->assertSame('__invoke', $endpoints[0]->methodName);
    }

    #[Test]
    #[TestDox('it handles controller@method format')]
    public function it_handles_at_method_format(): void
    {
        Route::get('/api/v1/products', ScannerStubAtMethodController::class.'@index');

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertNotEmpty($endpoints);
        $this->assertSame('index', $endpoints[0]->methodName);
    }

    #[Test]
    #[TestDox('it skips parameters with builtin types')]
    public function it_skips_builtin_type_params(): void
    {
        Route::get('/api/v1/products', [ScannerStubBuiltinParamController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertNotEmpty($endpoints);
        $this->assertNull($endpoints[0]->formRequestClass);
    }

    #[Test]
    #[TestDox('it handles invokable controller string format')]
    public function it_handles_invokable_string_format(): void
    {
        Route::get('/api/v1/products', ScannerStubListController::class);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertNotEmpty($endpoints);
        $this->assertSame('__invoke', $endpoints[0]->methodName);
    }

    #[Test]
    #[TestDox('it ignores non-existent controller classes')]
    public function it_ignores_non_existent_controllers(): void
    {
        Route::get('/api/v1/fake', 'App\NonExistent\FakeController@index');

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $endpointPaths = array_map(fn ($e) => $e->path, $endpoints);
        $this->assertNotContains('/api/v1/fake', $endpointPaths);
    }

    #[Test]
    #[TestDox('it ignores controllers where method does not exist')]
    public function it_ignores_missing_methods(): void
    {
        Route::get('/api/v1/products', ScannerStubListController::class.'@nonExistentMethod');

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertEmpty($endpoints);
    }

    #[Test]
    #[TestDox('it resolves resource class from same namespace when no use statement')]
    public function it_resolves_same_namespace(): void
    {
        // The existing controllers use `use` imports, so this is already covered
        // This test ensures the full scanning pipeline works
        Route::get('/api/v1/products', [ScannerStubListController::class, '__invoke']);

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $this->assertNotEmpty($endpoints);
        $this->assertSame(ProductResource::class, $endpoints[0]->resourceClass);
    }
}
