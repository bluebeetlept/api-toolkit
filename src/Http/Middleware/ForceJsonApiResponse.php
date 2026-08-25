<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class ForceJsonApiResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Declare the request as JSON:API so content negotiation and exception
        // rendering treat it as an API request (before the pipeline runs).
        $request->headers->set('Accept', 'application/vnd.api+json');

        $response = $next($request);

        $response->headers->set(
            key: 'Content-Type',
            values: 'application/vnd.api+json',
        );

        return $response;
    }
}
