<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\OpenApiStubIntEnumCastModel;

class OpenApiStubWithIntEnumCastModelResource extends Resource
{
    protected string $model = OpenApiStubIntEnumCastModel::class;

    public function attributes($model): array
    {
        return [];
    }
}
