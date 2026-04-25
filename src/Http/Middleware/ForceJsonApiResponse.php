<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class ForceJsonApiResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set(
            key: 'Content-Type',
            values: 'application/vnd.api+json',
        );

        return $response;
    }
}
