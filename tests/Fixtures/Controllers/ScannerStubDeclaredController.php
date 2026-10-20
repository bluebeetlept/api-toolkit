<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\OpenApi\Attributes\ApiResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\ScannerStubCreateRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\CategoryResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

final class ScannerStubDeclaredController
{
    #[ApiResource(ProductResource::class)]
    public function store(ScannerStubCreateRequest $request, Response $response): JsonResponse
    {
        return $response->success(new Product())->respond(201);
    }

    /**
     * Bound to a product, but answers with its category.
     */
    #[ApiResource(CategoryResource::class)]
    public function category(Product $product, Response $response): JsonResponse
    {
        return $response->success($product->category)->respond();
    }
}
