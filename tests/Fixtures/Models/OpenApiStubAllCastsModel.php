<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class OpenApiStubAllCastsModel extends Model
{
    protected $table = 'products';

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'price' => 'float',
            'precise_price' => 'decimal:2',
            'active' => 'boolean',
            'name' => 'string',
            'settings' => 'array',
            'birthday' => 'date',
            'created_at' => 'datetime',
            'unix_ts' => 'timestamp',
            'secret' => 'encrypted',
        ];
    }
}
