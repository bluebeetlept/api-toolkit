<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Testing;

use Illuminate\Testing\LoggedExceptionCollection;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    protected function createTestResponse($response, $request): TestResponse
    {
        return tap(TestResponse::fromBaseResponse($response, $request), function ($response) {
            $response->withExceptions(
                $this->app->bound(LoggedExceptionCollection::class)
                    ? $this->app->make(LoggedExceptionCollection::class)
                    : new LoggedExceptionCollection(),
            );
        });
    }
}
