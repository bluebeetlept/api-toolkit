<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Exceptions;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;

final class ConfigureExceptionHandler
{
    public function __invoke(Application $application): void
    {
        $application->singleton(
            abstract: ExceptionHandlerContract::class,
            concrete: Handler::class,
        );

        $application->afterResolving(
            abstract: Handler::class,
            callback: fn (Handler $handler) => $application->make(ExceptionHandler::class)->__invoke(new Exceptions($handler)),
        );
    }
}
