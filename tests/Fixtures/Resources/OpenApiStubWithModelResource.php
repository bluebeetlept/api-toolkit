<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;

class OpenApiStubWithModelResource extends Resource
{
    protected string $model = Product::class;

    public function attributes($model): array
    {
        return [
            'price_in_cents' => $model->price_in_cents,
            'featured' => $model->featured,
        ];
    }
}
