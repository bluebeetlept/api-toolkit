<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Pagination;

use BlueBeetle\ApiToolkit\Pagination\CursorPaginator;
use BlueBeetle\ApiToolkit\Parsers\PageParser;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class CursorPaginatorTest extends TestCase
{
    #[Test]
    #[TestDox('it cursor paginates with default page size')]
    public function it_cursor_paginates_with_defaults(): void
    {
        $this->createProducts(5);

        $request = Request::create('/');
        $paginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));

        $result = $paginator->paginate($request, Product::query());

        $this->assertInstanceOf(CursorPaginatorContract::class, $result);
        $this->assertSame(20, $result->perPage());
        $this->assertCount(5, $result->items());
    }

    #[Test]
    #[TestDox('it respects custom page size from request')]
    public function it_respects_page_size(): void
    {
        $this->createProducts(5);

        $request = Request::create('/', 'GET', ['page' => ['size' => 2]]);
        $paginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));

        $result = $paginator->paginate($request, Product::query());

        $this->assertSame(2, $result->perPage());
        $this->assertCount(2, $result->items());
        $this->assertTrue($result->hasMorePages());
    }

    #[Test]
    #[TestDox('it paginates with a cursor value')]
    public function it_paginates_with_cursor(): void
    {
        $this->createProducts(5);

        $initialRequest = Request::create('/', 'GET', ['page' => ['size' => 2]]);
        $initialPaginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));
        $initialResult = $initialPaginator->paginate($initialRequest, Product::query());

        $nextCursor = $initialResult->nextCursor()?->encode();
        $this->assertNotNull($nextCursor);

        $request = Request::create('/', 'GET', ['page' => ['size' => 2, 'cursor' => $nextCursor]]);
        $paginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));

        $result = $paginator->paginate($request, Product::query());

        $this->assertCount(2, $result->items());
    }

    #[Test]
    #[TestDox('it handles null cursor gracefully')]
    public function it_handles_null_cursor(): void
    {
        $this->createProducts(3);

        $request = Request::create('/', 'GET', ['page' => ['size' => 10]]);
        $paginator = new CursorPaginator(new PageParser(defaultSize: 20, maxSize: 100));

        $result = $paginator->paginate($request, Product::query());

        $this->assertCount(3, $result->items());
        $this->assertFalse($result->hasMorePages());
    }

    private function createProducts(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Product::create([
                'public_id' => "prod-{$i}",
                'name' => "Product {$i}",
                'code' => "P{$i}",
                'price_in_cents' => $i * 1000,
                'featured' => false,
            ]);
        }
    }
}
