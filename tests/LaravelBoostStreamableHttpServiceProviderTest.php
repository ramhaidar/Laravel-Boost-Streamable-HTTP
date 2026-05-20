<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp\Tests;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;

class LaravelBoostStreamableHttpServiceProviderTest extends TestCase
{
    /** @var array<string, mixed> */
    protected array $configOverrides = [];

    public function test_disabled_by_default_does_not_register_any_route(): void
    {
        $this->assertNull($this->findRoute('POST', '_boost/mcp'));
        $this->assertNull($this->findRoute('GET', '_boost/mcp'));
        $this->assertNull($this->findRoute('DELETE', '_boost/mcp'));
    }

    public function test_enabling_registers_post_get_and_delete_at_default_path(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
        ];

        $this->refreshApplication();

        $this->assertNotNull($this->findRoute('POST', '_boost/mcp'));
        $this->assertNotNull($this->findRoute('GET', '_boost/mcp'));
        $this->assertNotNull($this->findRoute('DELETE', '_boost/mcp'));
    }

    public function test_custom_path_is_respected(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.path' => '/custom/mcp/endpoint',
        ];

        $this->refreshApplication();

        $this->assertNotNull($this->findRoute('POST', 'custom/mcp/endpoint'));
        $this->assertNull($this->findRoute('POST', '_boost/mcp'));
    }

    public function test_middleware_config_is_applied_to_post_route(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => ['auth:sanctum', 'throttle:30,1'],
        ];

        $this->refreshApplication();

        $route = $this->findRoute('POST', '_boost/mcp');

        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertContains('auth:sanctum', $middleware);
        $this->assertContains('throttle:30,1', $middleware);
    }

    public function test_provider_loads_without_breaking_boost_stdio(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(\Laravel\Boost\BoostServiceProvider::class));
        $this->assertTrue($this->app->providerIsLoaded(\Ramhaidar\LaravelBoostStreamableHttp\LaravelBoostStreamableHttpServiceProvider::class));
        $this->assertTrue(class_exists(\Laravel\Boost\Mcp\Boost::class));
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        foreach ($this->configOverrides as $key => $value) {
            $app['config']->set($key, $value);
        }
    }

    private function findRoute(string $method, string $uri): ?Route
    {
        foreach (Router::getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        return null;
    }
}
