<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests;

use BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs\FormRequestWithFormInputRules;
use BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs\FormRequestWithNoRules;
use BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs\FormRequestWithQueryAndBodyRules;
use BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs\FormRequestWithQueryParamRules;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

it('validates query params', function () {
    Route::get('/', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));

    $response = $this->get('/?include[]=category');

    $response->assertStatus(Response::HTTP_OK);
});

it('fails on missing required query param', function () {
    Route::get('/', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));

    $response = $this->getJson('/');

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

it('rejects unknown query params', function () {
    Route::get('/', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));

    $response = $this->getJson('/?include[]=category&unknown=value');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});

it('validates form data', function () {
    Route::post('/', fn (FormRequestWithFormInputRules $request) => response()->json(['ok' => true]));

    $response = $this->postJson('/', ['name' => 'John']);

    $response->assertStatus(Response::HTTP_OK);
});

it('fails on missing required form field', function () {
    Route::post('/', fn (FormRequestWithFormInputRules $request) => response()->json(['ok' => true]));

    $response = $this->postJson('/', []);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

it('rejects unknown form fields', function () {
    Route::post('/', fn (FormRequestWithFormInputRules $request) => response()->json(['ok' => true]));

    $response = $this->postJson('/', ['name' => 'John', 'unknown' => 'value']);

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});

it('passes through when no rules are defined', function () {
    Route::get('/', fn (FormRequestWithNoRules $request) => response()->json(['ok' => true]));

    $response = $this->getJson('/');

    $response->assertStatus(Response::HTTP_OK);
});

it('rejects multiple unknown query params with plural message', function () {
    Route::get('/', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));

    $response = $this->getJson('/?include[]=category&foo=bar&baz=qux');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});

it('rejects multiple unknown form fields with plural message', function () {
    Route::post('/', fn (FormRequestWithFormInputRules $request) => response()->json(['ok' => true]));

    $response = $this->postJson('/', ['name' => 'John', 'foo' => 'bar', 'baz' => 'qux']);

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});

it('validates both query params and form data together', function () {
    Route::post('/', fn (FormRequestWithQueryAndBodyRules $request) => response()->json(['ok' => true]));

    $response = $this->postJson('/?include[]=category', ['name' => 'John']);

    $response->assertStatus(Response::HTTP_OK);
});

it('rejects unknown query params even when form data is valid', function () {
    Route::post('/', fn (FormRequestWithQueryAndBodyRules $request) => response()->json(['ok' => true]));

    $response = $this->postJson('/?include[]=category&unknown=value', ['name' => 'John']);

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});

it('rejects unknown form fields even when query params are valid', function () {
    Route::post('/', fn (FormRequestWithQueryAndBodyRules $request) => response()->json(['ok' => true]));

    $response = $this->postJson('/?include[]=category', ['name' => 'John', 'unknown' => 'value']);

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});
