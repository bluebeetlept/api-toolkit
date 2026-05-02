<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Pagination;

use BlueBeetle\ApiToolkit\Pagination\OffsetPaginator;
use BlueBeetle\ApiToolkit\Parsers\PageParser;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class OffsetPaginatorTest extends TestCase
{
    #[Test]
    #[TestDox('it paginates with default page size and page number')]
    public function it_paginates_with_defaults(): void
    {
        $this->createProducts(5);

        $request = Request::create('/');
        $paginator = new OffsetPaginator(new PageParser(defaultSize: 20, maxSize: 100));

        $result = $paginator->paginate($request, Product::query());

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(20, $result->perPage());
        $this->assertSame(1, $result->currentPage());
        $this->assertSame(5, $result->total());
    }

    #[Test]
    #[TestDox('it respects custom page size from request')]
    public function it_respects_custom_page_size(): void
    {
        $this->createProducts(5);

        $request = Request::create('/', 'GET', ['page' => ['size' => 2]]);
        $paginator = new OffsetPaginator(new PageParser(defaultSize: 20, maxSize: 100));

        $result = $paginator->paginate($request, Product::query());

        $this->assertSame(2, $result->perPage());
        $this->assertCount(2, $result->items());
    }

    #[Test]
    #[TestDox('it respects page number from request')]
    public function it_respects_page_number(): void
    {
        $this->createProducts(5);

        $request = Request::create('/', 'GET', ['page' => ['size' => 2, 'number' => 2]]);
        $paginator = new OffsetPaginator(new PageParser(defaultSize: 20, maxSize: 100));

        $result = $paginator->paginate($request, Product::query());

        $this->assertSame(2, $result->currentPage());
        $this->assertCount(2, $result->items());
    }

    #[Test]
    #[TestDox('it uses custom PageParser configuration')]
    public function it_uses_custom_page_parser(): void
    {
        $this->createProducts(5);

        $request = Request::create('/');
        $paginator = new OffsetPaginator(new PageParser(defaultSize: 3, maxSize: 10));

        $result = $paginator->paginate($request, Product::query());

        $this->assertSame(3, $result->perPage());
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
