<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\OpenApi\Attributes\ApiResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

#[ApiResource(ProductResource::class)]
final class ScannerStubDeclaredClassController
{
    public function __invoke(Response $response): JsonResponse
    {
        return $response->success(new Product())->respond(201);
    }
}
