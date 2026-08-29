<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp;

use Composer\InstalledVersions;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Facades\Mcp;
use RuntimeException;

class LaravelBoostStreamableHttpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-boost-streamable-http.php',
            'laravel-boost-streamable-http',
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-boost-streamable-http.php' => config_path('laravel-boost-streamable-http.php'),
            ], 'laravel-boost-streamable-http-config');
        }

        if (! config('laravel-boost-streamable-http.enabled')) {
            return;
        }

        if (! class_exists(Boost::class)) {
            throw new RuntimeException(
                'Laravel\\Boost\\Mcp\\Boost not found. Install laravel/boost: composer require laravel/boost',
            );
        }

        if (! class_exists(Mcp::class)) {
            throw new RuntimeException(
                'Laravel\\Mcp\\Facades\\Mcp not found. Install laravel/mcp: composer require laravel/mcp',
            );
        }

        $this->assertBoostSupportsExecutablePathsConfig();

        $this->ensureBoostUsesCliPhpBinary();

        // Normalize once so validation and registration consume the same value.
        $path = trim((string) config('laravel-boost-streamable-http.path', '/_boost/mcp'));
        $middleware = (array) config('laravel-boost-streamable-http.middleware', []);
        $domain = config('laravel-boost-streamable-http.domain');
        $prefix = config('laravel-boost-streamable-http.prefix');
        $as = config('laravel-boost-streamable-http.as');

        $this->assertValidPath($path);
        $this->assertSafeToRegisterInProtectedEnvironments($middleware);

        $attributes = [];

        if ($middleware !== []) {
            $attributes['middleware'] = $middleware;
        }

        if (is_string($domain) && $domain !== '') {
            $attributes['domain'] = $domain;
        }

        if (is_string($prefix) && $prefix !== '') {
            $attributes['prefix'] = $prefix;
        }

        if (is_string($as) && $as !== '') {
            $attributes['as'] = $as;
        }

        $register = static function () use ($path): void {
            Mcp::web($path, Boost::class);
        };

        if ($attributes === []) {
            $register();

            return;
        }

        Route::group($attributes, $register);
    }

    /**
     * Ensure Laravel Boost's tool subprocess uses a CLI PHP binary.
     *
     * Boost's ToolExecutor builds an `artisan boost:execute-tool ...` subprocess
     * using `config('boost.executable_paths.php') ?? PHP_BINARY`. Under PHP-FPM,
     * Apache mod_php, or php-cgi, `PHP_BINARY` resolves to the SAPI binary
     * (php-fpm, apache2/httpd, php-cgi), not the CLI. The subprocess then emits
     * HTTP headers ("X-Powered-By", "Content-type") and skips console-only
     * service registrations (because runningInConsole() is false), so
     * `boost:execute-tool` is never registered. The Streamable HTTP endpoint
     * then surfaces:
     *
     *   "Process tool execution failed:
     *     ERROR  There are no commands defined in the \"boost\" namespace."
     *
     * This method writes a discovered CLI php binary to
     * `boost.executable_paths.php` when:
     *   - auto resolution is enabled (default true)
     *   - no `boost.executable_paths.php` is already configured
     *
     * Resolution precedence:
     *   1. an existing, non-empty `boost.executable_paths.php` (never overwritten)
     *   2. the explicit `laravel-boost-streamable-http.php_binary` override
     *   3. the current `PHP_BINARY` when it is a usable CLI php binary and the
     *      current SAPI is exactly `cli` (this also repairs Boost's blank `''`
     *      executable_paths edge case)
     *   4. best-effort discovery via the CLI sibling, PATH, and PHP_BINDIR
     */
    private function ensureBoostUsesCliPhpBinary(): void
    {
        if (! (bool) config('laravel-boost-streamable-http.auto_resolve_php_binary', true)) {
            return;
        }

        // 1. Respect an explicit, non-empty Boost configuration if present.
        //    Whitespace-only values are treated as blank (Boost issue #930).
        $existing = config('boost.executable_paths.php');

        if (is_string($existing) && trim($existing) !== '') {
            return;
        }

        // 2. An explicit package override takes priority over PHP_BINARY.
        $configured = config('laravel-boost-streamable-http.php_binary');
        $configured = is_string($configured) ? trim($configured) : '';

        if ($configured !== '') {
            config(['boost.executable_paths.php' => $configured]);

            return;
        }

        // 3. A safe current PHP_BINARY also repairs Boost's blank '' config.
        if (defined('PHP_BINARY') && $this->isUsableCliPhp(PHP_BINARY) && $this->isCliSapi()) {
            config(['boost.executable_paths.php' => PHP_BINARY]);

            return;
        }

        // 4. Best-effort discovery.
        $resolved = $this->discoverCliPhpBinary();

        if (! is_string($resolved) || $resolved === '') {
            return;
        }

        config(['boost.executable_paths.php' => $resolved]);
    }

    /**
     * Laravel Boost honors `boost.executable_paths.php` in its ToolExecutor
     * from v2.4.2 onward. Before that it used `PHP_BINARY` directly, so the
     * FPM/CGI workaround this package provides cannot work.
     *
     * composer.json requires `^2.4.5` (the first Boost release compatible
     * with this package's minimum `laravel/mcp ^0.7.0`); this is a defensive
     * runtime guard for installs where the constraint cannot be enforced (for
     * example, a `dev-*` or path-repository install of an older Boost).
     */
    private function assertBoostSupportsExecutablePathsConfig(): void
    {
        if (! class_exists(InstalledVersions::class)) {
            return;
        }

        $this->assertBoostVersionSupportsExecutablePathsConfig(InstalledVersions::getVersion('laravel/boost'));
    }

    /**
     * Pure version check, extracted for testability.
     */
    private function assertBoostVersionSupportsExecutablePathsConfig(mixed $version): void
    {
        if (! is_string($version) || $version === '' || str_contains($version, 'dev')) {
            // Cannot reliably determine the installed version; do not block.
            return;
        }

        if (version_compare($version, '2.4.2', '<')) {
            throw new RuntimeException(
                'Laravel Boost '.$version.' does not honor boost.executable_paths.php (added in v2.4.2), so the '.
                'PHP-FPM/CGI subprocess fix in this package cannot work. This package requires '.
                'laravel/boost ^2.4.5. Run: composer require laravel/boost:^2.4.5',
            );
        }
    }

    /**
     * Fail closed in protected environments: refuse to register a
     * high-privilege endpoint that has no middleware unless the operator
     * explicitly opts out with `allow_unprotected_in_production`.
     *
     * The environments protected by default are those in
     * `laravel-boost-streamable-http.protected_environments` (default:
     * `['production']`). Add environments such as `staging` or `prod` to
     * extend the guard. This is a missing-middleware guard, not an
     * authentication guarantee: it cannot determine whether arbitrary
     * middleware authenticates the caller.
     *
     * @param  array<int, mixed>  $middleware
     */
    private function assertSafeToRegisterInProtectedEnvironments(array $middleware): void
    {
        $app = $this->app;

        if (! $app instanceof Application) {
            return;
        }

        $protected = config('laravel-boost-streamable-http.protected_environments', ['production']);
        $protected = is_array($protected) ? $protected : ['production'];

        if (! in_array($app->environment(), $protected, true)) {
            return;
        }

        if ($middleware !== []) {
            return;
        }

        if ((bool) config('laravel-boost-streamable-http.allow_unprotected_in_production', false)) {
            // Explicit escape hatch: register anyway, but still warn.
            $this->maybeWarnUnprotectedInProtectedEnvironment($middleware, $protected);

            return;
        }

        $environment = (string) $app->environment();

        throw new RuntimeException(
            'Refusing to register the Laravel Boost MCP endpoint in the "'.$environment.'" environment without middleware. '.
            'Laravel Boost exposes powerful capabilities including arbitrary code execution via Tinker. '.
            'Configure laravel-boost-streamable-http.middleware (e.g. ["auth:sanctum"]) or set '.
            'laravel-boost-streamable-http.allow_unprotected_in_production=true to explicitly accept the risk.',
        );
    }

    /**
     * Validate the configured route path so a misconfigured environment value
     * cannot silently register an empty, root, or absolute-URL route.
     */
    private function assertValidPath(string $path): void
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            throw new RuntimeException(
                'laravel-boost-streamable-http.path must not be empty.',
            );
        }

        if ($trimmed === '/') {
            throw new RuntimeException(
                'laravel-boost-streamable-http.path must not be the root path "/".',
            );
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $trimmed) === 1) {
            throw new RuntimeException(
                'laravel-boost-streamable-http.path must not contain a URL scheme (e.g. "https://").',
            );
        }

        if (str_starts_with($trimmed, '//')) {
            throw new RuntimeException(
                'laravel-boost-streamable-http.path must not be protocol-relative (e.g. "//host/path").',
            );
        }

        if (str_contains($trimmed, '?') || str_contains($trimmed, '#')) {
            throw new RuntimeException(
                'laravel-boost-streamable-http.path must not contain a query string or fragment.',
            );
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $trimmed) === 1) {
            throw new RuntimeException(
                'laravel-boost-streamable-http.path must not contain control characters.',
            );
        }
    }

    /**
     * Best-effort CLI PHP binary discovery.
     *
     * Strategy:
     *   1. Check the CLI sibling of the active SAPI binary (for example,
     *      `php.exe` beside `php-cgi.exe`).
     *   2. Scan every system PATH entry for `php` / `php.exe` / `php.bat` /
     *      `php.cmd` (equivalent to `where.exe php` / `which -a php`),
     *      skipping cgi/fpm names and version-manager shims.
     *   3. PHP_BINDIR + 'php' / 'php.exe' as a final fallback.
     */
    private function discoverCliPhpBinary(): ?string
    {
        // 1. Check the CLI sibling of the active SAPI binary first.
        //    This is important for Apache/PHP-CGI installations where PATH only
        //    contains a version-manager shim and PHP_BINARY points to php-cgi.
        $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : '';
        $binaryDirectory = $phpBinary !== '' ? dirname($phpBinary) : '';
        $siblingCandidates = $binaryDirectory !== ''
            ? [
                $binaryDirectory.DIRECTORY_SEPARATOR.'php',
                $binaryDirectory.DIRECTORY_SEPARATOR.'php.exe',
                $binaryDirectory.DIRECTORY_SEPARATOR.'php-cli',
                $binaryDirectory.DIRECTORY_SEPARATOR.'php-cli.exe',
            ]
            : [];

        foreach ($siblingCandidates as $candidate) {
            if ($this->isUsableCliPhp($candidate)) {
                return $candidate;
            }
        }

        // 2. Check all candidates from system PATH (equivalent to `where.exe php` / `which -a php`)
        $pathEnv = getenv('PATH') ?: getenv('Path') ?: '';
        $dirs = array_filter(explode(PATH_SEPARATOR, $pathEnv));

        $extensions = PHP_OS_FAMILY === 'Windows' ? ['.exe', '.bat', '.cmd', ''] : [''];

        foreach ($dirs as $dir) {
            foreach ($extensions as $ext) {
                $candidate = rtrim($dir, '\\/').DIRECTORY_SEPARATOR.'php'.$ext;

                if ($this->isUsableCliPhp($candidate)) {
                    return $candidate;
                }
            }
        }

        // 3. Fallback: PHP_BINDIR
        $bindir = defined('PHP_BINDIR') ? PHP_BINDIR : '';

        if ($bindir !== '') {
            $candidates = [
                $bindir.DIRECTORY_SEPARATOR.'php',
                $bindir.DIRECTORY_SEPARATOR.'php.exe',
            ];

            foreach ($candidates as $candidate) {
                if ($this->isUsableCliPhp($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Is this path a usable CLI php binary?
     *
     * Checks that the file exists, is executable (non-Windows), and that
     * both the given path and its resolved realpath (when it differs) look
     * like a CLI php binary. The realpath check catches symlinks such as
     * `/usr/bin/php -> /usr/bin/php-cgi`.
     */
    private function isUsableCliPhp(string $candidate): bool
    {
        if (! is_file($candidate)) {
            return false;
        }

        if (PHP_OS_FAMILY !== 'Windows' && ! is_executable($candidate)) {
            return false;
        }

        if (! $this->looksLikeCliPhp($candidate)) {
            return false;
        }

        $resolved = realpath($candidate);

        if (is_string($resolved) && $resolved !== $candidate && ! $this->looksLikeCliPhp($resolved)) {
            return false;
        }

        return true;
    }

    /**
     * Is the current process running through the CLI SAPI?
     *
     * Guards the `PHP_BINARY` fast path against the theoretical case where a
     * CGI/FPM executable is itself named "php": the basename heuristic would
     * classify it as CLI even though the current SAPI is not CLI. `phpdbg` is
     * intentionally excluded: it is an interactive debugger, not a drop-in
     * CLI binary for Boost's `artisan boost:execute-tool` subprocess, so
     * under `phpdbg` we fall through to normal discovery instead.
     */
    private function isCliSapi(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Heuristic: does this path look like a CLI php binary?
     *
     * Recognizes typical CLI names ("php", "php.exe", "php8.3") and rejects
     * SAPI binaries ("php-fpm", "php-cgi", "apache2", "httpd", etc.) and
     * version-manager shims (mise, asdf, volta, etc.).
     */
    private function looksLikeCliPhp(string $path): bool
    {
        $name = strtolower(basename($path));

        if ($name === '' || ! str_starts_with($name, 'php')) {
            return false;
        }

        // Reject SAPI variants.
        foreach (['cgi', 'fpm'] as $bad) {
            if (str_contains($name, $bad)) {
                return false;
            }
        }

        // Reject version-manager shims (mise, asdf, volta, etc.).
        $normalized = str_replace('\\', '/', strtolower($path));
        if (str_contains($normalized, '/shims/')) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $middleware
     * @param  array<int, string>  $protected
     */
    private function maybeWarnUnprotectedInProtectedEnvironment(array $middleware, array $protected): void
    {
        if ($middleware !== []) {
            return;
        }

        if (! (bool) config('laravel-boost-streamable-http.warn_unprotected_in_production', true)) {
            return;
        }

        $app = $this->app;

        if (! $app instanceof Application) {
            return;
        }

        if (! in_array($app->environment(), $protected, true) || ! $app->runningInConsole()) {
            return;
        }

        $environment = (string) $app->environment();

        Log::warning(
            '[laravel-boost-streamable-http] MCP endpoint enabled in the "'.$environment.'" environment with no middleware. '.
            'Laravel Boost exposes powerful capabilities including arbitrary code execution via Tinker. '.
            'Configure laravel-boost-streamable-http.middleware (e.g. ["auth:sanctum"]) or disable in protected environments.',
        );
    }
}
