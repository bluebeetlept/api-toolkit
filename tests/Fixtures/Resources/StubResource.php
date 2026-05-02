<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class StubResource extends Resource
{
    protected string $type = 'items';

    public function attributes($model): array
    {
        return [
            'name' => $model->name,
        ];
    }
}
