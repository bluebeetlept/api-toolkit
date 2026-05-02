<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\IncludeParser;
use Illuminate\Http\Request;

it('parses comma-separated includes', function () {
    $parser = new IncludeParser(allowed: ['category', 'supplier']);

    $request = Request::create('/', 'GET', ['include' => 'category,supplier']);

    expect($parser->parse($request))->toBe(['category', 'supplier']);
});

it('filters out disallowed includes', function () {
    $parser = new IncludeParser(allowed: ['category']);

    $request = Request::create('/', 'GET', ['include' => 'category,secret']);

    expect($parser->parse($request))->toBe(['category']);
});

it('returns empty array when no include param', function () {
    $parser = new IncludeParser(allowed: ['category']);

    $request = Request::create('/');

    expect($parser->parse($request))->toBe([]);
});

it('allows all includes when no allowed list', function () {
    $parser = new IncludeParser();

    $request = Request::create('/', 'GET', ['include' => 'anything,goes']);

    expect($parser->parse($request))->toBe(['anything', 'goes']);
});

it('returns empty for empty include string', function () {
    $parser = new IncludeParser(allowed: ['category']);

    $request = Request::create('/', 'GET', ['include' => '']);

    expect($parser->parse($request))->toBe([]);
});
