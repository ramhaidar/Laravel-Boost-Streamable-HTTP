# Laravel Boost Streamable HTTP

[![tests](https://github.com/ramhaidar/laravel-boost-streamable-http/actions/workflows/tests.yml/badge.svg)](https://github.com/ramhaidar/laravel-boost-streamable-http/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> **Disclaimer:** This is an unofficial community package. It is not affiliated with Laravel, Laravel Boost, or Laravel LLC.

Unofficial opt-in Streamable HTTP / web MCP transport for [Laravel Boost](https://github.com/laravel/boost).

## Why this exists

Laravel Boost's default stdio MCP transport can be unreliable in some MCP clients that stop, idle out, or lose long-running stdio processes. This package lets you expose Boost through Laravel MCP's web (Streamable HTTP) transport instead, by registering Boost's existing MCP server class against `Mcp::web(...)`.

It does not modify or fork Laravel Boost. It is a thin opt-in service provider.

## Requirements

- PHP `^8.2` (PHP `^8.3` required for Laravel 13)
- Laravel 11, 12, or 13
- [`laravel/boost`](https://github.com/laravel/boost) `^2.0`
- [`laravel/mcp`](https://github.com/laravel/mcp) `^0.7.0`

### Compatibility matrix

| This package | Laravel          | PHP                          | laravel/boost  | laravel/mcp |
|--------------|------------------|------------------------------|----------------|-------------|
| `0.x`        | 11.x, 12.x, 13.x | 8.2, 8.3, 8.4 (8.3+ for L13) | 2.x            | 0.7.x       |

`laravel/mcp` 0.7.x registers GET, POST, and DELETE routes on the configured path. Only POST handles MCP traffic; GET and DELETE return `405 Method Not Allowed` with `Allow: POST` per the current upstream implementation. The package wraps all three verbs in your configured middleware so the endpoint cannot be probed without authorization.

## Installation

```bash
composer require --dev ramhaidar/laravel-boost-streamable-http
```

This is a developer-tool transport, so install it as a dev dependency unless you intentionally want it available outside local/dev installs.

The service provider is auto-registered through Laravel package discovery.

## Publish config

```bash
php artisan vendor:publish --tag=laravel-boost-streamable-http-config
```

This publishes `config/laravel-boost-streamable-http.php`.

## Enable

In your `.env`:

```env
LARAVEL_BOOST_STREAMABLE_HTTP_ENABLED=true
LARAVEL_BOOST_STREAMABLE_HTTP_PATH=/_boost/mcp
```

The HTTP endpoint is **disabled by default**. It only registers when `LARAVEL_BOOST_STREAMABLE_HTTP_ENABLED=true`.

## Serve the Laravel app

The endpoint is just a normal Laravel route. Serve your app the way you normally would:

- Laravel Herd
- Laravel Valet
- Laravel Sail
- nginx + PHP-FPM
- Apache
- `php artisan serve`

## MCP client URL

Once enabled and your app is running, the MCP endpoint is available at:

```
https://your-app.test/_boost/mcp
```

## Configuration

The published config file:

```php
return [
    'enabled' => env('LARAVEL_BOOST_STREAMABLE_HTTP_ENABLED', false),
    'path' => env('LARAVEL_BOOST_STREAMABLE_HTTP_PATH', '/_boost/mcp'),
    'middleware' => [],
    'domain' => env('LARAVEL_BOOST_STREAMABLE_HTTP_DOMAIN'),
    'prefix' => env('LARAVEL_BOOST_STREAMABLE_HTTP_PREFIX'),
    'as' => env('LARAVEL_BOOST_STREAMABLE_HTTP_NAME_PREFIX'),
    'warn_unprotected_in_production' => env('LARAVEL_BOOST_STREAMABLE_HTTP_WARN_UNPROTECTED', true),
    'auto_resolve_php_binary' => env('LARAVEL_BOOST_STREAMABLE_HTTP_AUTO_RESOLVE_PHP_BINARY', true),
    'php_binary' => env('LARAVEL_BOOST_STREAMABLE_HTTP_PHP_BINARY'),
];
```

### Middleware

You can protect the endpoint with any Laravel middleware, for example:

```php
'middleware' => ['auth:sanctum'],
```

The configured middleware is applied to **every** verb that `Mcp::web()` registers (GET, POST, and DELETE), so unauthenticated probes cannot confirm the endpoint exists.

Different projects need different protection. There is no middleware default that fits every app, so this package ships with an empty middleware list and leaves the choice to you.

### Route group options

Set a subdomain, path prefix, or route name prefix:

```php
'domain' => 'mcp.your-app.test',
'prefix' => 'api/v1',
'as'     => 'mcp.boost.',
```

Leave any value `null` (or unset the environment variable) to skip that attribute.

### Production warning log

If the endpoint is enabled in the `production` environment **and** no middleware is configured, the package writes a single warning to the application log on Artisan/console boot. The warning is gated on `runningInConsole()` to avoid spamming PHP-FPM request logs. It surfaces during commands like `php artisan serve`, `route:list`, `config:cache`, `queue:work`, or any deploy command. Set `warn_unprotected_in_production` to `false` to silence it:

```env
LARAVEL_BOOST_STREAMABLE_HTTP_WARN_UNPROTECTED=false
```

### CLI PHP binary auto-resolution

Laravel Boost runs each MCP tool call in a subprocess that invokes `php artisan boost:execute-tool ...`. Boost defaults that subprocess to `PHP_BINARY`, which under PHP-FPM, Apache mod_php, or `php-cgi` resolves to the **SAPI** binary, not the CLI binary. The subprocess then emits HTTP headers (`X-Powered-By`, `Content-type`) and skips console-only service registrations, so the `boost:execute-tool` command is never registered. The endpoint then surfaces:

```
Process tool execution failed:
  ERROR  There are no commands defined in the "boost" namespace.
```

To prevent that, this package detects a CLI `php` binary at boot and writes it to `boost.executable_paths.php` for you. The discovery order is:

1. Symfony's `PhpExecutableFinder`
2. The first `php` on `PATH` (filtering out `php-cgi`, `php-fpm`)
3. `PHP_BINDIR` + `php` / `php.exe`

Auto-resolution skips when:

- `boost.executable_paths.php` is already set
- `auto_resolve_php_binary` is `false`
- The current `PHP_BINARY` already looks like a CLI php binary (so CLI invocations are unaffected)

Override the resolved path manually:

```env
LARAVEL_BOOST_STREAMABLE_HTTP_PHP_BINARY=/usr/bin/php8.3
```

Disable auto-resolution entirely:

```env
LARAVEL_BOOST_STREAMABLE_HTTP_AUTO_RESOLVE_PHP_BINARY=false
```

You can also set `BOOST_PHP_EXECUTABLE_PATH` directly. Boost reads that into `boost.executable_paths.php`, and this package will not overwrite it.

## Security warning

Laravel Boost exposes powerful local-development capabilities, including application inspection, database schema access, log reading, and a Tinker tool that can execute arbitrary PHP against your application.

**Do not expose this endpoint publicly without all of the following:**

- Authentication (for example `auth:sanctum`, signed URLs, or OAuth)
- Authorization scoped to trusted developers only
- HTTPS
- Network-level protection (VPN, IP allowlist, firewall, or local-only access)

Prefer local-only usage. Production usage is **not recommended** unless you explicitly understand the risks and have applied strong protection. This package will not block you from enabling it in any environment, by design, but you should treat it as a developer tool.

## Example MCP client config

Generic MCP client configuration shape:

```json
{
  "mcpServers": {
    "laravel-boost": {
      "type": "streamable-http",
      "url": "https://your-app.test/_boost/mcp"
    }
  }
}
```

Exact client config syntax varies by MCP client (Claude Desktop, Cursor, Continue, custom integrations, etc.). Check your client's documentation.

## Troubleshooting

**404 on the configured path**
- Confirm `LARAVEL_BOOST_STREAMABLE_HTTP_ENABLED=true` and that the env file is loaded.
- After changing config or env values, run `php artisan config:clear` and `php artisan route:clear`.
- If you cache routes (`php artisan route:cache`), re-cache after enabling.

**405 Method Not Allowed on GET or DELETE**
- That is expected behavior of `laravel/mcp` 0.7.x. The MCP spec uses POST for client-to-server calls. The current upstream implementation returns 405 on GET and DELETE; only POST handles MCP traffic.

**`Class "Laravel\\Boost\\Mcp\\Boost" not found`**
- `laravel/boost` is not installed in this app, or the version you use no longer ships that class. Run `composer require laravel/boost` and verify the installed version exposes `Laravel\Boost\Mcp\Boost`.

**`Class "Laravel\\Mcp\\Facades\\Mcp" not found`**
- `laravel/mcp` is not installed. Run `composer require laravel/mcp`.

**`Process tool execution failed: ERROR  There are no commands defined in the "boost" namespace.`**
- This means the Boost tool subprocess ran under PHP-FPM / mod_php / php-cgi instead of the CLI binary, so the `boost:execute-tool` command was never registered. By default this package auto-resolves a CLI php binary and writes it to `boost.executable_paths.php`. If the error still appears:
    - Set `LARAVEL_BOOST_STREAMABLE_HTTP_PHP_BINARY` to the absolute path of your CLI php binary (e.g. `/usr/bin/php8.3` or `C:\php\php.exe`).
    - Or set `BOOST_PHP_EXECUTABLE_PATH` directly. Boost reads that into `boost.executable_paths.php` and this package will not overwrite it.
    - Run `php artisan config:clear` after changing env values, then re-cache if you cache config.

**Warning in production logs about unprotected endpoint**
- Configure `laravel-boost-streamable-http.middleware`, disable the endpoint in production, or set `LARAVEL_BOOST_STREAMABLE_HTTP_WARN_UNPROTECTED=false` to silence.

**Route caching**
- The package registers routes inside the service provider's `boot()` method. Standard `route:cache` works.

**`config:cache` and env values**
- Calls to `env()` inside the published config file return `null` after `php artisan config:cache` if the env variable was not set at cache time. If the endpoint stops responding after caching, run `php artisan config:clear`, set the env vars, then re-cache.

## Compatibility

This package depends on:

- `Laravel\Boost\Mcp\Boost` (Laravel Boost's MCP server class)
- `Laravel\Mcp\Facades\Mcp` (Laravel MCP's web transport)

If Laravel Boost changes its server class location, or if Laravel MCP changes its web registration API, a package update may be required. The package guards both class names at boot and throws a `RuntimeException` with an actionable message if either is missing.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT.
