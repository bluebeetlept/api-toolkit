<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance;

use BlueBeetle\ApiToolkit\Exceptions\ConfigureExceptionHandler;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Exceptions\StubDomainException;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\IdempotencyMiddleware\IdempotencyException;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/** @var \BlueBeetle\ApiToolkit\Tests\TestCase $this */
beforeEach(function () {
    (new ConfigureExceptionHandler())($this->app);
});

it('renders authentication exception as JSON:API error', function () {
    Route::get('/test', fn () => throw new AuthenticationException('Unauthenticated'));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    $response->assertJsonPath('errors.0.status', '401');
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    $response->assertJsonPath('errors.0.title', 'Unauthorized');
});

it('renders validation exception as JSON:API errors', function () {
    Route::post('/test', function () {
        $validator = \Illuminate\Support\Facades\Validator::make([], [
            'name' => ['required'],
            'email' => ['required', 'email'],
        ]);

        throw new ValidationException($validator);
    });

    $response = $this->postJson('/test');

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.code', 'validation_error');
    $response->assertJsonPath('errors.0.source.pointer', '/name');
    $response->assertJsonPath('errors.1.source.pointer', '/email');
});

it('renders not found for missing routes', function () {
    $response = $this->get('/nonexistent');

    $response->assertStatus(Response::HTTP_NOT_FOUND);
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    $response->assertJsonPath('errors.0.title', 'Not Found');
});

it('renders method not allowed as not found', function () {
    Route::get('/test', fn () => 'ok');

    $response = $this->post('/test');

    $response->assertStatus(Response::HTTP_NOT_FOUND);
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
});

it('renders http exceptions', function () {
    Route::get('/test', fn () => throw new HttpException(
        statusCode: Response::HTTP_FORBIDDEN,
        message: 'Access denied',
    ));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_FORBIDDEN);
    $response->assertJsonPath('errors.0.status', '403');
    $response->assertJsonPath('errors.0.detail', 'Access denied');
});

it('renders generic exceptions as 500', function () {
    Route::get('/test', fn () => throw new RuntimeException('Something broke'));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    $response->assertJsonPath('errors.0.code', 'api_error');
});

it('includes debug payload when debug is enabled', function () {
    $this->app['config']->set('app.debug', true);

    Route::get('/test', fn () => throw new HttpException(
        statusCode: Response::HTTP_BAD_REQUEST,
        message: 'Bad request',
    ));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonStructure([
        'errors' => [
            ['status', 'code', 'title', 'detail', 'meta' => ['debug' => ['line', 'file', 'class', 'trace']]],
        ],
    ]);
});

it('excludes debug payload when debug is disabled', function () {
    $this->app['config']->set('app.debug', false);

    Route::get('/test', fn () => throw new HttpException(
        statusCode: Response::HTTP_BAD_REQUEST,
        message: 'Bad request',
    ));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonMissingPath('errors.0.meta');
});

it('renders query exception as 400', function () {
    $this->app['config']->set('app.debug', false);

    Route::get('/test', fn () => throw new QueryException(
        connectionName: 'testing',
        sql: 'SELECT * FROM invalid_table',
        bindings: [],
        previous: new Exception('Table not found'),
    ));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    $response->assertJsonPath('errors.0.detail', 'There was a problem during a database query');
});

it('renders query exception with detail when debug is on', function () {
    $this->app['config']->set('app.debug', true);

    Route::get('/test', fn () => throw new QueryException(
        connectionName: 'testing',
        sql: 'SELECT * FROM invalid_table',
        bindings: [],
        previous: new Exception('Table not found'),
    ));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    expect($response->json('errors.0.detail'))->not->toBe('There was a problem during a database query');
});

it('renders lazy loading violation as 400', function () {
    $this->app['config']->set('app.debug', false);

    Route::get('/test', fn () => throw new LazyLoadingViolationException(
        model: new Product(),
        relation: 'category',
    ));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    $response->assertJsonPath('errors.0.detail', 'There was a problem during a database query');
});

it('renders model not found exception', function () {
    $modelException = (new ModelNotFoundException())->setModel(Product::class, ['abc-123']);

    Route::get('/test', fn () => throw new NotFoundHttpException(
        message: '',
        previous: $modelException,
    ));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_NOT_FOUND);
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    $response->assertJsonPath('errors.0.title', 'Not Found');
    expect($response->json('errors.0.detail'))->toContain('product');
    expect($response->json('errors.0.detail'))->toContain('abc-123');
});

it('renders idempotency exception', function () {
    Route::post('/test', fn () => throw new IdempotencyException('Request already processed'));

    $response = $this->postJson('/test');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'idempotency_error');
    $response->assertJsonPath('errors.0.title', 'Idempotency Error');
    $response->assertJsonPath('errors.0.detail', 'Request already processed');
});

it('renders route not found exception', function () {
    Route::get('/test', fn () => throw new RouteNotFoundException(
        'Route [api.products.index] not defined.',
    ));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_NOT_FOUND);
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    $response->assertJsonPath('errors.0.title', 'Not Found');
    expect($response->json('errors.0.detail'))->toContain('api.products.index');
});

it('renders domain exceptions as 400', function () {
    $this->app['config']->set('api-toolkit.exceptions.domain', [
        StubDomainException::class,
    ]);

    Route::get('/test', fn () => throw new StubDomainException('Insufficient stock'));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    $response->assertJsonPath('errors.0.title', 'Bad Request');
    $response->assertJsonPath('errors.0.detail', 'Insufficient stock');
});

it('respects dont_report config', function () {
    $this->app['config']->set('api-toolkit.exceptions.dont_report', [
        RuntimeException::class,
    ]);

    Route::get('/test', fn () => throw new RuntimeException('Should not be reported'));

    $response = $this->get('/test');

    $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
});

it('renders not found for root path', function () {
    $response = $this->get('/');

    $response->assertStatus(Response::HTTP_NOT_FOUND);
    expect($response->json('errors.0.detail'))->toContain('GET: /');
});

it('sets json api content type on error responses', function () {
    Route::get('/test', fn () => throw new RuntimeException('Error'));

    $response = $this->get('/test');

    $response->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('renders multiple validation errors with source pointers', function () {
    Route::post('/test', function () {
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['email' => 'not-an-email'],
            [
                'name' => ['required'],
                'email' => ['required', 'email'],
                'age' => ['required', 'integer'],
            ],
        );

        throw new ValidationException($validator);
    });

    $response = $this->postJson('/test');

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    $errors = $response->json('errors');

    expect(count($errors))->toBeGreaterThanOrEqual(3);

    foreach ($errors as $error) {
        expect($error['code'])->toBe('validation_error');
        expect($error['title'])->toBe('Validation Error');
        expect($error['source'])->toHaveKey('pointer');
    }
});
