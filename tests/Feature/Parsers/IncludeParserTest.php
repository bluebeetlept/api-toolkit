<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Feature\Parsers;

use Eufaturo\ApiToolkit\Parsers\IncludeParser;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class IncludeParserTest extends TestCase
{
    #[Test]
    #[TestDox('it parses comma-separated includes')]
    public function it_parses_includes(): void
    {
        $parser = new IncludeParser(allowed: ['category', 'supplier']);

        $request = Request::create('/', 'GET', ['include' => 'category,supplier']);

        $this->assertSame(['category', 'supplier'], $parser->parse($request));
    }

    #[Test]
    #[TestDox('it filters out disallowed includes')]
    public function it_filters_disallowed_includes(): void
    {
        $parser = new IncludeParser(allowed: ['category']);

        $request = Request::create('/', 'GET', ['include' => 'category,secret']);

        $this->assertSame(['category'], $parser->parse($request));
    }

    #[Test]
    #[TestDox('it returns empty array when no include param')]
    public function it_returns_empty_without_param(): void
    {
        $parser = new IncludeParser(allowed: ['category']);

        $request = Request::create('/');

        $this->assertSame([], $parser->parse($request));
    }

    #[Test]
    #[TestDox('it allows all includes when no allowed list')]
    public function it_allows_all_when_unrestricted(): void
    {
        $parser = new IncludeParser();

        $request = Request::create('/', 'GET', ['include' => 'anything,goes']);

        $this->assertSame(['anything', 'goes'], $parser->parse($request));
    }

    #[Test]
    #[TestDox('it returns empty for empty include string')]
    public function it_returns_empty_for_empty_string(): void
    {
        $parser = new IncludeParser(allowed: ['category']);

        $request = Request::create('/', 'GET', ['include' => '']);

        $this->assertSame([], $parser->parse($request));
    }
}
