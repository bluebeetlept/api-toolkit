<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScannerStubBuiltinParamController
{
    public function __invoke(string $query, Response $response): JsonResponse
    {
        return QueryBuilder::for(Product::class, Request::create('/'))
            ->fromResource(ProductResource::class)
            ->paginate()
            ->respond()
        ;
    }
}
