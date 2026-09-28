<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Installing the package must not change how a host application renders.
 * These tests never call ConfigureExceptionHandler, so the JSON:API rules are
 * off and the application answers the way Laravel always has.
 *
 * @var \BlueBeetle\ApiToolkit\Tests\TestCase $this
 */
it('lets a web form validation failure redirect with its errors', function () {
    Route::post('/web-form', function () {
        throw new ValidationException(Validator::make([], ['name' => ['required']]));
    });

    $response = $this->post('/web-form');

    $response->assertStatus(Response::HTTP_FOUND);
    $response->assertSessionHasErrors('name');
});

it('leaves a JSON validation failure in the shape Laravel gives it', function () {
    Route::post('/json-form', function () {
        throw new ValidationException(Validator::make([], ['name' => ['required']]));
    });

    $response = $this->postJson('/json-form');

    // Keyed by field, not the JSON:API array of error objects.
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['name']);
});
