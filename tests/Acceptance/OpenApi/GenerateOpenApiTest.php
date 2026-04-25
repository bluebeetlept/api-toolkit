<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\OpenApi;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\OpenApi\DocumentBuilder;
use BlueBeetle\ApiToolkit\OpenApi\RouteScanner;
use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class GenerateOpenApiTest extends TestCase
{
    private array $document;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/v1/products', [ProductListController::class, '__invoke'])
            ->name('api.v1.products.index')
        ;

        Route::get('/api/v1/products/{product}', [ProductViewController::class, '__invoke'])
            ->name('api.v1.products.show')
        ;

        Route::post('/api/v1/products', [ProductCreateController::class, '__invoke'])
            ->name('api.v1.products.store')
        ;

        $scanner = app(RouteScanner::class);
        $endpoints = $scanner->scan();

        $builder = new DocumentBuilder(
            title: 'Test API',
            version: '1.0.0',
            description: 'A test API',
            servers: [['url' => 'https://api.example.com']],
            securitySchemes: [
                'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
            ],
            security: [['bearerAuth' => []]],
        );

        $this->document = $builder->build($endpoints);
    }

    #[Test]
    #[TestDox('it generates a valid OpenAPI 3.1 document')]
    public function it_generates_valid_document(): void
    {
        $this->assertSame('3.1.0', $this->document['openapi']);
        $this->assertSame('Test API', $this->document['info']['title']);
        $this->assertSame('1.0.0', $this->document['info']['version']);
        $this->assertSame('A test API', $this->document['info']['description']);
    }

    #[Test]
    #[TestDox('it includes servers')]
    public function it_includes_servers(): void
    {
        $this->assertSame('https://api.example.com', $this->document['servers'][0]['url']);
    }

    #[Test]
    #[TestDox('it includes security schemes')]
    public function it_includes_security(): void
    {
        $this->assertArrayHasKey('bearerAuth', $this->document['components']['securitySchemes']);
        $this->assertSame('http', $this->document['components']['securitySchemes']['bearerAuth']['type']);
        $this->assertSame([['bearerAuth' => []]], $this->document['security']);
    }

    #[Test]
    #[TestDox('it includes top-level tags')]
    public function it_includes_tags(): void
    {
        $tagNames = array_column($this->document['tags'], 'name');

        $this->assertContains('Products', $tagNames);
    }

    #[Test]
    #[TestDox('it generates the Product schema with attributes')]
    public function it_generates_product_schema(): void
    {
        $schema = $this->document['components']['schemas']['Product'];

        $this->assertSame('object', $schema['type']);
        $this->assertContains('type', $schema['required']);
        $this->assertContains('id', $schema['required']);
        $this->assertSame('products', $schema['properties']['type']['example']);

        $attributes = $schema['properties']['attributes'];
        $this->assertSame('string', $attributes['properties']['name']['type']);
        $this->assertSame('string', $attributes['properties']['code']['type']);
        $this->assertSame('integer', $attributes['properties']['price_in_cents']['type']);
        $this->assertSame('boolean', $attributes['properties']['featured']['type']);
        $this->assertSame(['active', 'inactive'], $attributes['properties']['status']['enum']);
    }

    #[Test]
    #[TestDox('it generates relationship schemas')]
    public function it_generates_relationships(): void
    {
        $schema = $this->document['components']['schemas']['Product'];
        $relationships = $schema['properties']['relationships'];

        $this->assertArrayHasKey('category', $relationships['properties']);
        $this->assertArrayHasKey('tags', $relationships['properties']);
    }

    #[Test]
    #[TestDox('it generates list endpoint path with query params')]
    public function it_generates_list_path(): void
    {
        $path = $this->document['paths']['/api/v1/products']['get'];

        $this->assertSame('api.v1.products.index', $path['operationId']);
        $this->assertContains('Products', $path['tags']);

        $paramNames = array_column($path['parameters'], 'name');
        $this->assertContains('filter[name]', $paramNames);
        $this->assertContains('filter[status]', $paramNames);
        $this->assertContains('sort', $paramNames);
        $this->assertContains('include', $paramNames);
        $this->assertContains('page[number]', $paramNames);
        $this->assertContains('page[size]', $paramNames);
        $this->assertContains('page[cursor]', $paramNames);

        $this->assertArrayHasKey('200', $path['responses']);
        $this->assertArrayHasKey('401', $path['responses']);
    }

    #[Test]
    #[TestDox('it generates single endpoint path')]
    public function it_generates_single_path(): void
    {
        $pathItem = $this->document['paths']['/api/v1/products/{product}'];
        $path = $pathItem['get'];

        $this->assertSame('api.v1.products.show', $path['operationId']);

        $this->assertArrayHasKey('200', $path['responses']);
        $this->assertArrayHasKey('404', $path['responses']);
        $this->assertArrayHasKey('401', $path['responses']);

        // Path parameter
        $this->assertSame('product', $pathItem['parameters'][0]['name']);
        $this->assertSame('path', $pathItem['parameters'][0]['in']);
        $this->assertTrue($pathItem['parameters'][0]['required']);
    }

    #[Test]
    #[TestDox('it generates POST endpoint with 201 response')]
    public function it_generates_post_path(): void
    {
        $path = $this->document['paths']['/api/v1/products']['post'];

        $this->assertSame('api.v1.products.store', $path['operationId']);
        $this->assertArrayHasKey('201', $path['responses']);
        $this->assertArrayHasKey('422', $path['responses']);
    }

    #[Test]
    #[TestDox('it uses shared error response refs')]
    public function it_uses_shared_error_refs(): void
    {
        $path = $this->document['paths']['/api/v1/products']['get'];

        $this->assertSame(
            '#/components/responses/UnauthorizedException',
            $path['responses']['401']['$ref'],
        );

        $this->assertArrayHasKey('UnauthorizedException', $this->document['components']['responses']);
        $this->assertArrayHasKey('NotFoundHttpException', $this->document['components']['responses']);
        $this->assertArrayHasKey('ValidationException', $this->document['components']['responses']);
    }

    #[Test]
    #[TestDox('it generates collection and single response schemas')]
    public function it_generates_response_schemas(): void
    {
        $schemas = $this->document['components']['schemas'];

        $this->assertArrayHasKey('Product', $schemas);
        $this->assertArrayHasKey('ProductCollection', $schemas);
        $this->assertArrayHasKey('ProductResponse', $schemas);
        $this->assertArrayHasKey('JsonApiError', $schemas);

        // Collection wraps in array
        $this->assertSame('array', $schemas['ProductCollection']['properties']['data']['type']);
        $this->assertSame('#/components/schemas/Product', $schemas['ProductCollection']['properties']['data']['items']['$ref']);

        // Single wraps in data
        $this->assertSame('#/components/schemas/Product', $schemas['ProductResponse']['properties']['data']['$ref']);
    }

    #[Test]
    #[TestDox('it generates valid JSON output')]
    public function it_generates_valid_json(): void
    {
        $json = json_encode($this->document, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $this->assertNotFalse($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame('3.1.0', $decoded['openapi']);
    }
}

final class ProductListController
{
    public function __invoke(Request $request, Response $response): JsonResponse
    {
        $products = QueryBuilder::for(Product::class, $request)
            ->fromResource(ProductResource::class)
            ->paginate()
        ;

        return $response->success($products, ProductResource::class)->respond();
    }
}

final class ProductViewController
{
    public function __invoke(Product $product, Response $response): JsonResponse
    {
        return $response->success($product, ProductResource::class)->respond();
    }
}

final class ProductCreateController
{
    public function __invoke(Request $request, Response $response): JsonResponse
    {
        $product = Product::create($request->all());

        return $response->success($product, ProductResource::class)->respond(201);
    }
}

final class ProductDeleteController
{
    public function __invoke(Product $product, Response $response): JsonResponse
    {
        $product->delete();

        return $response->success()->respond(204);
    }
}
