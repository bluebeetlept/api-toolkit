<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Models;

use BlueBeetle\ApiToolkit\Tests\Fixtures\Enums\OpenApiStubStatus;
use Illuminate\Database\Eloquent\Model;

class OpenApiStubEnumCastModel extends Model
{
    protected $table = 'products';

    protected function casts(): array
    {
        return [
            'status' => OpenApiStubStatus::class,
        ];
    }
}
