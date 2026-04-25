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
    | Configuration for the OpenAPI spec generator.
    | Run: php artisan api-toolkit:openapi
    |
    */

    'openapi' => [
        'title' => env('APP_NAME', 'API'),
        'version' => '1.0.0',
        'description' => '',

        'servers' => [
            ['url' => env('APP_URL', 'http://localhost') . '/api'],
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

];
