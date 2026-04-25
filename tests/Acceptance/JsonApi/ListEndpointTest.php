<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Acceptance\JsonApi;

use Eufaturo\ApiToolkit\Http\Response;
use Eufaturo\ApiToolkit\QueryBuilder;
use Eufaturo\ApiToolkit\Tests\Fixtures\Models\Category;
use Eufaturo\ApiToolkit\Tests\Fixtures\Models\Product;
use Eufaturo\ApiToolkit\Tests\Fixtures\Models\Tag;
use Eufaturo\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class ListEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/v1/products', function (Response $response) {
            $products = QueryBuilder::for(Product::class, request())
                ->fromResource(ProductResource::class)
                ->paginate()
            ;

            return $response->success($products, ProductResource::class)->respond();
        });
    }

    #[Test]
    #[TestDox('it returns a valid JSON:API collection response')]
    public function it_returns_valid_collection(): void
    {
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);
        Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01']);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

        $response->assertJsonStructure([
            'data' => [
                '*' => ['type', 'id', 'attributes', 'relationships', 'links', 'meta'],
            ],
            'meta' => ['page'],
            'links',
        ]);

        $response->assertJsonPath('data.0.type', 'products');
        $response->assertJsonPath('data.0.id', 'p1');
        $response->assertJsonPath('data.0.attributes.name', 'Widget');
        $response->assertJsonPath('data.0.attributes.code', 'W01');
    }

    #[Test]
    #[TestDox('it includes pagination meta and links')]
    public function it_includes_pagination(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Product::create(['public_id' => "p{$i}", 'name' => "Product {$i}", 'code' => "C{$i}"]);
        }

        $response = $this->getJson('/api/v1/products?page[size]=10&page[number]=2');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.page.currentPage', 2);
        $response->assertJsonPath('meta.page.perPage', 10);
        $response->assertJsonPath('meta.page.total', 25);
        $response->assertJsonPath('meta.page.lastPage', 3);

        $json = $response->json();
        $this->assertCount(10, $json['data']);
        $this->assertNotNull($json['links']['prev']);
        $this->assertNotNull($json['links']['next']);
    }

    #[Test]
    #[TestDox('it applies filters')]
    public function it_applies_filters(): void
    {
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'status' => 'active']);
        Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'status' => 'inactive']);
        Product::create(['public_id' => 'p3', 'name' => 'Widget Pro', 'code' => 'W02', 'status' => 'active']);

        $response = $this->getJson('/api/v1/products?filter[status]=active');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        $response = $this->getJson('/api/v1/products?filter[name]=Widget');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    #[TestDox('it applies sorting')]
    public function it_applies_sorting(): void
    {
        Product::create(['public_id' => 'p1', 'name' => 'Banana', 'code' => 'B01']);
        Product::create(['public_id' => 'p2', 'name' => 'Apple', 'code' => 'A01']);
        Product::create(['public_id' => 'p3', 'name' => 'Cherry', 'code' => 'C01']);

        $response = $this->getJson('/api/v1/products?sort=name');

        $response->assertStatus(200);
        $this->assertSame('Apple', $response->json('data.0.attributes.name'));
        $this->assertSame('Banana', $response->json('data.1.attributes.name'));
        $this->assertSame('Cherry', $response->json('data.2.attributes.name'));

        $response = $this->getJson('/api/v1/products?sort=-name');

        $this->assertSame('Cherry', $response->json('data.0.attributes.name'));
    }

    #[Test]
    #[TestDox('it includes relationships when requested')]
    public function it_includes_relationships(): void
    {
        $category = Category::create(['public_id' => 'c1', 'name' => 'Electronics', 'slug' => 'electronics']);
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'category_id' => $category->id]);

        $response = $this->getJson('/api/v1/products?include=category');

        $response->assertStatus(200);

        $response->assertJsonPath('data.0.relationships.category.data.type', 'categories');
        $response->assertJsonPath('data.0.relationships.category.data.id', 'c1');

        $response->assertJsonStructure([
            'included' => [
                ['type', 'id', 'attributes'],
            ],
        ]);

        $response->assertJsonPath('included.0.type', 'categories');
        $response->assertJsonPath('included.0.id', 'c1');
        $response->assertJsonPath('included.0.attributes.name', 'Electronics');
    }

    #[Test]
    #[TestDox('it includes self link and custom links')]
    public function it_includes_links(): void
    {
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.links.self', '/api/v1/products/p1');
        $response->assertJsonPath('data.0.links.category', '/api/v1/products/p1/category');
    }

    #[Test]
    #[TestDox('it includes resource meta')]
    public function it_includes_meta(): void
    {
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'status' => 'active']);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.meta.is_available', true);
    }

    #[Test]
    #[TestDox('it returns empty data array when no results')]
    public function it_returns_empty_array(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonPath('data', []);
        $response->assertJsonPath('meta.page.total', 0);
    }

    #[Test]
    #[TestDox('it deduplicates included resources')]
    public function it_deduplicates_included(): void
    {
        $category = Category::create(['public_id' => 'c1', 'name' => 'Electronics', 'slug' => 'electronics']);
        Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01', 'category_id' => $category->id]);
        Product::create(['public_id' => 'p2', 'name' => 'Gadget', 'code' => 'G01', 'category_id' => $category->id]);

        $response = $this->getJson('/api/v1/products?include=category');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $this->assertCount(1, $response->json('included'));
    }

    #[Test]
    #[TestDox('it handles many-to-many relationships')]
    public function it_handles_many_to_many(): void
    {
        $tag1 = Tag::create(['public_id' => 't1', 'name' => 'Sale']);
        $tag2 = Tag::create(['public_id' => 't2', 'name' => 'New']);
        $product = Product::create(['public_id' => 'p1', 'name' => 'Widget', 'code' => 'W01']);
        $product->tags()->attach([$tag1->id, $tag2->id]);

        $response = $this->getJson('/api/v1/products?include=tags');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.0.relationships.tags.data'));
        $this->assertCount(2, $response->json('included'));
    }
}
