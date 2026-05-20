<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp;

use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Facades\Mcp;

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

        $path = (string) config('laravel-boost-streamable-http.path', '/_boost/mcp');

        $registration = Mcp::web($path, Boost::class);

        $middleware = (array) config('laravel-boost-streamable-http.middleware', []);

        if ($middleware !== [] && $registration instanceof Route) {
            $registration->middleware($middleware);
        }
    }
}
