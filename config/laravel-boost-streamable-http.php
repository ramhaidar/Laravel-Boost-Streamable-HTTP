<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | When true, this package registers a Laravel MCP web (Streamable HTTP)
    | endpoint that exposes Laravel Boost. Disabled by default for safety.
    |
    */

    'enabled' => env('LARAVEL_BOOST_STREAMABLE_HTTP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    |
    | The HTTP path the MCP server is mounted on, relative to the application
    | root. Example: "/_boost/mcp" -> https://your-app.test/_boost/mcp
    |
    */

    'path' => env('LARAVEL_BOOST_STREAMABLE_HTTP_PATH', '/_boost/mcp'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the MCP route. Laravel Boost exposes powerful
    | local-development capabilities. Apply authentication, authorization,
    | and rate limiting middleware appropriate for your environment before
    | exposing the endpoint outside localhost.
    |
    | Example: ['auth:sanctum', 'throttle:60,1']
    |
    */

    'middleware' => [],

];
