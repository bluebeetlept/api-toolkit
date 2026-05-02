<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\FieldParser;
use Illuminate\Http\Request;

it('parses sparse fieldsets', function () {
    $parser = new FieldParser();

    $request = Request::create('/', 'GET', ['fields' => ['products' => 'name,code']]);

    $result = $parser->parse($request);

    expect($result['products'])->toBe(['name', 'code']);
});

it('parses multiple resource type fieldsets', function () {
    $parser = new FieldParser();

    $request = Request::create('/', 'GET', [
        'fields' => [
            'products' => 'name,code',
            'categories' => 'name',
        ],
    ]);

    $result = $parser->parse($request);

    expect($result['products'])->toBe(['name', 'code']);
    expect($result['categories'])->toBe(['name']);
});

it('returns empty array when no fields param', function () {
    $parser = new FieldParser();

    $request = Request::create('/');

    expect($parser->parse($request))->toBe([]);
});

it('filters attributes to requested fields', function () {
    $parser = new FieldParser();

    $attributes = ['name' => 'Widget', 'code' => 'W01', 'description' => 'A widget'];

    $result = $parser->filter($attributes, ['name', 'code']);

    expect($result)->toBe(['name' => 'Widget', 'code' => 'W01']);
});

it('returns all attributes when fields is null', function () {
    $parser = new FieldParser();

    $attributes = ['name' => 'Widget', 'code' => 'W01'];

    $result = $parser->filter($attributes, null);

    expect($result)->toBe($attributes);
});

it('returns all attributes when fields is empty', function () {
    $parser = new FieldParser();

    $attributes = ['name' => 'Widget', 'code' => 'W01'];

    $result = $parser->filter($attributes, []);

    expect($result)->toBe($attributes);
});

it('returns empty when fields param is not an array', function () {
    $parser = new FieldParser();

    $request = Request::create('/', 'GET', ['fields' => 'not-an-array']);

    expect($parser->parse($request))->toBe([]);
});

it('skips non-string or empty field values', function () {
    $parser = new FieldParser();

    $request = Request::create('/', 'GET', [
        'fields' => [
            'products' => 'name,code',
            'categories' => '',
            'tags' => ['not', 'a', 'string'],
        ],
    ]);

    $result = $parser->parse($request);

    expect($result['products'])->toBe(['name', 'code']);
    expect($result)->not->toHaveKey('categories');
    expect($result)->not->toHaveKey('tags');
});
