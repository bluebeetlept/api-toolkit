<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Filters\OpenApiStubCustomFilter;

class OpenApiStubWithCustomFilterResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return [];
    }

    public function allowedFilters(): array
    {
        return [
            'custom' => new OpenApiStubCustomFilter(),
        ];
    }
}
