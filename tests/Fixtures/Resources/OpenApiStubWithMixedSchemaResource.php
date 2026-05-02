<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class OpenApiStubWithMixedSchemaResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return [];
    }

    public function schema(): array
    {
        return [
            'name' => 'string',
            'description' => ['type' => 'string', 'nullable' => true],
        ];
    }
}
