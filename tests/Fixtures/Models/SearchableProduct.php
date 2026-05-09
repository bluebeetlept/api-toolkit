<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

final class SearchableProduct extends Model
{
    use Searchable;

    protected $table = 'products';

    protected $guarded = [];
}
