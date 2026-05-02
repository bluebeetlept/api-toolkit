<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature;

use BlueBeetle\ApiToolkit\Http\SuccessResponse;
use BlueBeetle\ApiToolkit\Parsers\Filters\ExactFilter;
use BlueBeetle\ApiToolkit\QueryBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\StubModel;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\StubQueryResource;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class QueryBuilderTest extends TestCase
{
    #[Test]
    #[TestDox('it creates a query builder from a model class')]
    public function it_creates_from_model(): void
    {
        $request = Request::create('/');

        $builder = QueryBuilder::for(StubModel::class, $request);

        $this->assertInstanceOf(QueryBuilder::class, $builder);
    }

    #[Test]
    #[TestDox('it creates a query builder from an existing builder')]
    public function it_creates_from_builder(): void
    {
        $request = Request::create('/');
        $eloquentBuilder = StubModel::query();

        $builder = QueryBuilder::for($eloquentBuilder, $request);

        $this->assertInstanceOf(QueryBuilder::class, $builder);
    }

    #[Test]
    #[TestDox('it applies filters from resource')]
    public function it_applies_filters_from_resource(): void
    {
        $request = Request::create('/', 'GET', ['filter' => ['name' => 'Widget']]);

        $builder = QueryBuilder::for(StubModel::class, $request)
            ->fromResource(StubQueryResource::class)
        ;

        $query = $builder->apply()->getQuery();
        $sql = $query->toSql();

        // The PartialFilter adds a LIKE clause
        $this->assertStringContainsString('like', mb_strtolower($sql));
    }

    #[Test]
    #[TestDox('it overrides resource filters with explicit filters')]
    public function it_overrides_filters(): void
    {
        $request = Request::create('/', 'GET', ['filter' => ['name' => 'Widget']]);

        $builder = QueryBuilder::for(StubModel::class, $request)
            ->fromResource(StubQueryResource::class)
            ->allowedFilters(['name' => new ExactFilter()])
        ;

        $query = $builder->apply()->getQuery();
        $sql = $query->toSql();

        // ExactFilter uses = instead of LIKE
        $this->assertStringNotContainsString('like', mb_strtolower($sql));
    }

    #[Test]
    #[TestDox('it applies sorts from resource')]
    public function it_applies_sorts_from_resource(): void
    {
        $request = Request::create('/');

        $builder = QueryBuilder::for(StubModel::class, $request)
            ->fromResource(StubQueryResource::class)
        ;

        $query = $builder->apply()->getQuery();

        // Default sort should be applied
        $orders = $query->getQuery()->orders ?? [];
        $this->assertNotEmpty($orders);
        $this->assertSame('created_at', $orders[0]['column']);
        $this->assertSame('desc', $orders[0]['direction']);
    }

    #[Test]
    #[TestDox('it overrides default sort')]
    public function it_overrides_default_sort(): void
    {
        $request = Request::create('/');

        $builder = QueryBuilder::for(StubModel::class, $request)
            ->fromResource(StubQueryResource::class)
            ->defaultSort('name')
        ;

        $query = $builder->apply()->getQuery();

        $orders = $query->getQuery()->orders ?? [];
        $this->assertNotEmpty($orders);
        $this->assertSame('name', $orders[0]['column']);
        $this->assertSame('asc', $orders[0]['direction']);
    }

    #[Test]
    #[TestDox('it applies includes from request')]
    public function it_applies_includes(): void
    {
        $request = Request::create('/', 'GET', ['include' => 'category']);

        $builder = QueryBuilder::for(StubModel::class, $request)
            ->fromResource(StubQueryResource::class)
        ;

        $query = $builder->apply()->getQuery();

        $eagerLoads = $query->getEagerLoads();
        $this->assertArrayHasKey('category', $eagerLoads);
    }

    #[Test]
    #[TestDox('it works without a resource')]
    public function it_works_without_resource(): void
    {
        $request = Request::create('/', 'GET', ['filter' => ['status' => 'active']]);

        $builder = QueryBuilder::for(StubModel::class, $request)
            ->allowedFilters(['status' => new ExactFilter()])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('-created_at')
        ;

        $query = $builder->apply()->getQuery();
        $sql = $query->toSql();

        $this->assertStringContainsString('status', $sql);
    }

    #[Test]
    #[TestDox('it ignores disallowed includes')]
    public function it_ignores_disallowed_includes(): void
    {
        $request = Request::create('/', 'GET', ['include' => 'category,secret']);

        $builder = QueryBuilder::for(StubModel::class, $request)
            ->allowedIncludes(['category'])
        ;

        $query = $builder->apply()->getQuery();

        $eagerLoads = $query->getEagerLoads();
        $this->assertArrayHasKey('category', $eagerLoads);
        $this->assertArrayNotHasKey('secret', $eagerLoads);
    }

    #[Test]
    #[TestDox('it does nothing with no configuration')]
    public function it_does_nothing_bare(): void
    {
        $request = Request::create('/', 'GET', [
            'filter' => ['status' => 'active'],
            'sort' => 'name',
            'include' => 'category',
        ]);

        $builder = QueryBuilder::for(StubModel::class, $request);
        $query = $builder->getQuery();

        // No filters, sorts, or includes applied (getQuery returns raw builder)
        $this->assertStringNotContainsString('status', $query->toSql());
        $this->assertEmpty($query->getQuery()->orders ?? []);
        $this->assertEmpty($query->getEagerLoads());
    }

    #[Test]
    #[TestDox('it returns a SuccessResponse from paginate')]
    public function it_paginates(): void
    {
        Product::create([
            'public_id' => 'prod-1',
            'name' => 'Widget',
            'code' => 'W01',
            'price_in_cents' => 1000,
            'featured' => false,
        ]);

        $request = Request::create('/', 'GET', ['page' => ['size' => 10]]);

        $result = QueryBuilder::for(Product::class, $request)
            ->fromResource(ProductResource::class)
            ->paginate()
        ;

        $this->assertInstanceOf(SuccessResponse::class, $result);

        $array = $result->toArray();
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertArrayHasKey('links', $array);
    }

    #[Test]
    #[TestDox('it returns a SuccessResponse from cursorPaginate')]
    public function it_cursor_paginates(): void
    {
        Product::create([
            'public_id' => 'prod-1',
            'name' => 'Widget',
            'code' => 'W01',
            'price_in_cents' => 1000,
            'featured' => false,
        ]);

        $request = Request::create('/', 'GET', ['page' => ['size' => 10]]);

        $result = QueryBuilder::for(Product::class, $request)
            ->fromResource(ProductResource::class)
            ->cursorPaginate()
        ;

        $this->assertInstanceOf(SuccessResponse::class, $result);

        $array = $result->toArray();
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('meta', $array);
    }

    #[Test]
    #[TestDox('it returns a SuccessResponse from get')]
    public function it_gets(): void
    {
        Product::create([
            'public_id' => 'prod-1',
            'name' => 'Widget',
            'code' => 'W01',
            'price_in_cents' => 1000,
            'featured' => false,
        ]);

        $request = Request::create('/');

        $result = QueryBuilder::for(Product::class, $request)
            ->fromResource(ProductResource::class)
            ->get()
        ;

        $this->assertInstanceOf(SuccessResponse::class, $result);

        $array = $result->toArray();
        $this->assertArrayHasKey('data', $array);
    }

    #[Test]
    #[TestDox('apply returns the builder for further chaining')]
    public function it_applies_and_returns_builder(): void
    {
        $request = Request::create('/', 'GET', ['filter' => ['status' => 'active']]);

        $builder = QueryBuilder::for(StubModel::class, $request)
            ->allowedFilters(['status' => new ExactFilter()])
            ->apply()
        ;

        $this->assertInstanceOf(QueryBuilder::class, $builder);

        $query = $builder->getQuery();
        $this->assertStringContainsString('status', $query->toSql());
    }

    #[Test]
    #[TestDox('paginate applies filters and sorts before paginating')]
    public function it_applies_filters_before_paginating(): void
    {
        Product::create([
            'public_id' => 'prod-1',
            'name' => 'Widget',
            'code' => 'W01',
            'price_in_cents' => 1000,
            'featured' => false,
            'status' => 'active',
        ]);

        Product::create([
            'public_id' => 'prod-2',
            'name' => 'Gadget',
            'code' => 'G01',
            'price_in_cents' => 2000,
            'featured' => false,
            'status' => 'archived',
        ]);

        $request = Request::create('/', 'GET', [
            'filter' => ['status' => 'active'],
            'page' => ['size' => 10],
        ]);

        $result = QueryBuilder::for(Product::class, $request)
            ->fromResource(ProductResource::class)
            ->allowedFilters(['status' => new ExactFilter()])
            ->paginate()
        ;

        $array = $result->toArray();
        $this->assertCount(1, $array['data']);
    }
}
