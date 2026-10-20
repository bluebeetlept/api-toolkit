<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Enums\OpenApiStubStatus;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScannerStubFilteredListController
{
    public function __invoke(Request $request): JsonResponse
    {
        $status = $request->enum('status', OpenApiStubStatus::class);

        return QueryBuilder::for(Product::query()->where('status', $status), $request)->paginate()->respond();
    }
}
