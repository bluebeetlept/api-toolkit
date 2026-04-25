<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class MakeResourceCommand extends Command
{
    protected $signature = 'api-toolkit:make-resource
        {name : The name of the resource class (e.g. ProductResource)}
        {--model= : The model class to associate with the resource}
        {--plain : Create a resource without a model (uses $type instead of $model)}';

    protected $description = 'Create a new API Toolkit resource class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $modelOption = $this->option('model');
        $plain = $this->option('plain');

        $resourceClass = Str::studly($name);

        if (! str_ends_with($resourceClass, 'Resource')) {
            $resourceClass .= 'Resource';
        }

        $path = app_path('Http/Resources/'.$resourceClass.'.php');
        $directory = dirname($path);

        if (file_exists($path)) {
            $this->error("Resource [{$resourceClass}] already exists.");

            return self::FAILURE;
        }

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($plain) {
            $typeName = $this->deriveTypeName($resourceClass);
            $content = $this->buildPlainStub($resourceClass, $typeName);
        } else {
            $modelClass = $modelOption ?? $this->guessModel($resourceClass);
            $content = $this->buildModelStub($resourceClass, $modelClass);
        }

        file_put_contents($path, $content);

        $this->info("Resource [{$resourceClass}] created successfully.");

        return self::SUCCESS;
    }

    private function buildModelStub(string $resourceClass, string $modelClass): string
    {
        $modelBaseName = class_basename($modelClass);
        $modelImport = str_starts_with($modelClass, 'App\\') ? $modelClass : 'App\\Models\\'.$modelClass;

        return <<<PHP
            <?php

            declare(strict_types = 1);

            namespace App\Http\Resources;

            use BlueBeetle\ApiToolkit\Resources\Resource;
            use {$modelImport};

            final class {$resourceClass} extends Resource
            {
                protected string \$model = {$modelBaseName}::class;

                public function attributes({$modelBaseName} \${$this->variableName($modelBaseName)}): array
                {
                    return [
                        //
                    ];
                }
            }

            PHP;
    }

    private function buildPlainStub(string $resourceClass, string $typeName): string
    {
        return <<<PHP
            <?php

            declare(strict_types = 1);

            namespace App\Http\Resources;

            use BlueBeetle\ApiToolkit\Resources\Resource;

            final class {$resourceClass} extends Resource
            {
                protected string \$type = '{$typeName}';

                public function attributes(\$model): array
                {
                    return [
                        //
                    ];
                }
            }

            PHP;
    }

    private function guessModel(string $resourceClass): string
    {
        $modelName = str_replace('Resource', '', $resourceClass);

        return 'App\\Models\\'.$modelName;
    }

    private function deriveTypeName(string $resourceClass): string
    {
        $name = str_replace('Resource', '', $resourceClass);

        return Str::snake($name, '-');
    }

    private function variableName(string $modelBaseName): string
    {
        return Str::camel($modelBaseName);
    }
}
