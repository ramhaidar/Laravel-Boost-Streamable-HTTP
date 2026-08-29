# Architecture

## Pattern Overview

**Overall:** Plugin-based hook system via Laravel Boost's MCP (Model Context Protocol) Streamable HTTP endpoint. The package registers a web endpoint that exposes Laravel Boost's tool execution capabilities over HTTP, using a streamable HTTP transport pattern. This allows IDEs and clients to communicate with Boost tools via POST/GET/DELETE requests to a mounted path.

**Key Characteristics:**
- MCP web endpoint registered via Laravel ServiceProvider
- Streamable HTTP transport for tool execution requests/results
- Boost::class and Mcp::facade as core abstractions
- Config-driven route attributes (path, middleware, domain, prefix, name prefix)
- Auto-detection and resolution of CLI PHP binary for subprocess execution
- Production safeguard: fail-closed refusal when endpoint is enabled in a
  protected environment without middleware (configurable escape hatch)

## Layers

**[Service Provider Layer]:**
- Purpose: Bootstraps the MCP endpoint registration and config merging; ensures Boost and Mcp classes are available
- Location: `src/LaravelBoostStreamableHttpServiceProvider.php`
- Contains: ServiceProvider subclass, route registration logic, PHP binary resolution, middleware warnings
- Depends on: Laravel's Application container, Boost::class, Mcp::facade, config path
- Used by: Laravel app boot cycle; MCP clients connecting to the registered endpoint

**[Configuration Layer]:**
- Purpose: Holds all tunable parameters for the MCP endpoint path, attributes, and behavior flags
- Location: `config/laravel-boost-streamable-http.php`
- Contains: enabled flag, path, middleware array, domain/prefix/name group attributes, auto-resolve PHP binary flag, explicit PHP binary override
- Depends on: Laravel config system, environment variables
- Used by: Service provider's register() and boot() methods

**[Runtime Execution Layer]:**
- Purpose: Executes Boost tools in subprocesses using a resolved CLI PHP binary; handles the streamable HTTP request/response cycle
- Location: Internal to Boost's ToolExecutor subprocess (`artisan boost:execute-tool`), configured via `boost.executable_paths.php`
- Depends on: CLI php binary path, config `boost.executable_paths.php`
- Used by: MCP requests that require tool execution outside the web process

## Data Flow

**MCP Endpoint Registration:** (triggered during Laravel boot)

1. ServiceProvider::register() — merges config from `__DIR__.'/../config/laravel-boost-streamable-http.php'` into Laravel's config store under `laravel-boost-streamable-http`
2. ServiceProvider::boot() — checks `config('laravel-boost-streamable-http.enabled')`; if false, returns early without registering any routes
3. If enabled, calls `ensureBoostUsesCliPhpBinary()` to resolve/write a CLI php binary path to `boost.executable_paths.php`
4. Builds route attributes from config: `middleware`, `domain`, `prefix`, `as`
5. If no attributes set, calls `Mcp::web($path, Boost::class)` directly; otherwise wraps in `Route::group($attributes, $register)`

**PHP Binary Resolution:**
1. `ensureBoostUsesCliPhpBinary()` checks `config('boost.executable_paths.php')` first; if explicitly set, returns
2. If `config('laravel-boost-streamable-http.php_binary')` is set, uses that value (explicit override wins over `PHP_BINARY`)
3. If `PHP_BINARY` is defined, passes `isUsableCliPhp()` (file exists, executable on Unix, CLI-looking basename, resolved symlink still CLI-looking) **and** the current SAPI is exactly `cli` (`isCliSapi()`; `phpdbg` is excluded), uses it; this also repairs Boost's blank `''`/whitespace executable_paths edge case
4. Otherwise calls `discoverCliPhpBinary()` which:
   a. Checks CLI sibling of active SAPI binary (e.g., `php.exe` next to `php-cgi.exe`)
   b. Scans system PATH for `php`/`php.exe`/`.bat`/`.cmd`, skipping CGI/FPM names and version-manager shims
   c. Falls back to `PHP_BINDIR` + `php`/`.exe`
