<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\OpenApi\Attributes;

use Attribute;
use BlueBeetle\ApiToolkit\Resources\Resource;

/**
 * Names the resource an endpoint answers with, for the OpenAPI generator. On a
 * method it covers that endpoint; on a controller, every endpoint in it. It wins
 * over anything the generator would work out from the code.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class ApiResource
{
    /**
     * @param class-string<resource> $resource
     */
    public function __construct(
        public string $resource,
    ) {
    }
}
