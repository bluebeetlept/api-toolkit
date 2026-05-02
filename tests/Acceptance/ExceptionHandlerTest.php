<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance;

use BlueBeetle\ApiToolkit\Exceptions\ConfigureExceptionHandler;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Exceptions\StubDomainException;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use BlueBeetle\IdempotencyMiddleware\IdempotencyException;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

final class ExceptionHandlerTest extends TestCase
{
    protected function resolveApplicationExceptionHandler($app): void
    {
        (new ConfigureExceptionHandler())($app);
    }

    #[Test]
    #[TestDox('it renders authentication exception as JSON:API error')]
    public function it_renders_authentication_exception(): void
    {
        Route::get('/test', fn () => throw new AuthenticationException('Unauthenticated'));

        $response = $this->get('/test');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonPath('errors.0.status', '401');
        $response->assertJsonPath('errors.0.code', 'invalid_request_error');
        $response->assertJsonPath('errors.0.title', 'Unauthorized');
    }

    #[Test]
    #[TestDox('it renders validation exception as JSON:API errors')]
    public function it_renders_validation_exception(): void
    {
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
    }

    #[Test]
    #[TestDox('it renders not found for missing routes')]
    public function it_renders_not_found(): void
    {
        $response = $this->get('/nonexistent');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertJsonPath('errors.0.code', 'invalid_request_error');
        $response->assertJsonPath('errors.0.title', 'Not Found');
    }

    #[Test]
    #[TestDox('it renders method not allowed as not found')]
    public function it_renders_method_not_allowed(): void
    {
        Route::get('/test', fn () => 'ok');

        $response = $this->post('/test');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertJsonPath('errors.0.code', 'invalid_request_error');
    }

    #[Test]
    #[TestDox('it renders http exceptions')]
    public function it_renders_http_exceptions(): void
    {
        Route::get('/test', fn () => throw new HttpException(
            statusCode: Response::HTTP_FORBIDDEN,
            message: 'Access denied',
        ));

        $response = $this->get('/test');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $response->assertJsonPath('errors.0.status', '403');
        $response->assertJsonPath('errors.0.detail', 'Access denied');
    }

    #[Test]
    #[TestDox('it renders generic exceptions as 500')]
    public function it_renders_generic_exceptions(): void
    {
        Route::get('/test', fn () => throw new RuntimeException('Something broke'));

        $response = $this->get('/test');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->assertJsonPath('errors.0.code', 'api_error');
    }

    #[Test]
    #[TestDox('it includes debug payload when debug is enabled')]
    public function it_includes_debug_when_enabled(): void
    {
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
    }

    #[Test]
    #[TestDox('it excludes debug payload when debug is disabled')]
    public function it_excludes_debug_when_disabled(): void
    {
        $this->app['config']->set('app.debug', false);

        Route::get('/test', fn () => throw new HttpException(
            statusCode: Response::HTTP_BAD_REQUEST,
            message: 'Bad request',
        ));

        $response = $this->get('/test');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonMissingPath('errors.0.meta');
    }

    #[Test]
    #[TestDox('it renders query exception as 400')]
    public function it_renders_query_exception(): void
    {
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
    }

    #[Test]
    #[TestDox('it renders query exception with detail when debug is on')]
    public function it_renders_query_exception_with_debug(): void
    {
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
        // When debug is on, the actual exception message is shown
        $this->assertNotSame('There was a problem during a database query', $response->json('errors.0.detail'));
    }

    #[Test]
    #[TestDox('it renders lazy loading violation as 400')]
    public function it_renders_lazy_loading_violation(): void
    {
        $this->app['config']->set('app.debug', false);

        Route::get('/test', fn () => throw new LazyLoadingViolationException(
            model: new Product(),
            relation: 'category',
        ));

        $response = $this->get('/test');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'invalid_request_error');
        $response->assertJsonPath('errors.0.detail', 'There was a problem during a database query');
    }

    #[Test]
    #[TestDox('it renders model not found exception')]
    public function it_renders_model_not_found(): void
    {
        $modelException = (new ModelNotFoundException())->setModel(Product::class, ['abc-123']);

        Route::get('/test', fn () => throw new NotFoundHttpException(
            message: '',
            previous: $modelException,
        ));

        $response = $this->get('/test');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertJsonPath('errors.0.code', 'invalid_request_error');
        $response->assertJsonPath('errors.0.title', 'Not Found');
        $this->assertStringContainsString('product', $response->json('errors.0.detail'));
        $this->assertStringContainsString('abc-123', $response->json('errors.0.detail'));
    }

    #[Test]
    #[TestDox('it renders idempotency exception')]
    public function it_renders_idempotency_exception(): void
    {
        Route::post('/test', fn () => throw new IdempotencyException('Request already processed'));

        $response = $this->postJson('/test');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'idempotency_error');
        $response->assertJsonPath('errors.0.title', 'Idempotency Error');
        $response->assertJsonPath('errors.0.detail', 'Request already processed');
    }

    #[Test]
    #[TestDox('it renders route not found exception')]
    public function it_renders_route_not_found(): void
    {
        Route::get('/test', fn () => throw new RouteNotFoundException(
            'Route [api.products.index] not defined.',
        ));

        $response = $this->get('/test');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertJsonPath('errors.0.code', 'invalid_request_error');
        $response->assertJsonPath('errors.0.title', 'Not Found');
        $this->assertStringContainsString('api.products.index', $response->json('errors.0.detail'));
    }

    #[Test]
    #[TestDox('it renders domain exceptions as 400')]
    public function it_renders_domain_exceptions(): void
    {
        $this->app['config']->set('api-toolkit.exceptions.domain', [
            StubDomainException::class,
        ]);

        Route::get('/test', fn () => throw new StubDomainException('Insufficient stock'));

        $response = $this->get('/test');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'invalid_request_error');
        $response->assertJsonPath('errors.0.title', 'Bad Request');
        $response->assertJsonPath('errors.0.detail', 'Insufficient stock');
    }

    #[Test]
    #[TestDox('it respects dont_report config')]
    public function it_respects_dont_report_config(): void
    {
        $this->app['config']->set('api-toolkit.exceptions.dont_report', [
            RuntimeException::class,
        ]);

        Route::get('/test', fn () => throw new RuntimeException('Should not be reported'));

        $response = $this->get('/test');

        // Still renders the response, but the exception should not be reported
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Test]
    #[TestDox('it renders not found for root path')]
    public function it_renders_not_found_for_root_path(): void
    {
        $response = $this->get('/');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $this->assertStringContainsString('GET: /', $response->json('errors.0.detail'));
    }

    #[Test]
    #[TestDox('it sets json api content type on error responses')]
    public function it_sets_content_type(): void
    {
        Route::get('/test', fn () => throw new RuntimeException('Error'));

        $response = $this->get('/test');

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    #[Test]
    #[TestDox('it renders multiple validation errors with source pointers')]
    public function it_renders_multiple_validation_errors(): void
    {
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

        // name required + email invalid + age required = 3 errors
        $this->assertGreaterThanOrEqual(3, count($errors));

        // All errors should have validation_error code
        foreach ($errors as $error) {
            $this->assertSame('validation_error', $error['code']);
            $this->assertSame('Validation Error', $error['title']);
            $this->assertArrayHasKey('pointer', $error['source']);
        }
    }
}
