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
