<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Exceptions;

use BlueBeetle\IdempotencyMiddleware\IdempotencyException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

final readonly class ExceptionHandler
{
    private const string CONTENT_TYPE = 'application/vnd.api+json';

    public function __construct(
        private ConfigRepository $config,
    ) {
    }

    public function __invoke(Exceptions $exceptions): void
    {
        $dontReport = $this->config->get('api-toolkit.exceptions.dont_report', []);

        if ($dontReport !== []) {
            $exceptions->dontReport($dontReport);
        }

        $exceptions->dontReportDuplicates();

        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($exception instanceof AuthenticationException) {
                return $this->buildErrorResponse(
                    status: Response::HTTP_UNAUTHORIZED,
                    title: 'Unauthorized',
                    detail: $exception->getMessage(),
                    code: 'invalid_request_error',
                    exception: $exception,
                );
            }

            if ($exception instanceof ValidationException) {
                return $this->handleValidationException($exception);
            }

            if ($exception instanceof QueryException || $exception instanceof LazyLoadingViolationException) {
                $detail = $this->isDebugOn() ? $exception->getMessage() : 'There was a problem during a database query';

                return $this->buildErrorResponse(
                    status: Response::HTTP_BAD_REQUEST,
                    title: 'Bad Request',
                    detail: $detail,
                    code: 'invalid_request_error',
                    exception: $exception,
                );
            }

            if ($exception instanceof RouteNotFoundException) {
                $routeName = str_replace(['Route [', '] not defined.'], '', $exception->getMessage());

                return $this->buildErrorResponse(
                    status: Response::HTTP_NOT_FOUND,
                    title: 'Not Found',
                    detail: sprintf('The route named (%s) does not seem to be defined.', $routeName),
                    code: 'invalid_request_error',
                    exception: $exception,
                );
            }

            if ($exception instanceof NotFoundHttpException && $exception->getPrevious() instanceof ModelNotFoundException) {
                /** @var ModelNotFoundException $previous */
                $previous = $exception->getPrevious();

                $modelBaseName = class_basename($previous->getModel());
                $lowerCasedModel = Str::snake($modelBaseName, ' ');
                $ids = $previous->getIds();
                $values = end($ids);

                return $this->buildErrorResponse(
                    status: Response::HTTP_NOT_FOUND,
                    title: 'Not Found',
                    detail: sprintf("No such %s: '%s'", $lowerCasedModel, $values),
                    code: 'invalid_request_error',
                    exception: $exception,
                );
            }

            if ($exception instanceof MethodNotAllowedHttpException || $exception instanceof NotFoundHttpException) {
                $path = $request->path();

                if ($path === '/') {
                    $path = '';
                }

                return $this->buildErrorResponse(
                    status: Response::HTTP_NOT_FOUND,
                    title: 'Not Found',
                    detail: sprintf('Unrecognized request URL (%s: /%s).', $request->method(), $path),
                    code: 'invalid_request_error',
                    exception: $exception,
                );
            }

            if ($exception instanceof IdempotencyException) {
                return $this->buildErrorResponse(
                    status: $exception->getStatusCode(),
                    title: 'Idempotency Error',
                    detail: $exception->getMessage(),
                    code: 'idempotency_error',
                    exception: $exception,
                    headers: $exception->getHeaders(),
                );
            }

            if ($exception instanceof HttpExceptionInterface) {
                return $this->buildErrorResponse(
                    status: $exception->getStatusCode(),
                    title: Response::$statusTexts[$exception->getStatusCode()] ?? 'Error',
                    detail: $exception->getMessage(),
                    code: 'invalid_request_error',
                    exception: $exception,
                    headers: $exception->getHeaders(),
                );
            }

            if ($this->isDomainException($exception)) {
                return $this->buildErrorResponse(
                    status: Response::HTTP_BAD_REQUEST,
                    title: 'Bad Request',
                    detail: $exception->getMessage(),
                    code: 'invalid_request_error',
                    exception: $exception,
                );
            }

            return $this->buildErrorResponse(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                title: 'Internal Server Error',
                detail: $exception->getMessage(),
                code: 'api_error',
                exception: $exception,
            );
        });
    }

    private function buildErrorResponse(
        int $status,
        string $title,
        string $detail,
        string $code,
        Throwable $exception,
        array $headers = [],
    ): JsonResponse {
        $error = [
            'status' => (string) $status,
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
        ];

        if ($this->isDebugOn()) {
            $error['meta'] = ['debug' => $this->buildDebugPayload($exception)];
        }

        return new JsonResponse(
            data: ['errors' => [$error]],
            status: $status,
            headers: array_merge(['Content-Type' => self::CONTENT_TYPE], $headers),
        );
    }

    private function handleValidationException(ValidationException $exception): JsonResponse
    {
        $errors = Collection::make($exception->validator->errors()->toArray())
            ->flatMap(function (array $messages, string $field): array {
                return array_map(fn (string $message): array => [
                    'status' => (string) Response::HTTP_UNPROCESSABLE_ENTITY,
                    'code' => 'validation_error',
                    'title' => 'Validation Error',
                    'detail' => $message,
                    'source' => ['pointer' => '/'.str_replace('.', '/', $field)],
                ], $messages);
            })
            ->values()
            ->all()
        ;

        return new JsonResponse(
            data: ['errors' => $errors],
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            headers: ['Content-Type' => self::CONTENT_TYPE],
        );
    }

    private function buildDebugPayload(Throwable $throwable): array
    {
        $trace = Collection::make($throwable->getTrace())
            ->map(fn ($trace) => Arr::except($trace, ['args']))
            ->all()
        ;

        $previous = $throwable->getPrevious();

        return [
            'line' => ($previous ?? $throwable)->getLine(),
            'file' => ($previous ?? $throwable)->getFile(),
            'class' => ($previous ?? $throwable)::class,
            'trace' => $trace,
        ];
    }

    private function isDomainException(Throwable $exception): bool
    {
        $domainExceptions = $this->config->get('api-toolkit.exceptions.domain', []);

        foreach ($domainExceptions as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return true;
            }
        }

        return false;
    }

    private function isDebugOn(): bool
    {
        return $this->config->get('app.debug') === true;
    }
}
