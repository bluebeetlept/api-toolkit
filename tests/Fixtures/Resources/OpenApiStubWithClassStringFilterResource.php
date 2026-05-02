<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Parsers\Filters\ExactFilter;
use BlueBeetle\ApiToolkit\Resources\Resource;

class OpenApiStubWithClassStringFilterResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return [];
    }

    public function allowedFilters(): array
    {
        return [
            'status' => ExactFilter::class,
        ];
    }
}
