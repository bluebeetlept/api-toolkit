<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Console;

use BlueBeetle\ApiToolkit\OpenApi\DocumentBuilder;
use BlueBeetle\ApiToolkit\OpenApi\RouteScanner;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;

final class GenerateOpenApiCommand extends Command
{
    protected $signature = 'api-toolkit:openapi
        {--output=openapi.json : Output file path}
        {--pretty : Pretty print the JSON output}';

    protected $description = 'Generate an OpenAPI 3.1 specification from your API Toolkit resources';

    public function handle(RouteScanner $scanner, Config $config): int
    {
        $endpoints = $scanner->scan();

        if ($endpoints === []) {
            $this->warn('No API Toolkit endpoints found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d endpoint(s).', count($endpoints)));

        $openApiConfig = $config->get('api-toolkit.openapi', []);

        $builder = new DocumentBuilder(
            title: $openApiConfig['title'] ?? config('app.name', 'API'),
            version: $openApiConfig['version'] ?? '1.0.0',
            description: $openApiConfig['description'] ?? '',
            servers: $openApiConfig['servers'] ?? [],
            securitySchemes: $openApiConfig['security_schemes'] ?? [],
            security: $openApiConfig['security'] ?? [],
        );

        $document = $builder->build($endpoints);

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        if ($this->option('pretty')) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($document, $flags);
        $outputPath = $this->option('output');

        file_put_contents(base_path($outputPath), $json);

        $this->info("OpenAPI spec written to {$outputPath}");

        return self::SUCCESS;
    }
}
