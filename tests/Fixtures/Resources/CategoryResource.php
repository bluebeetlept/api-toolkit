<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Category;

final class CategoryResource extends Resource
{
    protected string $model = Category::class;

    public function attributes(Category $category): array
    {
        return [
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }

    public function self(Category $category): string | null
    {
        return '/api/v1/categories/'.$category->public_id;
    }

    public function schema(): array
    {
        return [
            'name' => 'string',
            'slug' => 'string',
        ];
    }
}
