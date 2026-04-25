<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Feature;

use Eufaturo\ApiToolkit\Parsers\Filters\ExactFilter;
use Eufaturo\ApiToolkit\Parsers\Filters\PartialFilter;
use Eufaturo\ApiToolkit\QueryBuilder;
use Eufaturo\ApiToolkit\Resources\Resource;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
}

class StubModel extends Model
{
    protected $table = 'stub_models';
}

class StubQueryResource extends Resource
{
    protected string $type = 'stubs';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }

    public function relationships(): array
    {
        return [
            'category' => StubCategoryResource::class,
        ];
    }

    public function allowedFilters(): array
    {
        return [
            'name' => new PartialFilter(),
            'status' => new ExactFilter(),
        ];
    }

    public function allowedSorts(): array
    {
        return ['name', 'created_at'];
    }

    public function defaultSort(): string | null
    {
        return '-created_at';
    }
}

class StubCategoryResource extends Resource
{
    protected string $type = 'categories';

    public function attributes($model): array
    {
        return ['name' => $model->name];
    }
}
