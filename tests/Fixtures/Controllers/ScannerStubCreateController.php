<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Controllers;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\ScannerStubCreateRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

final class ScannerStubCreateController
{
    public function __invoke(ScannerStubCreateRequest $request, Response $response): JsonResponse
    {
        return $response->success(null, ProductResource::class)->respond(201);
    }
}
