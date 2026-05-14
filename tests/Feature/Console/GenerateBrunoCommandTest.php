<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Console;

use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\StubListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\StubShowController;
use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

afterEach(function () {
    $dir = base_path('bruno-test');

    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($dir);
    }
});

it('warns when no endpoints are found', function () {
    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->expectsOutputToContain('No API Toolkit endpoints found')
        ->assertSuccessful()
    ;
});

it('generates bruno collection files', function () {
    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->expectsOutputToContain('endpoint(s)')
        ->expectsOutputToContain('Bruno collection written to')
        ->assertSuccessful()
    ;

    $dir = base_path('bruno-test');

    expect($dir.'/bruno.json')->toBeFile();
    expect($dir.'/collection.bru')->toBeFile();
    expect($dir.'/environments/Local.bru')->toBeFile();

    $json = json_decode(file_get_contents($dir.'/bruno.json'), true);
    expect($json['version'])->toBe('1');
    expect($json['type'])->toBe('collection');
});

it('generates request files per endpoint', function () {
    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->assertSuccessful()
    ;

    $dir = base_path('bruno-test');

    expect($dir.'/Products/List.bru')->toBeFile();
    expect($dir.'/Products/View.bru')->toBeFile();
});

it('generates valid bru file content for list endpoint', function () {
    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->assertSuccessful()
    ;

    $content = file_get_contents(base_path('bruno-test/Products/List.bru'));

    expect($content)->toContain('meta {');
    expect($content)->toContain('name: List Products');
    expect($content)->toContain('type: http');
    expect($content)->toContain('get {');
    expect($content)->toContain('/api/v1/products');
    expect($content)->toContain('auth: inherit');
});

it('generates valid bru file content for view endpoint', function () {
    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->assertSuccessful()
    ;

    $content = file_get_contents(base_path('bruno-test/Products/View.bru'));

    expect($content)->toContain('name: View Product');
    expect($content)->toContain('get {');
    expect($content)->toContain('{{productId}}');
});

it('generates collection.bru with bearer auth', function () {
    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->assertSuccessful()
    ;

    $content = file_get_contents(base_path('bruno-test/collection.bru'));

    expect($content)->toContain('mode: bearer');
    expect($content)->toContain('token: {{apiToken}}');
});

it('generates environment file with host variable', function () {
    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->assertSuccessful()
    ;

    $content = file_get_contents(base_path('bruno-test/environments/Local.bru'));

    expect($content)->toContain('host:');
    expect($content)->toContain('apiToken:');
});

it('adds post-response script to list endpoints', function () {
    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->assertSuccessful()
    ;

    $content = file_get_contents(base_path('bruno-test/Products/List.bru'));

    expect($content)->toContain('script:post-response');
    expect($content)->toContain('productId');
});

it('reads config for collection name', function () {
    $this->app['config']->set('api-toolkit.bruno.name', 'My Custom API');

    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->assertSuccessful()
    ;

    $json = json_decode(file_get_contents(base_path('bruno-test/bruno.json')), true);
    expect($json['name'])->toBe('My Custom API');
});

it('generates multiple collections from config', function () {
    $this->app['config']->set('api-toolkit.bruno.collections', [
        'v1' => [
            'name' => 'API v1',
            'prefix' => 'v1',
            'output' => base_path('bruno-test/v1'),
        ],
        'v2' => [
            'name' => 'API v2',
            'prefix' => 'v2',
            'output' => base_path('bruno-test/v2'),
        ],
    ]);

    registerBrunoRoutes();
    registerBrunoV2Routes();

    $this->artisan('api-toolkit:bruno')
        ->expectsOutputToContain('Bruno collection written to')
        ->assertSuccessful()
    ;

    // V1 collection
    expect(base_path('bruno-test/v1/bruno.json'))->toBeFile();
    $v1Json = json_decode(file_get_contents(base_path('bruno-test/v1/bruno.json')), true);
    expect($v1Json['name'])->toBe('API v1');
    expect(base_path('bruno-test/v1/Products/List.bru'))->toBeFile();

    // V2 collection
    expect(base_path('bruno-test/v2/bruno.json'))->toBeFile();
    $v2Json = json_decode(file_get_contents(base_path('bruno-test/v2/bruno.json')), true);
    expect($v2Json['name'])->toBe('API v2');
    expect(base_path('bruno-test/v2/Products/List.bru'))->toBeFile();
});

it('skips collections with no matching endpoints', function () {
    $this->app['config']->set('api-toolkit.bruno.collections', [
        'v1' => [
            'name' => 'API v1',
            'prefix' => 'v1',
            'output' => base_path('bruno-test/v1'),
        ],
        'v3' => [
            'name' => 'API v3',
            'prefix' => 'v3',
            'output' => base_path('bruno-test/v3'),
        ],
    ]);

    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno')
        ->expectsOutputToContain('No endpoints found for prefix')
        ->assertSuccessful()
    ;

    expect(base_path('bruno-test/v1/bruno.json'))->toBeFile();
    expect(base_path('bruno-test/v3'))->not->toBeDirectory();
});

it('uses --output flag to override collections config', function () {
    $this->app['config']->set('api-toolkit.bruno.collections', [
        'v1' => [
            'name' => 'API v1',
            'prefix' => 'v1',
            'output' => base_path('bruno-test/v1'),
        ],
    ]);

    registerBrunoRoutes();

    $this->artisan('api-toolkit:bruno', ['--output' => base_path('bruno-test')])
        ->assertSuccessful()
    ;

    // Should generate single collection at --output, not per-collection
    expect(base_path('bruno-test/bruno.json'))->toBeFile();
    expect(base_path('bruno-test/v1'))->not->toBeDirectory();
});

function registerBrunoRoutes(): void
{
    Route::get('/api/v1/products', [StubListController::class, '__invoke'])
        ->name('api.v1.products.index')
    ;

    Route::get('/api/v1/products/{product}', [StubShowController::class, '__invoke'])
        ->name('api.v1.products.show')
    ;
}

function registerBrunoV2Routes(): void
{
    Route::get('/api/v2/products', [StubListController::class, '__invoke'])
        ->name('api.v2.products.index')
    ;
}
