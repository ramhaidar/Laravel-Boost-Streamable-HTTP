# Changelog

All notable changes to `ramhaidar/laravel-boost-streamable-http` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Laravel MCP 0.9 support. Verified that `Mcp::web()` and the `Mcp` facade API are identical between MCP 0.7, 0.8, and 0.9 — no implementation changes required.
- GitHub VCS installation instructions. The package is not on Packagist; users register the repository and require `dev-main`.
- Composer troubleshooting documentation for "Could not find a matching version" and "laravel/mcp is fixed to v0.8.x" errors.
- Auto-resolve a CLI `php` binary at boot and write it to `boost.executable_paths.php` when the package is enabled. Fixes `Process tool execution failed: ERROR There are no commands defined in the "boost" namespace.` under PHP-FPM, Apache mod_php, and `php-cgi`, where Boost's default `PHP_BINARY` resolves to the SAPI binary. Discovery scans the CLI sibling of the active SAPI binary, then system `PATH`, then `PHP_BINDIR`. Skips when `boost.executable_paths.php` is already set, when `auto_resolve_php_binary` is `false`, or when `PHP_BINARY` is a usable CLI php binary and the current SAPI is exactly `cli`.
- `auto_resolve_php_binary` config (env: `LARAVEL_BOOST_STREAMABLE_HTTP_AUTO_RESOLVE_PHP_BINARY`, default `true`) to toggle the behavior.
- `php_binary` config (env: `LARAVEL_BOOST_STREAMABLE_HTTP_PHP_BINARY`) to manually pin the CLI php binary used by Boost's tool subprocess.
- Test coverage: explicit `php_binary` is propagated to `boost.executable_paths.php` even when CLI is already running, an existing `boost.executable_paths.php` is never overwritten, and `auto_resolve_php_binary=false` short-circuits the resolution.
- Laravel 13 support. `illuminate/*` constraints widened to `^11.0|^12.0|^13.0`. `orchestra/testbench` widened to `^9.5|^10.0|^11.0`. `phpunit/phpunit` widened to `^10.5|^11.0|^12.0`. CI matrix gains L13 rows on PHP 8.3 and 8.4 (Laravel 13 requires PHP `^8.3`).
- Initial implementation. Registers Laravel Boost's MCP server (`Laravel\Boost\Mcp\Boost`) on a Laravel MCP web (Streamable HTTP) endpoint via `Mcp::web(...)`.
- `enabled`, `path`, `middleware`, `domain`, `prefix`, `as` config keys.
- Fail-closed protected-environment guard: refuses registration when enabled in a configured protected environment (default `['production']`, configurable via `protected_environments`) without middleware, unless the explicit escape hatch is enabled; an optional console-only warning (`warn_unprotected_in_production`) fires when the escape hatch is used.
- `class_exists` guards with informative `RuntimeException` if `laravel/boost` or `laravel/mcp` is missing.
- HTTP functional test posting JSON-RPC `initialize` and asserting JSON-RPC 2.0 response shape (`jsonrpc`, `id`, `result`/`error`).
- Test coverage: disabled-by-default, default path, custom path, middleware applied to all verbs, prefix, name prefix, domain, production warn-log toggling, JSON-RPC initialize, stdio compatibility.
- GitHub Actions CI matrix covering Laravel 11 (PHP 8.2-8.4), Laravel 12 (PHP 8.2-8.5), and Laravel 13 (PHP 8.3-8.5) across boost 2.4.5+ (MCP 0.8 floor 2.4.10, MCP 0.9 floor 2.4.13) and mcp 0.7/0.8/0.9, plus separate Pint, Larastan, and prefer-lowest jobs.
- Laravel Pint config (Laravel preset + strict types + ordered imports + trailing commas + single quotes).
- Larastan (level 8) config on `src/`.

### Changed
- Requires Laravel Boost `^2.4.5`. ToolExecutor began honoring `boost.executable_paths.php` in Boost v2.4.2, while v2.4.5 is the first release in that line compatible with this package's minimum Laravel MCP 0.7 requirement.
- Widened `laravel/mcp` Composer constraint from `^0.7.0 || ^0.8.0` to `^0.7.0 || ^0.8.0 || ^0.9.0` to support MCP 0.9.
- Fail-closed production default: the endpoint now refuses to register when enabled in the `production` environment without middleware, unless `allow_unprotected_in_production` is set to `true`. The previous behavior only emitted a warning.
- Reworked CLI PHP binary resolution precedence so the explicit `php_binary` override is honored even when `PHP_BINARY` already looks like a CLI binary, and so a blank `boost.executable_paths.php` value is repaired instead of left untouched.
- Validated the configured route `path` (non-empty, not `/`, no URL scheme, no protocol-relative host, no query/fragment, no control characters) and fail loudly on invalid values.
- Updated CI matrix to test MCP 0.7, 0.8, and 0.9 across Laravel 11, 12, and 13. MCP 0.8 rows use Boost `^2.4.10` and MCP 0.9 rows use Boost `^2.4.13` — the minimum Boost releases that support each MCP line — with dedicated exact-version floor rows (`boost 2.4.10 + mcp 0.8.0`, `boost 2.4.13 + mcp 0.9.0`) so CI verifies the actual compatibility floors rather than only newer Boost versions.
- Pinned GitHub Actions to immutable commit SHAs instead of mutable tags for stronger CI supply-chain protection.
- Updated compatibility matrix in README to show Boost `^2.4.5` and MCP 0.7.x/0.8.x/0.9.x support.
- Updated installation documentation to use GitHub VCS-based installation.
- Added PHP 8.5 to the CI matrix (Laravel 12 and 13) and documented Laravel 11 as legacy/EOL support.
- Added `protected_environments` config (default `['production']`) so the fail-closed guard can be extended to environments such as `staging` or `prod`.
- Clarified that the production guard is a missing-middleware guard, not an authentication guarantee.

### Fixed
- The functional `initialize` test now requires an actual successful JSON-RPC `result` instead of accepting any non-4xx/5xx status.
- Added end-to-end functional coverage for the `initialize` → `tools/list` → `tools/call` flow over HTTP, proving Boost tools are reachable through the Streamable HTTP endpoint.
- The explicit `php_binary` override test now asserts the override is actually written instead of accepting "null or the override".
- Whitespace-only `boost.executable_paths.php` values are now treated as blank and repaired (Boost issue #930), matching the existing `''` handling.
- The configured route `path` is now trimmed once before validation and registration, so a value like `" /_boost/mcp "` registers the intended path instead of a whitespace-containing one.
- CI failure artifact names now use the matrix job index (plus PHP version) so matrix rows no longer collide.
- Local log artifacts (`*.log`) are now excluded from release archives so local runtime/parse-error logs cannot ship in a package.
- Corrected the README `config:cache` troubleshooting note: cached config captures resolved values at build time; later `.env` changes require `config:clear` + `config:cache` again.
- Clarified in ARCHITECTURE.md that `boost.executable_paths.php` is mutated only in the runtime config repository and is not persisted to disk or baked into the config cache.
- Tightened SECURITY.md scope wording: bypass/failure of the fail-closed protected-environment guard is in scope; explicitly opting out via `allow_unprotected_in_production=true` is operator-accepted risk.
- Corrected the documented Boost/MCP compatibility floors: MCP 0.8 requires Boost 2.4.10+ and MCP 0.9 requires Boost 2.4.13+ (not Boost 2.5.0+). Boost v2.4.10 first allowed `laravel/mcp ^0.8.0`; Boost v2.4.13 first allowed `laravel/mcp ^0.9.0`.
