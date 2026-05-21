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

    /*
    |--------------------------------------------------------------------------
    | Auto-Resolve CLI PHP Binary
    |--------------------------------------------------------------------------
    |
    | Laravel Boost runs each MCP tool call in a subprocess that invokes
    | `php artisan boost:execute-tool ...`. Boost defaults the php binary to
    | PHP_BINARY, which under PHP-FPM, Apache mod_php, or php-cgi resolves to
    | the SAPI binary (php-fpm/php-cgi) instead of the CLI binary. The
    | subprocess then emits HTTP headers and skips console-only service
    | registrations, so the Boost console command is never registered and
    | tool calls fail with:
    |
    |     "Process tool execution failed:
    |       ERROR  There are no commands defined in the \"boost\" namespace."
    |
    | When this option is true (default), the package detects a CLI php binary
    | and writes it to `boost.executable_paths.php` for you, but only if you
    | have not already configured `boost.executable_paths.php` yourself.
    |
    */

    'auto_resolve_php_binary' => env('LARAVEL_BOOST_STREAMABLE_HTTP_AUTO_RESOLVE_PHP_BINARY', true),

    /*
    |--------------------------------------------------------------------------
    | PHP Binary Override
    |--------------------------------------------------------------------------
    |
    | Optional explicit path to a CLI php binary. When set, this value is
    | written to `boost.executable_paths.php` (subject to
    | `auto_resolve_php_binary`). Leave null to let the package discover one
    | automatically via Symfony's PhpExecutableFinder.
    |
    */

    'php_binary' => env('LARAVEL_BOOST_STREAMABLE_HTTP_PHP_BINARY'),

];
