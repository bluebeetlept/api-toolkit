<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Feature\Parsers;

use Eufaturo\ApiToolkit\Parsers\SortParser;
use Eufaturo\ApiToolkit\Tests\Fixtures\Models\Product;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class SortParserTest extends TestCase
{
    #[Test]
    #[TestDox('it sorts ascending by default')]
    public function it_sorts_ascending(): void
    {
        $parser = new SortParser(allowed: ['name']);

        $request = Request::create('/', 'GET', ['sort' => 'name']);
        $query = Product::query();

        $parser->apply($request, $query);

        $orders = $query->getQuery()->orders ?? [];
        $this->assertCount(1, $orders);
        $this->assertSame('name', $orders[0]['column']);
        $this->assertSame('asc', $orders[0]['direction']);
    }

    #[Test]
    #[TestDox('it sorts descending with dash prefix')]
    public function it_sorts_descending(): void
    {
        $parser = new SortParser(allowed: ['created_at']);

        $request = Request::create('/', 'GET', ['sort' => '-created_at']);
        $query = Product::query();

        $parser->apply($request, $query);

        $orders = $query->getQuery()->orders ?? [];
        $this->assertCount(1, $orders);
        $this->assertSame('created_at', $orders[0]['column']);
        $this->assertSame('desc', $orders[0]['direction']);
    }

    #[Test]
    #[TestDox('it handles multiple sort fields')]
    public function it_handles_multiple_sorts(): void
    {
        $parser = new SortParser(allowed: ['name', 'created_at']);

        $request = Request::create('/', 'GET', ['sort' => '-created_at,name']);
        $query = Product::query();

        $parser->apply($request, $query);

        $orders = $query->getQuery()->orders ?? [];
        $this->assertCount(2, $orders);
        $this->assertSame('created_at', $orders[0]['column']);
        $this->assertSame('desc', $orders[0]['direction']);
        $this->assertSame('name', $orders[1]['column']);
        $this->assertSame('asc', $orders[1]['direction']);
    }

    #[Test]
    #[TestDox('it ignores disallowed sort fields')]
    public function it_ignores_disallowed_fields(): void
    {
        $parser = new SortParser(allowed: ['name']);

        $request = Request::create('/', 'GET', ['sort' => 'secret_column']);
        $query = Product::query();

        $parser->apply($request, $query);

        $orders = $query->getQuery()->orders ?? [];
        $this->assertEmpty($orders);
    }

    #[Test]
    #[TestDox('it applies default sort when no sort parameter')]
    public function it_applies_default_sort(): void
    {
        $parser = new SortParser(allowed: ['created_at'], default: '-created_at');

        $request = Request::create('/');
        $query = Product::query();

        $parser->apply($request, $query);

        $orders = $query->getQuery()->orders ?? [];
        $this->assertCount(1, $orders);
        $this->assertSame('created_at', $orders[0]['column']);
        $this->assertSame('desc', $orders[0]['direction']);
    }

    #[Test]
    #[TestDox('it does nothing when no sort and no default')]
    public function it_does_nothing_without_sort(): void
    {
        $parser = new SortParser(allowed: ['name']);

        $request = Request::create('/');
        $query = Product::query();

        $parser->apply($request, $query);

        $orders = $query->getQuery()->orders ?? [];
        $this->assertEmpty($orders);
    }
}
