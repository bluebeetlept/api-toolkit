<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Middleware;

use BlueBeetle\ApiToolkit\Http\Middleware\ForceJsonApiResponse;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Response;

final class ForceJsonApiResponseTest extends TestCase
{
    #[Test]
    #[TestDox('it sets the content type to application/vnd.api+json')]
    public function it_sets_content_type(): void
    {
        $middleware = $this->app->make(ForceJsonApiResponse::class);

        $request = Request::create('/');

        $response = $middleware->handle(
            request: $request,
            next: fn (Request $request) => new Response('{"data": null}'),
        );

        $this->assertSame('application/vnd.api+json', $response->headers->get('Content-Type'));
        $this->assertSame(200, $response->getStatusCode());
    }
}
