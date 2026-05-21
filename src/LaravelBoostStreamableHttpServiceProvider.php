<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Facades\Mcp;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;

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

        $this->ensureBoostUsesCliPhpBinary();

        $path = (string) config('laravel-boost-streamable-http.path', '/_boost/mcp');
        $middleware = (array) config('laravel-boost-streamable-http.middleware', []);
        $domain = config('laravel-boost-streamable-http.domain');
        $prefix = config('laravel-boost-streamable-http.prefix');
        $as = config('laravel-boost-streamable-http.as');

        $this->maybeWarnUnprotectedInProduction($middleware);

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
     *   - PHP_BINARY does not already look like a CLI php binary
     */
    private function ensureBoostUsesCliPhpBinary(): void
    {
        if (! (bool) config('laravel-boost-streamable-http.auto_resolve_php_binary', true)) {
            return;
        }

        // Respect explicit Boost configuration if present.
        $existing = config('boost.executable_paths.php');

        if (is_string($existing) && $existing !== '') {
            return;
        }

        // PHP_BINARY already looks like a CLI php binary, no override needed.
        if (defined('PHP_BINARY') && $this->looksLikeCliPhp(PHP_BINARY)) {
            return;
        }

        $configured = config('laravel-boost-streamable-http.php_binary');
        $configured = is_string($configured) ? trim($configured) : '';

        $resolved = $configured !== ''
            ? $configured
            : $this->discoverCliPhpBinary();

        if (! is_string($resolved) || $resolved === '') {
            return;
        }

        config(['boost.executable_paths.php' => $resolved]);
    }

    /**
     * Best-effort CLI PHP binary discovery.
     *
     * Strategy:
     *   1. Symfony PhpExecutableFinder (handles most setups, including Herd/Valet).
     *   2. ExecutableFinder for `php` on PATH (filtered to skip cgi/fpm names).
     *   3. PHP_BINDIR + 'php' / 'php.exe' as a final fallback.
     */
    private function discoverCliPhpBinary(): ?string
    {
        if (class_exists(PhpExecutableFinder::class)) {
            $finder = new PhpExecutableFinder;
            $found = $finder->find(false);

            if (is_string($found) && $found !== '' && $this->looksLikeCliPhp($found)) {
                return $found;
            }
        }

        if (class_exists(ExecutableFinder::class)) {
            $finder = new ExecutableFinder;
            $found = $finder->find('php');

            if (is_string($found) && $found !== '' && $this->looksLikeCliPhp($found)) {
                return $found;
            }
        }

        $bindir = defined('PHP_BINDIR') ? PHP_BINDIR : '';

        if ($bindir !== '') {
            $candidates = [
                $bindir.DIRECTORY_SEPARATOR.'php',
                $bindir.DIRECTORY_SEPARATOR.'php.exe',
            ];

            foreach ($candidates as $candidate) {
                if (is_file($candidate) && $this->looksLikeCliPhp($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Heuristic: does this path look like a CLI php binary?
     *
     * Recognizes typical CLI names ("php", "php.exe", "php8.3") and rejects
     * SAPI binaries ("php-fpm", "php-cgi", "apache2", "httpd", etc.).
     */
    private function looksLikeCliPhp(string $path): bool
    {
        $name = strtolower(basename($path));

        if ($name === '') {
            return false;
        }

        // Must look like a php binary by name.
        if (! str_starts_with($name, 'php')) {
            return false;
        }

        // Reject SAPI variants by substring.
        foreach (['cgi', 'fpm'] as $bad) {
            if (str_contains($name, $bad)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $middleware
     */
    private function maybeWarnUnprotectedInProduction(array $middleware): void
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

        if ($app->environment('production') && $app->runningInConsole()) {
            Log::warning(
                '[laravel-boost-streamable-http] MCP endpoint enabled in production with no middleware. '.
                'Laravel Boost exposes powerful capabilities including arbitrary code execution via Tinker. '.
                'Configure laravel-boost-streamable-http.middleware (e.g. ["auth:sanctum"]) or disable in production.',
            );
        }
    }
}
