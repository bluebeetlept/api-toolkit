<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\JsonApi;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Category;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class SingleEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/v1/products/{product}', function (string $product, Response $response) {
            $product = Product::where('public_id', $product)->firstOrFail();
            $product->load(['category', 'tags']);

            return $response->success($product, ProductResource::class)->respond();
        })->where('product', '[a-zA-Z0-9-]+');
    }

    #[Test]
    #[TestDox('it returns a valid JSON:API single resource response')]
    public function it_returns_single_resource(): void
    {
        $category = Category::create(['public_id' => 'c1', 'name' => 'Electronics', 'slug' => 'electronics']);
        Product::create([
            'public_id' => 'p1',
            'name' => 'Widget',
            'code' => 'W01',
            'description' => 'A fine widget',
            'status' => 'active',
            'price_in_cents' => 999,
            'featured' => true,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/products/p1');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

        $response->assertJsonStructure([
            'data' => ['type', 'id', 'attributes', 'relationships', 'links', 'meta'],
        ]);

        $response->assertJsonPath('data.type', 'products');
        $response->assertJsonPath('data.id', 'p1');
        $response->assertJsonPath('data.attributes.name', 'Widget');
        $response->assertJsonPath('data.attributes.code', 'W01');
        $response->assertJsonPath('data.attributes.description', 'A fine widget');
        $response->assertJsonPath('data.attributes.status', 'active');
        $response->assertJsonPath('data.attributes.price_in_cents', 999);
        $response->assertJsonPath('data.attributes.featured', true);
    }

    #[Test]
    #[TestDox('it includes loaded relationships in response')]
    public function it_includes_loaded_relationships(): void
    {
        $category = Category::create(['public_id' => 'c1', 'name' => 'Electronics', 'slug' => 'electronics']);
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'category_id' => $category->id]);

        $response = $this->getJson('/api/v1/products/p1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.relationships.category.data.type', 'categories');
        $response->assertJsonPath('data.relationships.category.data.id', 'c1');
    }

    #[Test]
    #[TestDox('it includes self link')]
    public function it_includes_self_link(): void
    {
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

        $response = $this->getJson('/api/v1/products/p1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.links.self', '/api/v1/products/p1');
        $response->assertJsonPath('data.links.category', '/api/v1/products/p1/category');
    }

    #[Test]
    #[TestDox('it includes resource meta')]
    public function it_includes_meta(): void
    {
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'status' => 'active']);

        $response = $this->getJson('/api/v1/products/p1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.meta.is_available', true);
    }

    #[Test]
    #[TestDox('it handles null relationships')]
    public function it_handles_null_relationships(): void
    {
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'category_id' => null]);

        $response = $this->getJson('/api/v1/products/p1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.relationships.category.data', null);
    }

    #[Test]
    #[TestDox('it returns null data for success with null')]
    public function it_returns_null_data(): void
    {
        Route::get('/api/v1/empty', function (Response $response) {
            return $response->success()->respond();
        });

        $response = $this->getJson('/api/v1/empty');

        $response->assertStatus(200);
        $response->assertJsonPath('data', null);
    }

    #[Test]
    #[TestDox('it returns custom status code')]
    public function it_returns_custom_status(): void
    {
        Route::post('/api/v1/products', function (Response $response) {
            $product = Product::create(['public_id' => 'p1', 'name' => 'New', 'code' => 'N01']);

            return $response->success($product, ProductResource::class)->respond(201);
        });

        $response = $this->postJson('/api/v1/products');

        $response->assertStatus(201);
        $response->assertJsonPath('data.type', 'products');
    }

    #[Test]
    #[TestDox('it merges response-level meta')]
    public function it_merges_response_meta(): void
    {
        Route::get('/api/v1/products-with-meta/{product}', function (string $product, Response $response) {
            $product = Product::where('public_id', $product)->firstOrFail();

            return $response
                ->success($product, ProductResource::class)
                ->meta(['request_id' => 'req-123'])
                ->respond()
            ;
        })->where('product', '[a-zA-Z0-9-]+');

        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

        $response = $this->getJson('/api/v1/products-with-meta/p1');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.request_id', 'req-123');
    }
}
