<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Http\JsonResponse;

final class ProductDeleteController
{
    public function __invoke(Product $product, Response $response): JsonResponse
    {
        $product->delete();

        return $response->success()->respond(204);
    }
}
