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

it('names the requested includes that are not allowed', function (array | string $include, array $unknown) {
    $parser = new IncludeParser(allowed: ['category', 'supplier']);

    $request = Request::create('/', 'GET', ['include' => $include]);

    expect($parser->unknown($request))->toBe($unknown);
})->with([
    'all allowed' => ['category, supplier', []],
    'one not allowed' => ['category,secret', ['secret']],
    'as a list' => [['category', 'secret'], ['secret']],
    'nothing requested' => ['', []],
]);

it('names every requested include as unknown when none are allowed', function () {
    $parser = new IncludeParser();

    $request = Request::create('/', 'GET', ['include' => 'category,supplier']);

    expect($parser->unknown($request))->toBe(['category', 'supplier']);
});

it('returns empty for empty include string', function () {
    $parser = new IncludeParser(allowed: ['category']);

    $request = Request::create('/', 'GET', ['include' => '']);

    expect($parser->parse($request))->toBe([]);
});
