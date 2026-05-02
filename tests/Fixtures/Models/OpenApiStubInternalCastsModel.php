<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class OpenApiStubInternalCastsModel extends Model
{
    protected $table = 'products';

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'password' => 'string',
            'remember_token' => 'string',
            'name' => 'string',
        ];
    }
}
