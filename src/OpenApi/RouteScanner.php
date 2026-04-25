<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\OpenApi;

use Eufaturo\ApiToolkit\QueryBuilder;
use Eufaturo\ApiToolkit\Resources\Resource;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class RouteScanner
{
    public function __construct(
        private Router $router,
    ) {
    }

    /**
     * Scan all routes and return endpoint definitions for those using api-toolkit.
     *
     * @return list<EndpointDefinition>
     */
    public function scan(): array
    {
        return Collection::make($this->router->getRoutes()->getRoutes())
            ->map(fn (Route $route): EndpointDefinition | null => $this->analyzeRoute($route))
            ->filter()
            ->values()
            ->all()
        ;
    }

    private function analyzeRoute(Route $route): EndpointDefinition | null
    {
        $action = $route->getAction();

        if (! isset($action['uses']) || ! is_string($action['uses'])) {
            return null;
        }

        if (! str_contains($action['uses'], '@') && ! str_contains($action['uses'], '::')) {
            [$controller, $method] = [$action['uses'], '__invoke'];
        } else {
            [$controller, $method] = explode('@', $action['uses']);
        }

        if (! class_exists($controller)) {
            return null;
        }

        $reflection = new ReflectionClass($controller);

        if (! $reflection->hasMethod($method)) {
            return null;
        }

        $methodReflection = $reflection->getMethod($method);
        $source = $this->readMethodSource($methodReflection);

        if ($source === null) {
            return null;
        }

        $resourceClass = $this->extractResourceClass($source, $controller);

        if ($resourceClass === null) {
            return null;
        }

        $httpMethods = array_filter($route->methods(), fn (string $m): bool => $m !== 'HEAD');
        $isList = $this->isListEndpoint($source);
        $formRequestClass = $this->extractFormRequestClass($methodReflection);

        return new EndpointDefinition(
            path: '/'.mb_ltrim($route->uri(), '/'),
            httpMethods: array_values($httpMethods),
            resourceClass: $resourceClass,
            isList: $isList,
            controllerClass: $controller,
            methodName: $method,
            formRequestClass: $formRequestClass,
            routeName: $route->getName(),
        );
    }

    private function readMethodSource(ReflectionMethod $method): string | null
    {
        $filename = $method->getFileName();

        if ($filename === false || ! file_exists($filename)) {
            return null;
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if ($startLine === false || $endLine === false) {
            return null;
        }

        $lines = file($filename);

        if ($lines === false) {
            return null;
        }

        return implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
    }

    /**
     * Extract the Resource class from the controller method source.
     *
     * @return class-string<resource>|null
     */
    private function extractResourceClass(string $source, string $controllerClass): string | null
    {
        // Match patterns like: ProductResource::class or 'App\Resources\ProductResource'
        if (preg_match('/(\w+Resource)::class/', $source, $matches)) {
            $shortName = $matches[1];

            // Resolve the full class name from the controller's imports
            $fullClass = $this->resolveClassName($shortName, $controllerClass);

            if ($fullClass !== null && is_subclass_of($fullClass, Resource::class)) {
                return $fullClass;
            }
        }

        return null;
    }

    /**
     * Resolve a short class name to its fully qualified name using the controller's imports.
     */
    private function resolveClassName(string $shortName, string $controllerClass): string | null
    {
        $reflection = new ReflectionClass($controllerClass);
        $filename = $reflection->getFileName();

        if ($filename === false || ! file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);

        if ($content === false) {
            return null;
        }

        // Look for use statement
        if (preg_match('/use\s+([\\\\a-zA-Z0-9_]+\\\\'.preg_quote($shortName, '/').')\s*;/', $content, $matches)) {
            return $matches[1];
        }

        // Try same namespace as controller
        $namespace = $reflection->getNamespaceName();
        $candidate = $namespace.'\\'.$shortName;

        if (class_exists($candidate)) {
            return $candidate;
        }

        return null;
    }

    /**
     * Determine if this endpoint returns a collection (uses paginate/cursorPaginate/get on QueryBuilder).
     */
    private function isListEndpoint(string $source): bool
    {
        return str_contains($source, '->paginate(')
            || str_contains($source, '->cursorPaginate(')
            || str_contains($source, 'QueryBuilder::for(');
    }

    /**
     * Extract the FormRequest class from the method's type-hinted parameters.
     *
     * @return class-string|null
     */
    private function extractFormRequestClass(ReflectionMethod $method): string | null
    {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $className = $type->getName();

            if (is_subclass_of($className, \Illuminate\Foundation\Http\FormRequest::class)) {
                return $className;
            }
        }

        return null;
    }
}
