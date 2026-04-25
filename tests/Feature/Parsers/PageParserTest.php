<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\PageParser;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class PageParserTest extends TestCase
{
    #[Test]
    #[TestDox('it returns default size when no page param')]
    public function it_returns_default_size(): void
    {
        $parser = new PageParser(defaultSize: 20);

        $request = Request::create('/');

        $this->assertSame(20, $parser->getSize($request));
    }

    #[Test]
    #[TestDox('it parses page size from request')]
    public function it_parses_size(): void
    {
        $parser = new PageParser();

        $request = Request::create('/', 'GET', ['page' => ['size' => '40']]);

        $this->assertSame(40, $parser->getSize($request));
    }

    #[Test]
    #[TestDox('it clamps page size to max')]
    public function it_clamps_to_max(): void
    {
        $parser = new PageParser(maxSize: 100);

        $request = Request::create('/', 'GET', ['page' => ['size' => '500']]);

        $this->assertSame(100, $parser->getSize($request));
    }

    #[Test]
    #[TestDox('it clamps page size to minimum 1')]
    public function it_clamps_to_min(): void
    {
        $parser = new PageParser();

        $request = Request::create('/', 'GET', ['page' => ['size' => '0']]);

        $this->assertSame(1, $parser->getSize($request));
    }

    #[Test]
    #[TestDox('it returns page number')]
    public function it_returns_page_number(): void
    {
        $parser = new PageParser();

        $request = Request::create('/', 'GET', ['page' => ['number' => '3']]);

        $this->assertSame(3, $parser->getNumber($request));
    }

    #[Test]
    #[TestDox('it defaults to page 1')]
    public function it_defaults_to_page_1(): void
    {
        $parser = new PageParser();

        $request = Request::create('/');

        $this->assertSame(1, $parser->getNumber($request));
    }

    #[Test]
    #[TestDox('it detects cursor pagination')]
    public function it_detects_cursor(): void
    {
        $parser = new PageParser();

        $request = Request::create('/', 'GET', ['page' => ['cursor' => 'eyJpZCI6MTB9']]);

        $this->assertTrue($parser->isCursor($request));
        $this->assertSame('eyJpZCI6MTB9', $parser->getCursor($request));
    }

    #[Test]
    #[TestDox('it detects non-cursor pagination')]
    public function it_detects_non_cursor(): void
    {
        $parser = new PageParser();

        $request = Request::create('/', 'GET', ['page' => ['number' => '2']]);

        $this->assertFalse($parser->isCursor($request));
        $this->assertNull($parser->getCursor($request));
    }

    #[Test]
    #[TestDox('it returns null cursor when no page param')]
    public function it_returns_null_cursor_without_param(): void
    {
        $parser = new PageParser();

        $request = Request::create('/');

        $this->assertFalse($parser->isCursor($request));
        $this->assertNull($parser->getCursor($request));
    }
}
