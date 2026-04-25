<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

final readonly class DetectLanguage
{
    private const string HEADER_NAME = 'Language';

    public function __construct(
        private Application $app,
        private Config $config,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader(self::HEADER_NAME)) {
            $language = mb_strtolower($request->header(self::HEADER_NAME));

            $validLocales = $this->config->get('app.valid_locales', []);

            $fallback = $this->config->get('app.fallback_locale');

            $locale = in_array($language, $validLocales, true) ? $language : $fallback;

            $request->setLocale($locale);

            $this->app->setLocale($locale);
        }

        return $next($request);
    }
}
