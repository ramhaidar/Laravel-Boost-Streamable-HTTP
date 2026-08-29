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
    | Warn When Enabled In A Protected Environment With No Middleware
    |--------------------------------------------------------------------------
    |
    | When true (default), the package emits a warning to the application
    | log when the endpoint is enabled in any configured protected
    | environment (see `protected_environments` below) without any middleware
    | AND `allow_unprotected_in_production` is set to true (the fail-closed
    | default refuses to register instead). The warning is a reminder, not an
    | enforcement. Disable to silence.
    |
    */

    'warn_unprotected_in_production' => env('LARAVEL_BOOST_STREAMABLE_HTTP_WARN_UNPROTECTED', true),

    /*
    |--------------------------------------------------------------------------
    | Allow Unprotected Endpoint In Protected Environments
    |--------------------------------------------------------------------------
    |
    | When false (default), the package refuses to register the endpoint if it
    | is enabled in any configured protected environment (see
    | `protected_environments` below, default `['production']`) without any
    | middleware configured. This is a fail-closed safety default: Laravel
    | Boost exposes powerful capabilities, so accidental exposure in a
    | protected environment should fail loudly instead of silently
    | registering an unauthenticated endpoint.
    |
    | Set this to true only if you explicitly understand the risk and want to
    | register the endpoint anyway (for example, when protection is handled
    | outside the middleware list, such as a VPN or a reverse proxy allowlist).
    | The key name retains "in_production" for backward compatibility, but it
    | applies to every environment listed in `protected_environments`.
    |
    */

    'allow_unprotected_in_production' => env('LARAVEL_BOOST_STREAMABLE_HTTP_ALLOW_UNPROTECTED_IN_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Protected Environments
    |--------------------------------------------------------------------------
    |
    | Environments in which the endpoint must not register without middleware
    | (unless `allow_unprotected_in_production` is set to true). The default is
    | `['production']`. Add environments such as `staging` or `prod` to extend
    | the fail-closed guard to them.
    |
    | Note: this is a missing-middleware guard, not an authentication
    | guarantee. The package cannot determine whether arbitrary middleware
    | authenticates the caller, so it only refuses the empty-middleware case.
    |
    */

    'protected_environments' => ['production'],

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
    | automatically by scanning the system PATH.
    |
    */

    'php_binary' => env('LARAVEL_BOOST_STREAMABLE_HTTP_PHP_BINARY'),

];
