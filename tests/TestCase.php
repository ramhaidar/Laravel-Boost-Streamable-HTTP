<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp\Tests;

use Laravel\Boost\BoostServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Ramhaidar\LaravelBoostStreamableHttp\LaravelBoostStreamableHttpServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            McpServiceProvider::class,
            BoostServiceProvider::class,
            LaravelBoostStreamableHttpServiceProvider::class,
        ];
    }
}
