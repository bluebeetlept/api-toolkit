<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\FieldParser;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class FieldParserTest extends TestCase
{
    #[Test]
    #[TestDox('it parses sparse fieldsets')]
    public function it_parses_fields(): void
    {
        $parser = new FieldParser();

        $request = Request::create('/', 'GET', ['fields' => ['products' => 'name,code']]);

        $result = $parser->parse($request);

        $this->assertSame(['name', 'code'], $result['products']);
    }

    #[Test]
    #[TestDox('it parses multiple resource type fieldsets')]
    public function it_parses_multiple_types(): void
    {
        $parser = new FieldParser();

        $request = Request::create('/', 'GET', [
            'fields' => [
                'products' => 'name,code',
                'categories' => 'name',
            ],
        ]);

        $result = $parser->parse($request);

        $this->assertSame(['name', 'code'], $result['products']);
        $this->assertSame(['name'], $result['categories']);
    }

    #[Test]
    #[TestDox('it returns empty array when no fields param')]
    public function it_returns_empty_without_param(): void
    {
        $parser = new FieldParser();

        $request = Request::create('/');

        $this->assertSame([], $parser->parse($request));
    }

    #[Test]
    #[TestDox('it filters attributes to requested fields')]
    public function it_filters_attributes(): void
    {
        $parser = new FieldParser();

        $attributes = ['name' => 'Widget', 'code' => 'W01', 'description' => 'A widget'];

        $result = $parser->filter($attributes, ['name', 'code']);

        $this->assertSame(['name' => 'Widget', 'code' => 'W01'], $result);
    }

    #[Test]
    #[TestDox('it returns all attributes when fields is null')]
    public function it_returns_all_when_null(): void
    {
        $parser = new FieldParser();

        $attributes = ['name' => 'Widget', 'code' => 'W01'];

        $result = $parser->filter($attributes, null);

        $this->assertSame($attributes, $result);
    }

    #[Test]
    #[TestDox('it returns all attributes when fields is empty')]
    public function it_returns_all_when_empty(): void
    {
        $parser = new FieldParser();

        $attributes = ['name' => 'Widget', 'code' => 'W01'];

        $result = $parser->filter($attributes, []);

        $this->assertSame($attributes, $result);
    }

    #[Test]
    #[TestDox('it returns empty when fields param is not an array')]
    public function it_returns_empty_for_non_array_fields(): void
    {
        $parser = new FieldParser();

        $request = Request::create('/', 'GET', ['fields' => 'not-an-array']);

        $this->assertSame([], $parser->parse($request));
    }

    #[Test]
    #[TestDox('it skips non-string or empty field values')]
    public function it_skips_invalid_field_values(): void
    {
        $parser = new FieldParser();

        $request = Request::create('/', 'GET', [
            'fields' => [
                'products' => 'name,code',
                'categories' => '',
                'tags' => ['not', 'a', 'string'],
            ],
        ]);

        $result = $parser->parse($request);

        $this->assertSame(['name', 'code'], $result['products']);
        $this->assertArrayNotHasKey('categories', $result);
        $this->assertArrayNotHasKey('tags', $result);
    }
}
