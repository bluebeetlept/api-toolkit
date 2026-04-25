<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Tag extends Model
{
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\Eufaturo\ApiToolkit\Tests\Fixtures\Models\Product, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
