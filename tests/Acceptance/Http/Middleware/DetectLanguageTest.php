<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Acceptance\Http\Middleware;

use Eufaturo\ApiToolkit\Http\Middleware\DetectLanguage;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Response;

final class DetectLanguageTest extends TestCase
{
    #[Test]
    #[TestDox('it sets the locale from the Language header')]
    public function it_sets_locale(): void
    {
        $middleware = $this->app->make(DetectLanguage::class);

        $this->app->make(Repository::class)->set('app.valid_locales', ['en', 'pt']);

        $request = Request::create('/');
        $request->headers->set('Language', 'pt');

        $middleware->handle(
            request: $request,
            next: fn (Request $request) => new Response('ok'),
        );

        $this->assertSame('pt', $this->app->getLocale());
    }

    #[Test]
    #[TestDox('it falls back to fallback locale for invalid language')]
    public function it_falls_back(): void
    {
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

        $this->assertSame('en', $this->app->getLocale());
    }

    #[Test]
    #[TestDox('it does nothing without Language header')]
    public function it_does_nothing_without_header(): void
    {
        $middleware = $this->app->make(DetectLanguage::class);

        $originalLocale = $this->app->getLocale();

        $request = Request::create('/');

        $middleware->handle(
            request: $request,
            next: fn (Request $request) => new Response('ok'),
        );

        $this->assertSame($originalLocale, $this->app->getLocale());
    }
}
