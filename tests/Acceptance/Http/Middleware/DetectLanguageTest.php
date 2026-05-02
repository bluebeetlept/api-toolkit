<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Middleware;

use BlueBeetle\ApiToolkit\Http\Middleware\DetectLanguage;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('sets the locale from the Language header', function () {
    $middleware = $this->app->make(DetectLanguage::class);

    $this->app->make(Repository::class)->set('app.valid_locales', ['en', 'pt']);

    $request = Request::create('/');
    $request->headers->set('Language', 'pt');

    $middleware->handle(
        request: $request,
        next: fn (Request $request) => new Response('ok'),
    );

    expect($this->app->getLocale())->toBe('pt');
});

it('falls back to fallback locale for invalid language', function () {
    $middleware = $this->app->make(DetectLanguage::class);

    $config = $this->app->make(Repository::class);
    $config->set('app.valid_locales', ['en', 'pt']);
    $config->set('app.fallback_locale', 'en');

    $request = Request::create('/');
    $request->headers->set('Language', 'invalid');

    $middleware->handle(
        request: $request,
        next: fn (Request $request) => new Response('ok'),
    );

    expect($this->app->getLocale())->toBe('en');
});

it('does nothing without Language header', function () {
    $middleware = $this->app->make(DetectLanguage::class);

    $originalLocale = $this->app->getLocale();

    $request = Request::create('/');

    $middleware->handle(
        request: $request,
        next: fn (Request $request) => new Response('ok'),
    );

    expect($this->app->getLocale())->toBe($originalLocale);
});
