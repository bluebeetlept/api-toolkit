<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

final class ScannerStubShowController
{
    public function __invoke(Product $product, Response $response): JsonResponse
    {
        return $response->success($product, ProductResource::class)->respond();
    }
}
