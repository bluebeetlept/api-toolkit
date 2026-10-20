<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScannerStubMappedListController
{
    public function __invoke(Request $request): JsonResponse
    {
        return QueryBuilder::for(Product::class, $request)->paginate()->respond();
    }
}
