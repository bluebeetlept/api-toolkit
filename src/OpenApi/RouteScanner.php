<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\OpenApi;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\OpenApi\Attributes\ApiResource;
use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Resources\Resource;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

final readonly class RouteScanner
{
    public function __construct(
        private Router $router,
        private Config $config,
    ) {
    }

    /**
     * Scan the routes the `api-toolkit.openapi.paths` patterns cover and return endpoint definitions for those
     * using api-toolkit.
     *
     * @return list<EndpointDefinition>
     */
    public function scan(): array
    {
        /** @var list<string> $paths */
        $paths = $this->config->get('api-toolkit.openapi.paths', ['*']);

        return Collection::make($this->router->getRoutes()->getRoutes())
            ->filter(static fn (Route $route): bool => Str::is($paths, mb_ltrim($route->uri(), '/')))
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

        [$controller, $method] = Str::parseCallback($action['uses'], '__invoke');

        if (! class_exists($controller)) {
            return null;
        }

        $reflection = new ReflectionClass($controller);

        if (! $reflection->hasMethod($method)) {
            return null;
        }

        $methodReflection = $reflection->getMethod($method);
        $source = MethodSource::of($methodReflection) ?? '';

        $resourceClass = $this->declaredResource($methodReflection, $reflection)
            ?? $this->extractResourceClass($source, $controller)
            ?? $this->resourceForListedModel($source, $controller);

        // Only an endpoint answering through the toolkit hands its models to a resource
        if ($resourceClass === null && $this->answersThroughToolkit($reflection, $methodReflection)) {
            $resourceClass = $this->resourceForBoundModel($methodReflection);
        }

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
     * A list endpoint names its model, in `QueryBuilder::for(Model::class)` or a `Model::query()` it paginates, and
     * the resource map knows its resource.
     *
     * @return class-string<resource>|null
     */
    private function resourceForListedModel(string $source, string $controllerClass): string | null
    {
        if (! $this->isListEndpoint($source)) {
            return null;
        }

        foreach (['/QueryBuilder::for\(\s*(\w+)::(?:class|query\()/', '/(\w+)::query\(/'] as $pattern) {
            if (preg_match($pattern, $source, $matches)) {
                $model = $this->resolveClassName($matches[1], $controllerClass);

                return $model === null ? null : Resource::resolveResourceClass($model);
            }
        }

        return null;
    }

    /**
     * The resource the endpoint says it answers with, on its method or else on its controller.
     *
     * @param ReflectionClass<object> $controller
     *
     * @return class-string<resource>|null
     */
    private function declaredResource(ReflectionMethod $method, ReflectionClass $controller): string | null
    {
        foreach ([$method, $controller] as $declarer) {
            $attribute = $declarer->getAttributes(ApiResource::class)[0] ?? null;

            if ($attribute !== null) {
                return $attribute->newInstance()->resource;
            }
        }

        return null;
    }

    /**
     * An endpoint about one record, such as viewing or deleting it, often takes the record as a route-bound
     * parameter. It is a guess, the last one tried; an endpoint answering with something else says so with
     * #[ApiResource].
     *
     * @return class-string<resource>|null
     */
    private function resourceForBoundModel(ReflectionMethod $method): string | null
    {
        foreach ($this->parameterClasses($method) as $class) {
            $resource = Resource::resolveResourceClass($class);

            if ($resource !== null) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * @param ReflectionClass<object> $controller
     */
    private function answersThroughToolkit(ReflectionClass $controller, ReflectionMethod $method): bool
    {
        $types = [
            ...array_map(static fn (ReflectionProperty $property): mixed => $property->getType(), $controller->getProperties()),
            ...array_map(static fn (ReflectionParameter $parameter): mixed => $parameter->getType(), $method->getParameters()),
        ];

        foreach ($types as $type) {
            if ($type instanceof ReflectionNamedType && is_a($type->getName(), Response::class, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<class-string>
     */
    private function parameterClasses(ReflectionMethod $method): array
    {
        $classes = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin() && class_exists($type->getName())) {
                $classes[] = $type->getName();
            }
        }

        return $classes;
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
     * Whether the endpoint answers with a page of records rather than one.
     */
    private function isListEndpoint(string $source): bool
    {
        return str_contains($source, '->paginate(')
            || str_contains($source, '->cursor(')
            || str_contains($source, 'QueryBuilder::for(');
    }

    /**
     * The form request the endpoint validates its input with.
     *
     * @return class-string|null
     */
    private function extractFormRequestClass(ReflectionMethod $method): string | null
    {
        foreach ($this->parameterClasses($method) as $class) {
            if (is_subclass_of($class, FormRequest::class)) {
                return $class;
            }
        }

        return null;
    }
}
