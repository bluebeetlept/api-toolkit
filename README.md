# API Toolkit

<p>
    <a href="https://github.com/bluebeetlept/api-toolkit/actions?query=workflow%3Atests"><img src="https://github.com/bluebeetlept/api-toolkit/actions/workflows/tests.yml/badge.svg?branch=main" alt="Tests"></a>
    <a href="https://github.com/bluebeetlept/api-toolkit/actions?query=workflow%3Acode-analysis"><img src="https://github.com/bluebeetlept/api-toolkit/actions/workflows/code-analysis.yml/badge.svg?branch=main" alt="Code Analysis"></a>
    <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/github/license/bluebeetlept/api-toolkit?label=License" alt="License"></a>
</p>

JSON:API compliant toolkit for building REST APIs with Laravel.

## Installation

```bash
composer require bluebeetle/api-toolkit
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
    public function __invoke(Request $request)
    {
        return QueryBuilder::for(Product::class, $request)
            ->fromResource(ProductResource::class)
            ->paginate();
    }
}
```

## Documentation

Full documentation is available in the `docs/` directory, powered by [Mintlify](https://mintlify.com).

## Testing

```bash
composer test
```

## Credits

- [Blue Beetle](https://bluebeetle.pt)
- [All Contributors](../../contributors)

## License

Licensed under the [MIT license](https://opensource.org/licenses/MIT).
