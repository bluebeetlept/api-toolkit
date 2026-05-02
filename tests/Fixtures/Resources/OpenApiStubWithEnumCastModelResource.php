<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\OpenApiStubEnumCastModel;

class OpenApiStubWithEnumCastModelResource extends Resource
{
    protected string $model = OpenApiStubEnumCastModel::class;

    public function attributes($model): array
    {
        return [];
    }
}
