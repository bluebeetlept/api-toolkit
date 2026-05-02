<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductListController
{
    public function __invoke(Request $request, Response $response): JsonResponse
    {
        $products = QueryBuilder::for(Product::class, $request)
            ->fromResource(ProductResource::class)
            ->paginate()
        ;

        return $response->success($products, ProductResource::class)->respond();
    }
}
