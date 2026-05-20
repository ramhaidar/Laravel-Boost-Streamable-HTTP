# Laravel Boost Streamable HTTP

> **Disclaimer:** This is an unofficial community package. It is not affiliated with Laravel, Laravel Boost, or Laravel LLC.

Unofficial opt-in Streamable HTTP / web MCP transport for [Laravel Boost](https://github.com/laravel/boost).

## Why this exists

Laravel Boost's default stdio MCP transport can be unreliable in some MCP clients that stop, idle out, or lose long-running stdio processes. This package lets you expose Boost through Laravel MCP's web (Streamable HTTP) transport instead, by registering Boost's existing MCP server class against `Mcp::web(...)`.

It does not modify or fork Laravel Boost. It is a thin opt-in service provider.

## Requirements

- PHP `^8.2`
- Laravel 11, 12, or 13
- [`laravel/boost`](https://github.com/laravel/boost)
- [`laravel/mcp`](https://github.com/laravel/mcp) `^0.7.1`

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
];
```

### Middleware

You can protect the endpoint with any Laravel middleware, for example:

```php
'middleware' => ['auth:sanctum'],
```

Different projects need different protection. There is no middleware default that fits every app, so this package ships with an empty middleware list and leaves the choice to you.

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

## Compatibility

This package depends on:

- `Laravel\Boost\Mcp\Boost` (Laravel Boost's MCP server class)
- `Laravel\Mcp\Facades\Mcp` (Laravel MCP's web transport)

If Laravel Boost changes its server class location, or if Laravel MCP changes its web registration API, a package update may be required.

## License

MIT.
