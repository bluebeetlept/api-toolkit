<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Console;

use BlueBeetle\ApiToolkit\Bruno\BrunoCollectionBuilder;
use BlueBeetle\ApiToolkit\OpenApi\EndpointDefinition;
use BlueBeetle\ApiToolkit\OpenApi\RouteScanner;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class GenerateBrunoCommand extends Command
{
    protected $signature = 'api-toolkit:bruno
        {--output= : Output directory path (overrides config, single collection mode)}';

    protected $description = 'Generate Bruno collection(s) from your API routes';

    public function handle(RouteScanner $scanner, Config $config): int
    {
        $endpoints = $scanner->scan();

        if ($endpoints === []) {
            $this->warn('No API Toolkit endpoints found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d endpoint(s).', count($endpoints)));
        $this->newLine();

        $brunoConfig = $config->get('api-toolkit.bruno', []);

        if ($this->option('output')) {
            return $this->generateSingleCollection($endpoints, $brunoConfig);
        }

        $collections = $brunoConfig['collections'] ?? [];

        if ($collections !== []) {
            return $this->generateMultipleCollections($endpoints, $collections, $brunoConfig);
        }

        return $this->generateSingleCollection($endpoints, $brunoConfig);
    }

    /**
     * @param list<EndpointDefinition> $endpoints
     * @param array<string, mixed>     $brunoConfig
     */
    private function generateSingleCollection(array $endpoints, array $brunoConfig): int
    {
        $outputDir = $this->option('output')
            ?? $brunoConfig['output']
            ?? base_path('bruno');

        $collectionName = $brunoConfig['name'] ?? config('app.name', 'API');
        $baseUrl = $brunoConfig['base_url'] ?? '{{host}}';

        $this->writeCollection($endpoints, $outputDir, $collectionName, $baseUrl);

        return self::SUCCESS;
    }

    /**
     * @param list<EndpointDefinition>            $endpoints
     * @param array<string, array<string, mixed>> $collections
     * @param array<string, mixed>                $brunoConfig
     */
    private function generateMultipleCollections(array $endpoints, array $collections, array $brunoConfig): int
    {
        $baseUrl = $brunoConfig['base_url'] ?? '{{host}}';

        foreach ($collections as $key => $collectionConfig) {
            $prefix = $collectionConfig['prefix'] ?? $key;
            $outputDir = $collectionConfig['output'] ?? base_path('bruno/'.$key);
            $collectionName = $collectionConfig['name'] ?? $brunoConfig['name'] ?? config('app.name', 'API');

            $filtered = $this->filterByPrefix($endpoints, $prefix);

            if ($filtered === []) {
                $this->warn("No endpoints found for prefix [{$prefix}], skipping.");

                continue;
            }

            $this->writeCollection($filtered, $outputDir, $collectionName, $baseUrl);
        }

        return self::SUCCESS;
    }

    /**
     * @param list<EndpointDefinition> $endpoints
     */
    private function writeCollection(array $endpoints, string $outputDir, string $name, string $baseUrl): void
    {
        $builder = new BrunoCollectionBuilder(name: $name, baseUrl: $baseUrl);

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Only write config files if they don't exist yet (preserve user customizations)
        if (! file_exists($outputDir.'/bruno.json')) {
            file_put_contents($outputDir.'/bruno.json', $builder->buildCollectionJson());
        }

        if (! file_exists($outputDir.'/collection.bru')) {
            file_put_contents($outputDir.'/collection.bru', $builder->buildCollectionBru());
        }

        $envDir = $outputDir.'/environments';

        if (! is_dir($envDir)) {
            mkdir($envDir, 0755, true);
        }

        $secretVars = $builder->resolveSecretVars($endpoints);

        $this->updateEnvironmentFiles($envDir, $builder, $secretVars);

        $folders = $builder->buildEndpoints($endpoints);

        // Clean stale resource folders before regenerating
        $this->cleanResourceFolders($outputDir, array_keys($folders));

        $fileCount = 0;

        foreach ($folders as $folderName => $files) {
            $folderPath = $outputDir.'/'.$folderName;

            if (! is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            foreach ($files as $fileName => $content) {
                file_put_contents($folderPath.'/'.$fileName.'.bru', $content);
                $fileCount++;
            }
        }

        $this->info("Bruno collection written to {$outputDir} ({$fileCount} requests)");
    }

    /**
     * @param list<EndpointDefinition> $endpoints
     *
     * @return list<EndpointDefinition>
     */
    /**
     * @param list<string> $secretVars
     */
    private function updateEnvironmentFiles(string $envDir, BrunoCollectionBuilder $builder, array $secretVars): void
    {
        $localEnv = $envDir.'/Local.bru';

        if (! file_exists($localEnv)) {
            file_put_contents(
                $localEnv,
                $builder->buildEnvironmentBru('Local', config('app.url', 'http://localhost'), '', $secretVars),
            );

            return;
        }

        // Update vars:secret block in all existing environment files
        $envFiles = glob($envDir.'/*.bru');

        if ($envFiles === false) {
            return;
        }

        $secretBlock = $builder->buildSecretVarsBlock($secretVars);

        foreach ($envFiles as $envFile) {
            $content = file_get_contents($envFile);

            if ($content === false) {
                continue;
            }

            // Replace existing vars:secret block or append it
            if (preg_match('/vars:secret\s*\[.*?\]\s*/s', $content)) {
                $content = preg_replace('/vars:secret\s*\[.*?\]\s*/s', $secretBlock, $content);
            } else {
                $content = mb_rtrim($content)."\n".$secretBlock;
            }

            file_put_contents($envFile, $content);
        }
    }

    private function filterByPrefix(array $endpoints, string $prefix): array
    {
        $prefix = '/'.mb_ltrim($prefix, '/');

        return array_values(
            array_filter($endpoints, fn (EndpointDefinition $e): bool => str_contains($e->path, $prefix)),
        );
    }

    /**
     * Remove resource folders that are not in the current generation,
     * preserving bruno.json, collection.bru, and environments/.
     *
     * @param list<string> $currentFolders
     */
    private function cleanResourceFolders(string $outputDir, array $currentFolders): void
    {
        if (! is_dir($outputDir)) {
            return;
        }

        $preserved = ['environments', 'bruno.json', 'collection.bru'];
        $entries = scandir($outputDir);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (in_array($entry, $preserved, true)) {
                continue;
            }

            $path = $outputDir.'/'.$entry;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($dir);
    }
}
