<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\OpenApi;

use BlueBeetle\ApiToolkit\Resources\Resource;

final readonly class EndpointDefinition
{
    /**
     * @param list<string>           $httpMethods
     * @param class-string<resource> $resourceClass
     * @param class-string|null      $formRequestClass
     */
    public function __construct(
        public string $path,
        public array $httpMethods,
        public string $resourceClass,
        public bool $isList,
        public string $controllerClass,
        public string $methodName,
        public string | null $formRequestClass,
        public string | null $routeName,
    ) {
    }
}
