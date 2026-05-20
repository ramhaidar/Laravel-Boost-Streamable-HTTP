<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp;

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
            'laravel-boost-streamable-http'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-boost-streamable-http.php' => config_path('laravel-boost-streamable-http.php'),
        ], 'laravel-boost-streamable-http-config');

        if (! config('laravel-boost-streamable-http.enabled')) {
            return;
        }

        if (! class_exists(Boost::class)) {
            throw new RuntimeException(
                'Laravel\\Boost\\Mcp\\Boost not found. Install laravel/boost: composer require laravel/boost'
            );
        }

        if (! class_exists(Mcp::class)) {
            throw new RuntimeException(
                'Laravel\\Mcp\\Facades\\Mcp not found. Install laravel/mcp: composer require laravel/mcp'
            );
        }

        $path = (string) config('laravel-boost-streamable-http.path', '/_boost/mcp');
        $middleware = (array) config('laravel-boost-streamable-http.middleware', []);

        $register = static function () use ($path): void {
            Mcp::web($path, Boost::class);
        };

        if ($middleware !== []) {
            Route::middleware($middleware)->group($register);

            return;
        }

        $register();
    }
}
