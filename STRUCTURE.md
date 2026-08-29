# Codebase Structure

## Directory Layout

```
D:\GitHub\Laravel Boost Streamable HTTP\
├── config/                   # Package configuration
│   └── laravel-boost-streamable-http.php  # Published config file
├── src/                      # Source code
│   └── LaravelBoostStreamableHttpServiceProvider.php  # Main ServiceProvider
├── tests/                    # Test suite
│   ├── LaravelBoostStreamableHttpServiceProviderTest.php  # Service provider tests
│   └── TestCase.php  # Base test case
├── composer.json             # Project dependencies
├── CHANGELOG.md              # Change log
├── README.md                 # Project overview
└── .git/                     # Git repository
```

## Directory Purposes

**config/:**
- Purpose: Holds the package's publishable configuration file
- Contains: `laravel-boost-streamable-http.php` — all tunable parameters (enabled flag, path, middleware, domain/prefix/name attributes, auto-resolve PHP binary flag, explicit PHP binary override)
- Key files: `laravel-boost-streamable-http.php`

**src/:**
- Purpose: Contains the main source code — the Laravel ServiceProvider that registers the MCP Streamable HTTP endpoint
- Contains: `LaravelBoostStreamableHttpServiceProvider.php` — the central ServiceProvider, config merge, route registration, PHP binary resolution, fail-closed protected-environment guard, route path validation, Boost version guard
- Key files: `LaravelBoostStreamableHttpServiceProvider.php`

**tests/:**
- Purpose: Test suite for the ServiceProvider, covering endpoint registration, config options, middleware, route attributes, protected-environment fail-closed behavior, PHP binary resolution, route path validation, Boost version guard, and JSON-RPC initialize/tools/list/tools/call responses
- Contains: `LaravelBoostStreamableHttpServiceProviderTest.php` — comprehensive test cases; `TestCase.php` — base test case
- Key files: `LaravelBoostStreamableHttpServiceProviderTest.php`, `TestCase.php`

## Key File Locations

**Entry Points:** `src/LaravelBoostStreamableHttpServiceProvider.php`: The ServiceProvider that bootstraps the MCP endpoint and registers routes during Laravel's boot cycle

**Configuration:** `config/laravel-boost-streamable-http.php`: Package configuration — all settings are environment-driven via `.env` or overridden in tests

**Core Logic:** `src/LaravelBoostStreamableHttpServiceProvider.php::boot()`: Core endpoint registration logic — checks enabled flag, resolves CLI PHP binary, validates the route path, enforces the fail-closed protected-environment guard, builds route attributes, calls `Mcp::web()` or wraps in `Route::group()`

**Tests:** `tests/LaravelBoostStreamableHttpServiceProviderTest.php`: Full test coverage for the ServiceProvider, including disabled-by-default behavior, enabling routes, custom paths, middleware application, route prefix/domain/name, protected-environment fail-closed behavior and escape-hatch warnings, JSON-RPC initialize/tools/list/tools/call responses, route path validation, Boost version guard, and PHP binary config handling

## Naming Conventions

**Files:** StudlyCase PascalPHP: `LaravelBoostStreamableHttpServiceProvider.php`, `TestCase.php`; kebab-case config: `laravel-boost-streamable-http.php`

**Directories:** StudlyCase: `src/`, `tests/`, `config/`

## Where to Add New Code

**New ServiceProvider:** Create a new `src/` file following the existing `LaravelBoostStreamableHttpServiceProvider.php` pattern — extend ServiceProvider, merge config in `register()`, bootstrap routes and safeguards in `boot()`

**New config option:** Edit `config/laravel-boost-streamable-http.php` — add a new key and reference it in `LaravelBoostStreamableHttpServiceProvider.php::boot()` as needed

**New test case:** Add a new test method to `tests/LaravelBoostStreamableHttpServiceProviderTest.php` following the existing patterns (configOverrides, refreshApplication, assertions on routes/middleware/log)

**New route attribute:** Extend the config array in `config/laravel-boost-streamable-http.php` and the attribute-building logic in `LaravelBoostStreamableHttpServiceProvider.php::boot()` to support `domain`, `prefix`, or `as` group attributes
