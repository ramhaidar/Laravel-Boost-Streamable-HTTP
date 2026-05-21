# Changelog

All notable changes to `ramhaidar/laravel-boost-streamable-http` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Auto-resolve a CLI `php` binary at boot and write it to `boost.executable_paths.php` when the package is enabled. Fixes `Process tool execution failed: ERROR There are no commands defined in the "boost" namespace.` under PHP-FPM, Apache mod_php, and `php-cgi`, where Boost's default `PHP_BINARY` resolves to the SAPI binary. Discovery uses Symfony's `PhpExecutableFinder`, then `ExecutableFinder('php')`, then `PHP_BINDIR`. Skips when `boost.executable_paths.php` is already set, when `auto_resolve_php_binary` is `false`, or when `PHP_BINARY` already looks like a CLI php binary.
- `auto_resolve_php_binary` config (env: `LARAVEL_BOOST_STREAMABLE_HTTP_AUTO_RESOLVE_PHP_BINARY`, default `true`) to toggle the behavior.
- `php_binary` config (env: `LARAVEL_BOOST_STREAMABLE_HTTP_PHP_BINARY`) to manually pin the CLI php binary used by Boost's tool subprocess.
- Test coverage: explicit `php_binary` is propagated to `boost.executable_paths.php` (or left untouched when CLI is already running), an existing `boost.executable_paths.php` is never overwritten, and `auto_resolve_php_binary=false` short-circuits the resolution.
- Laravel 13 support. `illuminate/*` constraints widened to `^11.0|^12.0|^13.0`. `orchestra/testbench` widened to `^9.5|^10.0|^11.0`. `phpunit/phpunit` widened to `^10.5|^11.0|^12.0`. CI matrix gains L13 rows on PHP 8.3 and 8.4 (Laravel 13 requires PHP `^8.3`).
- Initial implementation. Registers Laravel Boost's MCP server (`Laravel\Boost\Mcp\Boost`) on a Laravel MCP web (Streamable HTTP) endpoint via `Mcp::web(...)`.
- `enabled`, `path`, `middleware`, `domain`, `prefix`, `as` config keys.
- Optional production warning log (console-only) when enabled without middleware (`warn_unprotected_in_production`).
- `class_exists` guards with informative `RuntimeException` if `laravel/boost` or `laravel/mcp` is missing.
- HTTP functional test posting JSON-RPC `initialize` and asserting JSON-RPC 2.0 response shape (`jsonrpc`, `id`, `result`/`error`).
- Test coverage: disabled-by-default, default path, custom path, middleware applied to all verbs, prefix, name prefix, domain, production warn-log toggling, JSON-RPC initialize, stdio compatibility.
- GitHub Actions CI matrix (PHP 8.2/8.3/8.4 × Laravel 11/12 × boost 2 × mcp 0.7), plus separate Pint, Larastan, and prefer-lowest jobs.
- Laravel Pint config (Laravel preset + strict types + ordered imports + trailing commas + single quotes).
- Larastan (level 8) config on `src/`.
