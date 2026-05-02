<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Middleware;

use BlueBeetle\ApiToolkit\Http\Middleware\ForceJsonApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('sets the content type to application/vnd.api+json', function () {
    $middleware = $this->app->make(ForceJsonApiResponse::class);

    $request = Request::create('/');

    $response = $middleware->handle(
        request: $request,
        next: fn (Request $request) => new Response('{"data": null}'),
    );

    expect($response->headers->get('Content-Type'))->toBe('application/vnd.api+json');
    expect($response->getStatusCode())->toBe(200);
});
