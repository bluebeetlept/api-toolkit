<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Http\JsonResponse;

final class ScannerStubPageController
{
    public function __invoke(Product $product): JsonResponse
    {
        return response()->json(['name' => $product->name]);
    }
}
