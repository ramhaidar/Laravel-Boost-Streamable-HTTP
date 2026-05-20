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
    | root (or to the prefix/domain set below). Example: "/_boost/mcp" mounts
    | the endpoint at https://your-app.test/_boost/mcp.
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

    /*
    |--------------------------------------------------------------------------
    | Route Group
    |--------------------------------------------------------------------------
    |
    | Optional route group attributes. Set 'domain' to scope the endpoint to
    | a specific subdomain, 'prefix' to mount it under a path prefix, or
    | 'as' to apply a Laravel route name prefix. Leave any value as null to
    | skip that attribute.
    |
    */

    'domain' => env('LARAVEL_BOOST_STREAMABLE_HTTP_DOMAIN'),

    'prefix' => env('LARAVEL_BOOST_STREAMABLE_HTTP_PREFIX'),

    'as' => env('LARAVEL_BOOST_STREAMABLE_HTTP_NAME_PREFIX'),

    /*
    |--------------------------------------------------------------------------
    | Warn When Enabled In Production With No Middleware
    |--------------------------------------------------------------------------
    |
    | When true (default), the package emits a warning to the application
    | log if it is enabled in the 'production' environment without any
    | middleware configured. The endpoint still registers; this is a
    | reminder, not an enforcement. Disable to silence.
    |
    */

    'warn_unprotected_in_production' => env('LARAVEL_BOOST_STREAMABLE_HTTP_WARN_UNPROTECTED', true),

];
