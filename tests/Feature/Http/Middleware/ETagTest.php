<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Http\Middleware;

use BlueBeetle\ApiToolkit\Http\Middleware\ETag;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(ETag::class)->get('/test', fn () => response()->json(['data' => 'hello']));
    Route::middleware(ETag::class)->post('/test', fn () => response()->json(['created' => true], 201));
    Route::middleware(ETag::class)->get('/error', fn () => response()->json(['error' => 'fail'], 500));
    Route::middleware(ETag::class)->get('/empty', fn () => response('', 204));
});

it('adds etag header to GET responses', function () {
    $response = $this->getJson('/test');

    $response->assertOk();
    $response->assertHeader('ETag');

    $etag = $response->headers->get('ETag');
    expect($etag)->toStartWith('"');
    expect($etag)->toEndWith('"');
});

it('returns 304 when If-None-Match matches', function () {
    $first = $this->getJson('/test');
    $etag = $first->headers->get('ETag');

    $second = $this->getJson('/test', ['If-None-Match' => $etag]);

    $second->assertStatus(304);
    expect($second->getContent())->toBe('');
});

it('returns full response when If-None-Match does not match', function () {
    $response = $this->getJson('/test', ['If-None-Match' => '"stale-etag"']);

    $response->assertOk();
    $response->assertHeader('ETag');
    expect($response->json('data'))->toBe('hello');
});

it('returns full response without If-None-Match header', function () {
    $response = $this->getJson('/test');

    $response->assertOk();
    $response->assertHeader('ETag');
    expect($response->json('data'))->toBe('hello');
});

it('does not process non-safe methods', function () {
    $response = $this->postJson('/test');

    $response->assertStatus(201);
    expect($response->headers->has('ETag'))->toBeFalse();
});

it('does not process error responses', function () {
    $response = $this->getJson('/error');

    $response->assertStatus(500);
    expect($response->headers->has('ETag'))->toBeFalse();
});

it('does not process empty responses', function () {
    $response = $this->get('/empty');

    $response->assertStatus(204);
    expect($response->headers->has('ETag'))->toBeFalse();
});

it('generates consistent etags for same content', function () {
    $first = $this->getJson('/test');
    $second = $this->getJson('/test');

    expect($first->headers->get('ETag'))->toBe($second->headers->get('ETag'));
});
