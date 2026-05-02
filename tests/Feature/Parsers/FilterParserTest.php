<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\FilterParser;
use BlueBeetle\ApiToolkit\Parsers\Filters\ExactFilter;
use BlueBeetle\ApiToolkit\Parsers\Filters\PartialFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class FilterParserTest extends TestCase
{
    #[Test]
    #[TestDox('it applies exact filter')]
    public function it_applies_exact_filter(): void
    {
        $parser = new FilterParser([
            'status' => new ExactFilter(),
        ]);

        $request = Request::create('/', 'GET', ['filter' => ['status' => 'active']]);
        $query = Product::query();

        $parser->apply($request, $query);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('=', $sql);
        $this->assertContains('active', $bindings);
    }

    #[Test]
    #[TestDox('it applies exact filter with array value as whereIn')]
    public function it_applies_exact_filter_with_array(): void
    {
        $parser = new FilterParser([
            'status' => new ExactFilter(),
        ]);

        $request = Request::create('/', 'GET', ['filter' => ['status' => ['active', 'pending']]]);
        $query = Product::query();

        $parser->apply($request, $query);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('in', mb_strtolower($sql));
        $this->assertContains('active', $bindings);
        $this->assertContains('pending', $bindings);
    }

    #[Test]
    #[TestDox('it applies partial filter')]
    public function it_applies_partial_filter(): void
    {
        $parser = new FilterParser([
            'name' => new PartialFilter(),
        ]);

        $request = Request::create('/', 'GET', ['filter' => ['name' => 'wid']]);
        $query = Product::query();

        $parser->apply($request, $query);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('like', mb_strtolower($sql));
        $this->assertContains('%wid%', $bindings);
    }

    #[Test]
    #[TestDox('it ignores unknown filter fields')]
    public function it_ignores_unknown_fields(): void
    {
        $parser = new FilterParser([
            'status' => new ExactFilter(),
        ]);

        $request = Request::create('/', 'GET', ['filter' => ['unknown' => 'value']]);
        $query = Product::query();

        $parser->apply($request, $query);

        $sql = $query->toSql();

        $this->assertStringNotContainsString('unknown', $sql);
    }

    #[Test]
    #[TestDox('it handles missing filter parameter')]
    public function it_handles_missing_filter(): void
    {
        $parser = new FilterParser([
            'status' => new ExactFilter(),
        ]);

        $request = Request::create('/');
        $query = Product::query();

        $parser->apply($request, $query);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Only the soft-delete where clause should be present, no filter bindings
        $this->assertEmpty($bindings);
    }

    #[Test]
    #[TestDox('it returns allowed fields')]
    public function it_returns_allowed_fields(): void
    {
        $parser = new FilterParser([
            'status' => new ExactFilter(),
            'name' => new PartialFilter(),
        ]);

        $this->assertSame(['status', 'name'], $parser->allowedFields());
    }

    #[Test]
    #[TestDox('it resolves filter from class string')]
    public function it_resolves_filter_from_class_string(): void
    {
        $parser = new FilterParser([
            'status' => ExactFilter::class,
        ]);

        $request = Request::create('/', 'GET', ['filter' => ['status' => 'active']]);
        $query = Product::query();

        $parser->apply($request, $query);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('status', $sql);
        $this->assertContains('active', $bindings);
    }

    #[Test]
    #[TestDox('it handles non-array filter parameter')]
    public function it_handles_non_array_filter(): void
    {
        $parser = new FilterParser([
            'status' => new ExactFilter(),
        ]);

        $request = Request::create('/', 'GET', ['filter' => 'not-an-array']);
        $query = Product::query();

        $result = $parser->apply($request, $query);

        $this->assertEmpty($query->getBindings());
    }
}
