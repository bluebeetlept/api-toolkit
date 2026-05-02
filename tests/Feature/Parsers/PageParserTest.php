<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Parsers;

use BlueBeetle\ApiToolkit\Parsers\PageParser;
use Illuminate\Http\Request;

it('returns default size when no page param', function () {
    $parser = new PageParser(defaultSize: 20);

    $request = Request::create('/');

    expect($parser->getSize($request))->toBe(20);
});

it('parses page size from request', function () {
    $parser = new PageParser();

    $request = Request::create('/', 'GET', ['page' => ['size' => '40']]);

    expect($parser->getSize($request))->toBe(40);
});

it('clamps page size to max', function () {
    $parser = new PageParser(maxSize: 100);

    $request = Request::create('/', 'GET', ['page' => ['size' => '500']]);

    expect($parser->getSize($request))->toBe(100);
});

it('clamps page size to minimum 1', function () {
    $parser = new PageParser();

    $request = Request::create('/', 'GET', ['page' => ['size' => '0']]);

    expect($parser->getSize($request))->toBe(1);
});

it('returns page number', function () {
    $parser = new PageParser();

    $request = Request::create('/', 'GET', ['page' => ['number' => '3']]);

    expect($parser->getNumber($request))->toBe(3);
});

it('defaults to page 1', function () {
    $parser = new PageParser();

    $request = Request::create('/');

    expect($parser->getNumber($request))->toBe(1);
});

it('detects cursor pagination', function () {
    $parser = new PageParser();

    $request = Request::create('/', 'GET', ['page' => ['cursor' => 'eyJpZCI6MTB9']]);

    expect($parser->isCursor($request))->toBeTrue();
    expect($parser->getCursor($request))->toBe('eyJpZCI6MTB9');
});

it('detects non-cursor pagination', function () {
    $parser = new PageParser();

    $request = Request::create('/', 'GET', ['page' => ['number' => '2']]);

    expect($parser->isCursor($request))->toBeFalse();
    expect($parser->getCursor($request))->toBeNull();
});

it('returns null cursor when no page param', function () {
    $parser = new PageParser();

    $request = Request::create('/');

    expect($parser->isCursor($request))->toBeFalse();
    expect($parser->getCursor($request))->toBeNull();
});

it('handles non-array page param for isCursor', function () {
    $parser = new PageParser();

    $request = Request::create('/', 'GET', ['page' => 'not-an-array']);

    expect($parser->isCursor($request))->toBeFalse();
});

it('handles non-array page param for getSize', function () {
    $parser = new PageParser(defaultSize: 25);

    $request = Request::create('/', 'GET', ['page' => 'not-an-array']);

    expect($parser->getSize($request))->toBe(25);
});

it('handles non-array page param for getNumber', function () {
    $parser = new PageParser();

    $request = Request::create('/', 'GET', ['page' => 'not-an-array']);

    expect($parser->getNumber($request))->toBe(1);
});

it('handles non-array page param for getCursor', function () {
    $parser = new PageParser();

    $request = Request::create('/', 'GET', ['page' => 'not-an-array']);

    expect($parser->getCursor($request))->toBeNull();
});
