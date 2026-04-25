<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Acceptance;

use Eufaturo\ApiToolkit\Exceptions\ConfigureExceptionHandler;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
}
