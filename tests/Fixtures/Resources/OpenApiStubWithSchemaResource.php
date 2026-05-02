<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class OpenApiStubWithSchemaResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }

    public function schema(): array
    {
        return [
            'name' => 'string',
            'quantity' => 'integer',
            'active' => 'boolean',
        ];
    }
}
