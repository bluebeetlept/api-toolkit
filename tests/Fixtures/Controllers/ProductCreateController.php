<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductCreateController
{
    public function __invoke(Request $request, Response $response): JsonResponse
    {
        $product = Product::create($request->all());

        return $response->success($product, ProductResource::class)->respond(201);
    }
}
