<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Parsers\Filters\ExactFilter;
use BlueBeetle\ApiToolkit\Parsers\Filters\PartialFilter;
use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;

final class ProductResource extends Resource
{
    protected string $model = Product::class;

    public function attributes(Product $product): array
    {
        return [
            'name' => $product->name,
            'code' => $product->code,
            'description' => $product->description,
            'status' => $product->status,
            'price_in_cents' => $product->price_in_cents,
            'featured' => $product->featured,
            'created_at' => $product->created_at?->toDateTimeString(),
            'updated_at' => $product->updated_at?->toDateTimeString(),
        ];
    }

    public function self(Product $product): string | null
    {
        return '/api/v1/products/'.$product->public_id;
    }

    public function links(Product $product): array
    {
        return [
            'category' => '/api/v1/products/'.$product->public_id.'/category',
        ];
    }

    public function meta(Product $product): array
    {
        return [
            'is_available' => $product->status === 'active',
        ];
    }

    public function relationships(): array
    {
        return [
            'category' => CategoryResource::class,
            'tags' => TagResource::class,
        ];
    }

    public function allowedFilters(): array
    {
        return [
            'name' => new PartialFilter(),
            'status' => new ExactFilter(),
            'code' => new ExactFilter(),
        ];
    }

    public function allowedSorts(): array
    {
        return ['name', 'code', 'created_at'];
    }

    public function defaultSort(): string | null
    {
        return '-created_at';
    }

    public function schema(): array
    {
        return [
            'name' => 'string',
            'code' => 'string',
            'description' => ['type' => 'string', 'nullable' => true],
            'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
            'price_in_cents' => 'integer',
            'featured' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
