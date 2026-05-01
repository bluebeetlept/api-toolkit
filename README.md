# API Toolkit

[![Tests][icon-action-tests]][url-action-tests]
[![Code Analysis][icon-action-analysis]][url-action-analysis]
[![License][icon-license]][url-license]

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

[url-action-tests]: https://github.com/bluebeetlept/api-toolkit/actions?query=workflow%3Atests
[url-action-analysis]: https://github.com/bluebeetlept/api-toolkit/actions?query=workflow%3Acode-analysis
[url-license]: https://opensource.org/licenses/MIT

[icon-action-tests]: https://github.com/bluebeetlept/api-toolkit/actions/workflows/tests.yml/badge.svg?branch=main
[icon-action-analysis]: https://github.com/bluebeetlept/api-toolkit/actions/workflows/code-analysis.yml/badge.svg?branch=main
[icon-license]: https://img.shields.io/github/license/bluebeetlept/api-toolkit?label=License
