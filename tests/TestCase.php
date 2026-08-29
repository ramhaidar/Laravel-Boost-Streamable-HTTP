<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp\Tests;

use Illuminate\Foundation\Application;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Boost\Rules\RuleRepository;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Ramhaidar\LaravelBoostStreamableHttp\LaravelBoostStreamableHttpServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
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

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Boost 2.5.0+ only registers RuleRepository when not running unit
        // tests. Bind it manually so tools/list and tools/call can resolve the
        // RecordRule tool in the testbench environment, mirroring a real app.
        if (class_exists(RuleRepository::class)) {
            $app->singleton(
                RuleRepository::class,
                fn (): RuleRepository => new RuleRepository(base_path('.ai/rules')),
            );
        }
    }
}
