# Laravel Boost Streamable HTTP

[![tests](https://github.com/ramhaidar/laravel-boost-streamable-http/actions/workflows/tests.yml/badge.svg)](https://github.com/ramhaidar/laravel-boost-streamable-http/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> **Disclaimer:** This is an unofficial community package. It is not affiliated with Laravel, Laravel Boost, or Laravel LLC.

Unofficial opt-in Streamable HTTP / web MCP transport for [Laravel Boost](https://github.com/laravel/boost).

## Why this exists

Laravel Boost's default stdio MCP transport can be unreliable in some MCP clients that stop, idle out, or lose long-running stdio processes. This package lets you expose Boost through Laravel MCP's web (Streamable HTTP) transport instead, by registering Boost's existing MCP server class against `Mcp::web(...)`.

It does not modify or fork Laravel Boost. It is a thin opt-in service provider.

## Requirements

- PHP `^8.2`
- Laravel 11, 12, or 13
- [`laravel/boost`](https://github.com/laravel/boost) `^1.0` or `^2.0`
- [`laravel/mcp`](https://github.com/laravel/mcp) `^0.7.0` or `^1.0`

### Compatibility matrix

| This package | Laravel        | PHP           | laravel/boost  | laravel/mcp     |
|--------------|----------------|---------------|----------------|-----------------|
| `0.x`        | 11.x, 12.x     | 8.2, 8.3, 8.4 | 1.x, 2.x       | 0.7.x, 1.x      |

`laravel/mcp` `<0.7.1` registers GET + POST on the endpoint. `>=0.7.1` also registers DELETE per the MCP spec. The package handles both transparently.

## Installation

```bash
composer require ramhaidar/laravel-boost-streamable-http
```

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
];
```

### Middleware

You can protect the endpoint with any Laravel middleware, for example:

```php
'middleware' => ['auth:sanctum'],
```

The configured middleware is applied to **every** verb that `Mcp::web()` registers (GET, POST, and DELETE on `laravel/mcp >=0.7.1`), so unauthenticated probes cannot confirm the endpoint exists.

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

**405 Method Not Allowed on GET**
- That is expected. The MCP spec uses POST for client-to-server calls. GET (and DELETE on `laravel/mcp >=0.7.1`) return 405 by design and exist only to advertise the endpoint.

**`Class "Laravel\\Boost\\Mcp\\Boost" not found`**
- `laravel/boost` is not installed in this app, or the version you use no longer ships that class. Run `composer require laravel/boost` and verify the installed version exposes `Laravel\Boost\Mcp\Boost`.

**`Class "Laravel\\Mcp\\Facades\\Mcp" not found`**
- `laravel/mcp` is not installed. Run `composer require laravel/mcp`.

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
