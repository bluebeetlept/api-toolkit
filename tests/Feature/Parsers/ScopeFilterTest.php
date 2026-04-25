<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\Filters\ScopeFilter;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class ScopeFilterTest extends TestCase
{
    #[Test]
    #[TestDox('it delegates to eloquent scope using field name')]
    public function it_delegates_to_scope(): void
    {
        $filter = new ScopeFilter();

        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $query->expects($this->once())
            ->method('__call')
            ->with('active', ['yes'])
        ;

        $filter->apply($query, 'active', 'yes');
    }

    #[Test]
    #[TestDox('it delegates to custom scope name')]
    public function it_delegates_to_custom_scope(): void
    {
        $filter = new ScopeFilter(scopeName: 'whereActive');

        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $query->expects($this->once())
            ->method('__call')
            ->with('whereActive', ['yes'])
        ;

        $filter->apply($query, 'status', 'yes');
    }
}
