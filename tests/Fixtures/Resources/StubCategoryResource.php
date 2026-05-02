<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class StubCategoryResource extends Resource
{
    protected string $type = 'categories';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }
}
