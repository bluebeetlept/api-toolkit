<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Testing;

use Illuminate\Testing\LoggedExceptionCollection;

trait CreatesTestResponse
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
