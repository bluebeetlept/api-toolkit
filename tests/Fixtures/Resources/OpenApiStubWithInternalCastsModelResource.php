<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\OpenApiStubInternalCastsModel;

class OpenApiStubWithInternalCastsModelResource extends Resource
{
    protected string $model = OpenApiStubInternalCastsModel::class;

    public function attributes($model): array
    {
        return [];
    }
}
