<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Http\JsonResponse;

final class ScannerStubQueryListController
{
    public function __invoke(Response $response): JsonResponse
    {
        return $response->success(Product::query()->latest()->paginate())->respond();
    }
}
