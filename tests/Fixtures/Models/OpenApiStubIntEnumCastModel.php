<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Models;

use BlueBeetle\ApiToolkit\Tests\Fixtures\Enums\OpenApiStubPriority;
use Illuminate\Database\Eloquent\Model;

class OpenApiStubIntEnumCastModel extends Model
{
    protected $table = 'products';

    protected function casts(): array
    {
        return [
            'priority' => OpenApiStubPriority::class,
        ];
    }
}
