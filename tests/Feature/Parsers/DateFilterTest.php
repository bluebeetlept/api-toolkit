<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\DateFilter;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class DateFilterTest extends TestCase
{
    #[Test]
    #[TestDox('it filters by exact date')]
    public function it_filters_exact_date(): void
    {
        $filter = new DateFilter();

        $query = Product::query();
        $filter->apply($query, 'created_at', '2025-01-15');

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('created_at', $sql);
        $this->assertContains('2025-01-15', $bindings);
    }

    #[Test]
    #[TestDox('it filters by date range with from')]
    public function it_filters_date_from(): void
    {
        $filter = new DateFilter();

        $query = Product::query();
        $filter->apply($query, 'created_at', ['from' => '2025-01-01']);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('created_at', $sql);
        $this->assertStringContainsString('>=', $sql);
        $this->assertContains('2025-01-01', $bindings);
    }

    #[Test]
    #[TestDox('it filters by date range with from and to')]
    public function it_filters_date_range(): void
    {
        $filter = new DateFilter();

        $query = Product::query();
        $filter->apply($query, 'created_at', ['from' => '2025-01-01', 'to' => '2025-12-31']);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('created_at', $sql);
        $this->assertContains('2025-01-01', $bindings);
        $this->assertContains('2025-12-31', $bindings);
    }
}
