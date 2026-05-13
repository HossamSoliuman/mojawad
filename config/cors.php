<?php

// config/cors.php — Add this to your existing cors config or replace it.
// Laravel 11+ ships with fruitcake/laravel-cors built in.

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true, // required for Sanctum cookie auth
];
