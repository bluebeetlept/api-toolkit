<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class OpenApiStubWithSortsOnlyResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return [];
    }

    public function allowedSorts(): array
    {
        return ['name', 'created_at'];
    }
}
