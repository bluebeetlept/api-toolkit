<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\OpenApiStubNoCastModel;

class OpenApiStubWithNoCastModelResource extends Resource
{
    protected string $model = OpenApiStubNoCastModel::class;

    public function attributes($model): array
    {
        return [];
    }
}