5. Writes discovered/resolved path to `config('boost.executable_paths.php')`

## Key Abstractions

**LaravelBoostStreamableHttpServiceProvider:**
- Purpose: The central ServiceProvider that wires the MCP endpoint into a Laravel application
- Location: `src/LaravelBoostStreamableHttpServiceProvider.php`
- Pattern: Laravel ServiceProvider boot-time registration with config merge and route grouping

**Boost::class (Laravel\Boost\Mcp\Boost):**
- Purpose: Core Boost facade providing the namespace and tool definitions registered under the "boost" namespace
- Location: Provided by `laravel/boost` package
- Pattern: Singleton-like facade accessed via Mcp::web()

**Mcp::facade (Laravel\Mcp\Facades\Mcp):**
- Purpose: Facade for registering web MCP endpoints (`Mcp::web()`) and handling the streamable HTTP protocol
- Location: Provided by `laravel/mcp` package
- Pattern: Facade wrapping the MCP web server implementation

## Entry Points

**Service Provider Boot:**
- Location: `src/LaravelBoostStreamableHttpServiceProvider.php::boot()`
- Triggers: Laravel app boot cycle; package enabled via `LARAVEL_BOOST_STREAMABLE_HTTP_ENABLED` env var (defaults to false)
- Responsibilities: Registers the MCP web endpoint at the configured path with optional route group attributes (middleware, domain, prefix, name prefix); refuses registration in protected environments when no middleware is configured (a warning is emitted only when the explicit escape hatch is enabled)

**Config Publishing:**
- Location: `src/LaravelBoostStreamableHttpServiceProvider.php::boot()`
- Triggers: `php artisan vendor:publish` with tag `laravel-boost-streamable-http-config`
- Responsibilities: Publishes `config/laravel-boost-streamable-http.php` to `config_path('laravel-boost-streamable-http.php')`

## Error Handling

**Strategy:** Fail closed with descriptive RuntimeExceptions for missing dependencies, invalid configuration, and unprotected protected-environment registration; warnings only for the explicit escape-hatch path

- Missing `Boost::class`: throws `RuntimeException` with installation instructions (`composer require laravel/boost`)
- Missing `Mcp::class`: throws `RuntimeException` with installation instructions (`composer require laravel/mcp`)
- Invalid route path (empty, root, URL scheme, protocol-relative, query/fragment, control characters): throws `RuntimeException`
- Boost < 2.4.2 (which does not honor `boost.executable_paths.php`): throws `RuntimeException`
- Endpoint enabled in a protected environment (default `['production']`, configurable via `protected_environments`) without middleware: throws `RuntimeException` unless `allow_unprotected_in_production` is `true`
- When the escape hatch is set, emits `Log::warning` (configurable via `warn_unprotected_in_production` flag) and registration proceeds
- CLI PHP binary not found: `discoverCliPhpBinary()` returns null; no override written; auto-resolution silently skips

**Logged Errors:** `Log::warning` for unprotected endpoint registration via the explicit escape hatch (configurable)

## Cross-Cutting Concerns

**Logging:** Uses Laravel's `Log` facade; writes a warning to the application log when the endpoint is registered unprotected via the explicit escape hatch (configurable via `warn_unprotected_in_production`); there is no logging for PHP binary discovery events; channel not configured explicitly, uses default

**Caching:** Normal package configuration is merged via `mergeConfigFrom` and respects Laravel's config cache (`config:cache`). The auto-resolved `boost.executable_paths.php` is mutated only in the runtime config repository for the current application/process lifecycle; this package does not persist it to disk or bake it into the existing config cache.

**Storage:** Modifies Laravel's runtime config repository (`config(['boost.executable_paths.php' => $resolved])`) for the current process only; it does not write `config/boost.php` to disk and is not baked into Laravel's generated config cache; publishes original config file via vendor:publish; no other persistent state beyond config
