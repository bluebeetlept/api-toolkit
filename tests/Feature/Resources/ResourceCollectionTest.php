<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Resources;

use BlueBeetle\ApiToolkit\Resources\ResourceCollection;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\StubResource;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;

final class ResourceCollectionTest extends TestCase
{
    #[Test]
    #[TestDox('it serializes a collection of items')]
    public function it_serializes_collection(): void
    {
        $items = Collection::make([
            $this->makeItem('1', 'Widget'),
            $this->makeItem('2', 'Gadget'),
        ]);

        $collection = new ResourceCollection($items, StubResource::class);
        $result = $collection->toArray();

        $this->assertCount(2, $result['data']);
        $this->assertSame('items', $result['data'][0]['type']);
        $this->assertSame('1', $result['data'][0]['id']);
        $this->assertSame('Widget', $result['data'][0]['attributes']['name']);
        $this->assertSame('2', $result['data'][1]['id']);
    }

    #[Test]
    #[TestDox('it includes pagination meta and links for offset pagination')]
    public function it_includes_offset_pagination(): void
    {
        $items = [
            $this->makeItem('1', 'Widget'),
            $this->makeItem('2', 'Gadget'),
        ];

        $paginator = new LengthAwarePaginator(
            items: $items,
            total: 50,
            perPage: 20,
            currentPage: 2,
        );

        $collection = new ResourceCollection($paginator, StubResource::class);
        $result = $collection->toArray();

        $this->assertCount(2, $result['data']);
        $this->assertSame(2, $result['meta']['page']['currentPage']);
        $this->assertSame(3, $result['meta']['page']['lastPage']);
        $this->assertSame(20, $result['meta']['page']['perPage']);
        $this->assertSame(50, $result['meta']['page']['total']);
        $this->assertArrayHasKey('first', $result['links']);
        $this->assertArrayHasKey('last', $result['links']);
        $this->assertArrayHasKey('prev', $result['links']);
        $this->assertArrayHasKey('next', $result['links']);
    }

    #[Test]
    #[TestDox('it handles empty collections')]
    public function it_handles_empty_collections(): void
    {
        $collection = new ResourceCollection(Collection::make(), StubResource::class);
        $result = $collection->toArray();

        $this->assertSame([], $result['data']);
        $this->assertArrayNotHasKey('meta', $result);
    }

    #[Test]
    #[TestDox('it handles arrays')]
    public function it_handles_arrays(): void
    {
        $items = [
            $this->makeItem('1', 'Widget'),
        ];

        $collection = new ResourceCollection($items, StubResource::class);
        $result = $collection->toArray();

        $this->assertCount(1, $result['data']);
        $this->assertSame('1', $result['data'][0]['id']);
    }

    #[Test]
    #[TestDox('it includes cursor pagination meta and links')]
    public function it_includes_cursor_pagination(): void
    {
        $items = [
            $this->makeItem('1', 'Widget'),
            $this->makeItem('2', 'Gadget'),
        ];

        $paginator = new CursorPaginator(
            items: $items,
            perPage: 2,
            cursor: null,
            options: ['parameters' => ['cursor']],
        );

        $collection = new ResourceCollection($paginator, StubResource::class);
        $result = $collection->toArray();

        $this->assertCount(2, $result['data']);
        $this->assertArrayHasKey('page', $result['meta']);
        $this->assertSame(2, $result['meta']['page']['perPage']);
        $this->assertArrayHasKey('hasMore', $result['meta']['page']);
        $this->assertArrayHasKey('prev', $result['links']);
        $this->assertArrayHasKey('next', $result['links']);
    }

    #[Test]
    #[TestDox('cursor pagination does not include offset-specific meta')]
    public function it_excludes_offset_meta_for_cursor(): void
    {
        $items = [$this->makeItem('1', 'Widget')];

        $paginator = new CursorPaginator(
            items: $items,
            perPage: 10,
            cursor: null,
        );

        $collection = new ResourceCollection($paginator, StubResource::class);
        $result = $collection->toArray();

        $this->assertArrayNotHasKey('currentPage', $result['meta']['page']);
        $this->assertArrayNotHasKey('lastPage', $result['meta']['page']);
        $this->assertArrayNotHasKey('total', $result['meta']['page']);
    }

    #[Test]
    #[TestDox('it skips null relations in included')]
    public function it_skips_null_relations_in_included(): void
    {
        $product = Product::create([
            'public_id' => 'prod-1',
            'name' => 'Widget',
            'code' => 'W01',
            'price_in_cents' => 1000,
            'featured' => false,
            'category_id' => null,
        ]);

        // Load the category relation — it will be null
        $product->load('category');

        $collection = new ResourceCollection(
            data: Collection::make([$product]),
            resourceClass: ProductResource::class,
        );

        $result = $collection->toArray();

        $this->assertCount(1, $result['data']);
        // No included section since the only relation is null
        $this->assertArrayNotHasKey('included', $result);
    }

    private function makeItem(string $id, string $name): stdClass
    {
        $item = new stdClass();
        $item->id = $id;
        $item->name = $name;

        return $item;
    }
}
