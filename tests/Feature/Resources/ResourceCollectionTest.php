<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Resources\ResourceCollection;
use BlueBeetle\ApiToolkit\Tests\TestCase;
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

    private function makeItem(string $id, string $name): stdClass
    {
        $item = new stdClass();
        $item->id = $id;
        $item->name = $name;

        return $item;
    }
}

class StubResource extends Resource
{
    protected string $type = 'items';

    public function attributes($model): array
    {
        return [
            'name' => $model->name,
        ];
    }
}
