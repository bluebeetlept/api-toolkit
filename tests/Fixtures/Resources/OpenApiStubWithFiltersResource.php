<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Parsers\Filters\ExactFilter;
use BlueBeetle\ApiToolkit\Parsers\Filters\PartialFilter;
use BlueBeetle\ApiToolkit\Resources\Resource;

class OpenApiStubWithFiltersResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }

    public function allowedFilters(): array
    {
        return [
            'name' => new PartialFilter(),
            'status' => new ExactFilter(),
        ];
    }

    public function allowedSorts(): array
    {
        return ['name', 'created_at'];
    }

    public function defaultSort(): string | null
    {
        return '-created_at';
    }

    public function relationships(): array
    {
        return [
            'category' => OpenApiStubCategoryResource::class,
        ];
    }
}
