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
    }

    public function test_enabling_registers_get_and_post_at_default_path(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
        ];

        $this->refreshApplication();

        $this->assertNotNull($this->findRoute('POST', '_boost/mcp'));
        $this->assertNotNull($this->findRoute('GET', '_boost/mcp'));
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

    public function test_middleware_config_applies_to_all_registered_verbs(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => ['auth:sanctum', 'throttle:30,1'],
        ];

        $this->refreshApplication();

        $verbs = ['POST', 'GET', 'DELETE'];

        foreach ($verbs as $verb) {
            $route = $this->findRoute($verb, '_boost/mcp');

            // DELETE may not be registered on older laravel/mcp versions (<0.7.1).
            // Skip silently if not present; assert middleware on whatever exists.
            if ($route === null) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:sanctum', $middleware, "auth:sanctum missing on {$verb}");
            $this->assertContains('throttle:30,1', $middleware, "throttle missing on {$verb}");
        }

        // Sanity: at minimum POST and GET must exist and carry the middleware.
        $this->assertNotNull($this->findRoute('POST', '_boost/mcp'));
        $this->assertNotNull($this->findRoute('GET', '_boost/mcp'));
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
