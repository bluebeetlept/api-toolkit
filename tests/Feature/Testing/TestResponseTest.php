<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Testing;

use BlueBeetle\ApiToolkit\Testing\TestDataResponse;
use BlueBeetle\ApiToolkit\Testing\TestResponse;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\AssertionFailedError;

function makeTestResponse(array $data, int $status = 200): TestResponse
{
    $jsonResponse = new JsonResponse($data, $status);

    return TestResponse::fromBaseResponse($jsonResponse);
}

it('returns errors as TestDataResponse', function () {
    $response = makeTestResponse([
        'errors' => [
            ['status' => '400', 'title' => 'Bad Request', 'detail' => 'Something went wrong'],
        ],
    ]);

    $errors = $response->errors();

    expect($errors)->toBeInstanceOf(TestDataResponse::class);
    $errors->item(0)->assertSame('title', 'Bad Request');
});

it('returns data as TestDataResponse', function () {
    $response = makeTestResponse([
        'data' => ['type' => 'products', 'id' => '1', 'attributes' => ['name' => 'Widget']],
    ]);

    $data = $response->data();

    expect($data)->toBeInstanceOf(TestDataResponse::class);
    $data->assertSame('type', 'products');
});

it('returns attributes as shortcut', function () {
    $response = makeTestResponse([
        'data' => ['type' => 'products', 'id' => '1', 'attributes' => ['name' => 'Widget']],
    ]);

    $response->attributes()->assertSame('name', 'Widget');
});

it('returns included as TestDataResponse', function () {
    $response = makeTestResponse([
        'data' => [],
        'included' => [
            ['type' => 'categories', 'id' => '1', 'attributes' => ['name' => 'Electronics']],
        ],
    ]);

    $response->included()->assertCount(1);
    $response->included()->item(0)->assertSame('type', 'categories');
});

it('returns empty TestDataResponse when included is missing', function () {
    $response = makeTestResponse(['data' => []]);

    $response->included()->assertEmpty();
});

it('returns meta as TestDataResponse', function () {
    $response = makeTestResponse([
        'data' => [],
        'meta' => ['page' => ['currentPage' => 1, 'total' => 50]],
    ]);

    $response->meta()->assertHasKey('page');
});

it('asserts error title', function () {
    $response = makeTestResponse([
        'errors' => [['status' => '400', 'title' => 'Bad Request']],
    ]);

    $response->assertErrorTitle('Bad Request');
});

it('asserts error detail', function () {
    $response = makeTestResponse([
        'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'Missing field: name']],
    ]);

    $response->assertErrorDetail('Missing field: name');
});

it('asserts error detail contains substring', function () {
    $response = makeTestResponse([
        'errors' => [['status' => '400', 'detail' => 'Missing required field: name']],
    ]);

    $response->assertErrorDetailContains('required field');
});

it('fails assertErrorDetailContains when substring not found', function () {
    $response = makeTestResponse([
        'errors' => [['detail' => 'Something else']],
    ]);

    $response->assertErrorDetailContains('not present');
})->throws(AssertionFailedError::class);

it('asserts error code', function () {
    $response = makeTestResponse([
        'errors' => [['code' => 'validation_error']],
    ]);

    $response->assertErrorCode('validation_error');
});

it('asserts validation error with field and message', function () {
    $response = makeTestResponse([
        'errors' => [
            ['detail' => 'The name field is required.', 'source' => ['pointer' => '/name']],
            ['detail' => 'The email must be valid.', 'source' => ['pointer' => '/email']],
        ],
    ]);

    $response->assertValidationError('name', 'The name field is required.');
    $response->assertValidationError('email', 'The email must be valid.');
});

it('asserts validation error with nested field pointer', function () {
    $response = makeTestResponse([
        'errors' => [
            ['detail' => 'Invalid value.', 'source' => ['pointer' => '/data/attributes/name']],
        ],
    ]);

    $response->assertValidationError('data.attributes.name', 'Invalid value.');
});

it('fails assertValidationError when field not found', function () {
    $response = makeTestResponse([
        'errors' => [
            ['detail' => 'Required.', 'source' => ['pointer' => '/name']],
        ],
    ]);

    $response->assertValidationError('email', 'Required.');
})->throws(AssertionFailedError::class);

it('asserts resource type', function () {
    $response = makeTestResponse([
        'data' => ['type' => 'products', 'id' => '1'],
    ]);

    $response->assertResourceType('products');
});

it('asserts resource id', function () {
    $response = makeTestResponse([
        'data' => ['type' => 'products', 'id' => 'abc-123'],
    ]);

    $response->assertResourceId('abc-123');
});

it('returns self from assertion methods for chaining', function () {
    $response = makeTestResponse([
        'errors' => [
            ['status' => '422', 'code' => 'validation_error', 'title' => 'Validation Error', 'detail' => 'Name is required.'],
        ],
    ]);

    $result = $response
        ->assertErrorTitle('Validation Error')
        ->assertErrorDetail('Name is required.')
        ->assertErrorCode('validation_error')
    ;

    expect($result)->toBeInstanceOf(TestResponse::class);
});
