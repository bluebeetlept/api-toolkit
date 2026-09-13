<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Exceptions;

use BlueBeetle\ApiToolkit\ApiToolkitServiceProvider;
use BlueBeetle\ApiToolkit\Exceptions\ConfigureExceptionHandler;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler;

function bareApplication(): Application
{
    $application = new Application(__DIR__);

    $application->instance('config', new Repository());

    return $application;
}

function registeredApplication(): Application
{
    $application = bareApplication();

    $application->register(ApiToolkitServiceProvider::class);

    return $application;
}

it('binds the exception handler while registering, before anything boots', function () {
    $application = registeredApplication();

    expect($application->isBooted())->toBeFalse()
        ->and($application->bound(ExceptionHandlerContract::class))->toBeTrue()
    ;
});

it('resolves the handler to the framework handler', function () {
    $application = registeredApplication();

    expect($application->make(ExceptionHandlerContract::class))->toBeInstanceOf(Handler::class);
});

it('does not configure a second time when an app still calls it from booted', function () {
    $application = registeredApplication();

    $configured = 0;

    $application->afterResolving(Handler::class, function () use (&$configured): void {
        $configured++;
    });

    (new ConfigureExceptionHandler())($application);

    $application->make(ExceptionHandlerContract::class);

    expect($configured)->toBe(1);
});

it('configures the handler when used on its own', function () {
    $application = bareApplication();

    expect($application->bound(ExceptionHandlerContract::class))->toBeFalse();

    (new ConfigureExceptionHandler())($application);

    expect($application->bound(ExceptionHandlerContract::class))->toBeTrue();
});
