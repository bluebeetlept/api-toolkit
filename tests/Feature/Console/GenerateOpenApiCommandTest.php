<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Console;

use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\StubListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\StubShowController;
use Illuminate\Support\Facades\Route;

afterEach(function () {
    $files = [
        base_path('openapi.json'),
        base_path('custom-output.json'),
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

it('warns when no endpoints are found', function () {
    $this->artisan('api-toolkit:openapi')
        ->expectsOutputToContain('No API Toolkit endpoints found')
        ->assertSuccessful()
    ;

    expect(base_path('openapi.json'))->not->toBeFile();
});

it('generates openapi.json with default output path', function () {
    registerRoutes();

    $this->artisan('api-toolkit:openapi')
        ->expectsOutputToContain('endpoint(s)')
        ->expectsOutputToContain('OpenAPI spec written to openapi.json')
        ->assertSuccessful()
    ;

    expect(base_path('openapi.json'))->toBeFile();

    $content = json_decode(file_get_contents(base_path('openapi.json')), true);
    expect($content['openapi'])->toBe('3.1.0');
});

it('generates to custom output path', function () {
    registerRoutes();

    $this->artisan('api-toolkit:openapi', ['--output' => 'custom-output.json'])
        ->expectsOutputToContain('OpenAPI spec written to custom-output.json')
        ->assertSuccessful()
    ;

    expect(base_path('custom-output.json'))->toBeFile();

    $content = json_decode(file_get_contents(base_path('custom-output.json')), true);
    expect($content['openapi'])->toBe('3.1.0');
});

it('generates pretty-printed JSON with --pretty flag', function () {
    registerRoutes();

    $this->artisan('api-toolkit:openapi', ['--pretty' => true])
        ->assertSuccessful()
    ;

    $raw = file_get_contents(base_path('openapi.json'));
    expect($raw)->toContain("\n");
    expect($raw)->toContain('    ');
});

it('generates compact JSON without --pretty flag', function () {
    registerRoutes();

    $this->artisan('api-toolkit:openapi')
        ->assertSuccessful()
    ;

    $raw = file_get_contents(base_path('openapi.json'));
    expect($raw)->not->toContain("\n");
});

it('reads openapi config values', function () {
    $this->app['config']->set('api-toolkit.openapi', [
        'title' => 'My Custom API',
        'version' => '2.5.0',
        'description' => 'Custom description',
        'servers' => [['url' => 'https://custom.example.com']],
        'security_schemes' => [
            'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
        ],
        'security' => [['bearerAuth' => []]],
    ]);

    registerRoutes();

    $this->artisan('api-toolkit:openapi', ['--pretty' => true])
        ->assertSuccessful()
    ;

    $content = json_decode(file_get_contents(base_path('openapi.json')), true);
    expect($content['info']['title'])->toBe('My Custom API');
    expect($content['info']['version'])->toBe('2.5.0');
    expect($content['info']['description'])->toBe('Custom description');
    expect($content['servers'][0]['url'])->toBe('https://custom.example.com');
    expect($content['components']['securitySchemes'])->toHaveKey('bearerAuth');
    expect($content['security'])->toBe([['bearerAuth' => []]]);
});

it('uses app name as fallback title', function () {
    $this->app['config']->set('app.name', 'Fallback App');
    $this->app['config']->set('api-toolkit.openapi', []);

    registerRoutes();

    $this->artisan('api-toolkit:openapi')
        ->assertSuccessful()
    ;

    $content = json_decode(file_get_contents(base_path('openapi.json')), true);
    expect($content['info']['title'])->toBe('Fallback App');
});

it('reports the correct endpoint count', function () {
    registerRoutes();

    $this->artisan('api-toolkit:openapi')
        ->expectsOutputToContain('Found 2 endpoint(s)')
        ->assertSuccessful()
    ;
});

function registerRoutes(): void
{
    Route::get('/api/v1/products', [StubListController::class, '__invoke'])
        ->name('api.v1.products.index')
    ;

    Route::get('/api/v1/products/{product}', [StubShowController::class, '__invoke'])
        ->name('api.v1.products.show')
    ;
}
