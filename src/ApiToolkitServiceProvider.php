<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit;

use Eufaturo\ApiToolkit\Console\GenerateOpenApiCommand;
use Eufaturo\ApiToolkit\Http\Response;
use Eufaturo\ApiToolkit\Parsers\PageParser;
use Eufaturo\IdempotencyMiddleware\IdempotencyServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

final class ApiToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-toolkit.php', 'api-toolkit');

        $this->app->singleton(Response::class, function () {
            return new Response(
                request: $this->app->make(Request::class),
            );
        });

        $this->app->singleton(PageParser::class, function () {
            return new PageParser(
                defaultSize: $this->app['config']->get('api-toolkit.pagination.default_size', 20),
                maxSize: $this->app['config']->get('api-toolkit.pagination.max_size', 100),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateOpenApiCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/api-toolkit.php' => config_path('api-toolkit.php'),
        ], 'api-toolkit-config');

        $this->app->register(IdempotencyServiceProvider::class);
    }
}
