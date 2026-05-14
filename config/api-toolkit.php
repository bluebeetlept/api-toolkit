<?php

declare(strict_types = 1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for JSON:API responses.
    |
    */

    'pagination' => [
        'default_size' => 20,
        'max_size' => 100,
        'valid_sizes' => [10, 20, 40, 80, 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    |
    | Configure which exceptions should not be reported and which domain
    | exceptions should be caught and rendered as 400 Bad Request.
    |
    */

    'exceptions' => [
        'dont_report' => [
            // Add exception classes that should not be reported to your error tracker.
            // e.g. \App\Exceptions\DomainException::class,
        ],

        'domain' => [
            // Add your domain exception classes here. These will be rendered
            // as 400 Bad Request with the exception message as detail.
            // e.g. \App\Exceptions\DomainException::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAPI
    |--------------------------------------------------------------------------
    |
    | Configuration for the OpenAPI 3.1 spec generator.
    | https://www.openapis.org/
    |
    | Run: php artisan api-toolkit:openapi
    |
    */

    'openapi' => [
        'title' => env('APP_NAME', 'API'),
        'version' => '1.0.0',
        'description' => '',

        'output' => [
            'path' => public_path('openapi.json'),
            'pretty' => false,
        ],

        'servers' => [
            ['url' => env('APP_URL', 'http://localhost')],
        ],

        // 'security_schemes' => [
        //     'bearerAuth' => [
        //         'type' => 'http',
        //         'scheme' => 'bearer',
        //     ],
        // ],

        // 'security' => [
        //     ['bearerAuth' => []],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bruno
    |--------------------------------------------------------------------------
    |
    | Configuration for the Bruno API client collection generator.
    | https://www.usebruno.com/
    |
    | Run: php artisan api-toolkit:bruno
    |
    */

    'bruno' => [
        'name' => env('APP_NAME', 'API'),
        'output' => base_path('bruno'),
        'base_url' => '{{host}}',

        // Define multiple collections for versioned APIs.
        // When set, the generator creates a separate Bruno collection per entry.
        'collections' => [
            // 'v1' => [
            //     'name' => 'API v1',
            //     'prefix' => 'v1',
            //     'output' => app_path('V1/Bruno'),
            // ],
        ],
    ],

];
