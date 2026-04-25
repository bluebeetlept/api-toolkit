# API Toolkit

JSON:API compliant toolkit for building REST APIs with Laravel.

## Installation

```shell
composer require eufaturo/api-toolkit:^1.0@dev
```

## Quick Start

Define a resource:

```php
final class ProductResource extends Resource
{
    protected string $model = Product::class;

    public function attributes(Product $product): array
    {
        return [
            'name' => $product->name,
            'code' => $product->code,
        ];
    }
}
```

Use it in a controller:

```php
final class ListController
{
    public function __invoke(Request $request, Response $response): JsonResponse
    {
        $products = QueryBuilder::for(Product::class, $request)
            ->fromResource(ProductResource::class)
            ->paginate();

        return $response->success($products, ProductResource::class)->respond();
    }
}
```

## Documentation

Full documentation is available in the `docs/` directory, powered by [Mintlify](https://mintlify.com).

## Testing

```shell
composer test
```
