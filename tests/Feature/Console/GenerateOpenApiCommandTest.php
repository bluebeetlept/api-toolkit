<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Console;

use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\StubListController;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers\StubShowController;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class GenerateOpenApiCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $files = [
            base_path('openapi.json'),
            base_path('custom-output.json'),
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    #[Test]
    #[TestDox('it warns when no endpoints are found')]
    public function it_warns_when_no_endpoints(): void
    {
        $this->artisan('api-toolkit:openapi')
            ->expectsOutputToContain('No API Toolkit endpoints found')
            ->assertSuccessful()
        ;

        $this->assertFileDoesNotExist(base_path('openapi.json'));
    }

    #[Test]
    #[TestDox('it generates openapi.json with default output path')]
    public function it_generates_default_output(): void
    {
        $this->registerRoutes();

        $this->artisan('api-toolkit:openapi')
            ->expectsOutputToContain('endpoint(s)')
            ->expectsOutputToContain('OpenAPI spec written to openapi.json')
            ->assertSuccessful()
        ;

        $this->assertFileExists(base_path('openapi.json'));

        $content = json_decode(file_get_contents(base_path('openapi.json')), true);
        $this->assertSame('3.1.0', $content['openapi']);
    }

    #[Test]
    #[TestDox('it generates to custom output path')]
    public function it_generates_custom_output(): void
    {
        $this->registerRoutes();

        $this->artisan('api-toolkit:openapi', ['--output' => 'custom-output.json'])
            ->expectsOutputToContain('OpenAPI spec written to custom-output.json')
            ->assertSuccessful()
        ;

        $this->assertFileExists(base_path('custom-output.json'));

        $content = json_decode(file_get_contents(base_path('custom-output.json')), true);
        $this->assertSame('3.1.0', $content['openapi']);
    }

    #[Test]
    #[TestDox('it generates pretty-printed JSON with --pretty flag')]
    public function it_generates_pretty_json(): void
    {
        $this->registerRoutes();

        $this->artisan('api-toolkit:openapi', ['--pretty' => true])
            ->assertSuccessful()
        ;

        $raw = file_get_contents(base_path('openapi.json'));
        $this->assertStringContainsString("\n", $raw);
        $this->assertStringContainsString('    ', $raw);
    }

    #[Test]
    #[TestDox('it generates compact JSON without --pretty flag')]
    public function it_generates_compact_json(): void
    {
        $this->registerRoutes();

        $this->artisan('api-toolkit:openapi')
            ->assertSuccessful()
        ;

        $raw = file_get_contents(base_path('openapi.json'));
        $this->assertStringNotContainsString("\n", $raw);
    }

    #[Test]
    #[TestDox('it reads openapi config values')]
    public function it_reads_config(): void
    {
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

        $this->registerRoutes();

        $this->artisan('api-toolkit:openapi', ['--pretty' => true])
            ->assertSuccessful()
        ;

        $content = json_decode(file_get_contents(base_path('openapi.json')), true);
        $this->assertSame('My Custom API', $content['info']['title']);
        $this->assertSame('2.5.0', $content['info']['version']);
        $this->assertSame('Custom description', $content['info']['description']);
        $this->assertSame('https://custom.example.com', $content['servers'][0]['url']);
        $this->assertArrayHasKey('bearerAuth', $content['components']['securitySchemes']);
        $this->assertSame([['bearerAuth' => []]], $content['security']);
    }

    #[Test]
    #[TestDox('it uses app name as fallback title')]
    public function it_uses_app_name_fallback(): void
    {
        $this->app['config']->set('app.name', 'Fallback App');
        $this->app['config']->set('api-toolkit.openapi', []);

        $this->registerRoutes();

        $this->artisan('api-toolkit:openapi')
            ->assertSuccessful()
        ;

        $content = json_decode(file_get_contents(base_path('openapi.json')), true);
        $this->assertSame('Fallback App', $content['info']['title']);
    }

    #[Test]
    #[TestDox('it reports the correct endpoint count')]
    public function it_reports_endpoint_count(): void
    {
        $this->registerRoutes();

        $this->artisan('api-toolkit:openapi')
            ->expectsOutputToContain('Found 2 endpoint(s)')
            ->assertSuccessful()
        ;
    }

    private function registerRoutes(): void
    {
        Route::get('/api/v1/products', [StubListController::class, '__invoke'])
            ->name('api.v1.products.index')
        ;

        Route::get('/api/v1/products/{product}', [StubShowController::class, '__invoke'])
            ->name('api.v1.products.show')
        ;
    }
}
