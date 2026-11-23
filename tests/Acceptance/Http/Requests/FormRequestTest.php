<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests;

use BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs\FormRequestWithAllowedIncludes;
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

it('lets the query keys the toolkit reads through, whatever the request declares', function () {
    Route::get('/', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));

    $response = $this->getJson('/?include[]=category&page[number]=2&page[size]=10&sort=-name&filter[name]=a&fields[products]=name');

    $response->assertStatus(Response::HTTP_OK);
});

it('refuses a page that is not one, with or without query rules of its own', function (string $query) {
    Route::get('/rules', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));
    Route::get('/none', fn (FormRequestWithNoRules $request) => response()->json(['ok' => true]));

    $this->getJson("/rules?include[]=category&{$query}")->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $this->getJson("/none?{$query}")->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
})->with([
    'a page number below 1' => ['page[number]=0'],
    'a page number that is not a number' => ['page[number]=abc'],
    'a page size that is not offered' => ['page[size]=1000'],
    'a page that is not a set' => ['page=2'],
]);

it('checks include against the relationships the request allows', function (string $query, int $status) {
    Route::get('/', fn (FormRequestWithAllowedIncludes $request) => response()->json(['ok' => true]));

    $this->getJson("/?{$query}")->assertStatus($status);
})->with([
    'no include' => ['', Response::HTTP_OK],
    'allowed includes' => ['include=category,supplier', Response::HTTP_OK],
    'allowed includes as a list' => ['include[]=category&include[]=supplier', Response::HTTP_OK],
    'an include it does not allow' => ['include=category,secret', Response::HTTP_UNPROCESSABLE_ENTITY],
]);

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
