<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class OpenApiStubWithAllTypesResource extends Resource
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
            'count' => 'integer',
            'price' => 'number',
            'active' => 'boolean',
            'tags' => 'array',
            'metadata' => 'object',
            'birthday' => 'date',
            'created_at' => 'datetime',
        ];
    }
}
