<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp\Tests;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route as Router;

class LaravelBoostStreamableHttpServiceProviderTest extends TestCase
{
    /** @var array<string, mixed> */
    protected array $configOverrides = [];

    /** @var string|null */
    protected ?string $forcedEnvironment = null;

    protected bool $spyLog = false;

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

        foreach (['POST', 'GET', 'DELETE'] as $verb) {
            $route = $this->findRoute($verb, '_boost/mcp');

            // DELETE may not be registered on older laravel/mcp versions (<0.7.1).
            if ($route === null) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:sanctum', $middleware, "auth:sanctum missing on {$verb}");
            $this->assertContains('throttle:30,1', $middleware, "throttle missing on {$verb}");
        }

        $this->assertNotNull($this->findRoute('POST', '_boost/mcp'));
        $this->assertNotNull($this->findRoute('GET', '_boost/mcp'));
    }

    public function test_route_prefix_is_applied(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.prefix' => 'api/v1',
        ];

        $this->refreshApplication();

        $this->assertNotNull($this->findRoute('POST', 'api/v1/_boost/mcp'));
        $this->assertNull($this->findRoute('POST', '_boost/mcp'));
    }

    public function test_route_name_prefix_is_applied(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.as' => 'mcp.boost.',
        ];

        $this->refreshApplication();

        $route = $this->findRoute('POST', '_boost/mcp');

        $this->assertNotNull($route);
        $this->assertNotNull($route->getName());
        $this->assertStringStartsWith('mcp.boost.', (string) $route->getName());
    }

    public function test_route_domain_is_applied(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.domain' => 'mcp.example.test',
        ];

        $this->refreshApplication();

        $route = $this->findRoute('POST', '_boost/mcp');

        $this->assertNotNull($route);
        $this->assertSame('mcp.example.test', $route->getDomain());
    }

    public function test_warn_log_emitted_when_enabled_in_production_without_middleware(): void
    {
        $this->forcedEnvironment = 'production';
        $this->spyLog = true;
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => [],
        ];

        $this->refreshApplication();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message): bool => str_contains($message, 'laravel-boost-streamable-http'));
    }

    public function test_warn_log_not_emitted_in_production_when_middleware_configured(): void
    {
        $this->forcedEnvironment = 'production';
        $this->spyLog = true;
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => ['auth:sanctum'],
        ];

        $this->refreshApplication();

        Log::shouldNotHaveReceived('warning');
    }

    public function test_warn_log_can_be_disabled(): void
    {
        $this->forcedEnvironment = 'production';
        $this->spyLog = true;
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => [],
            'laravel-boost-streamable-http.warn_unprotected_in_production' => false,
        ];

        $this->refreshApplication();

        Log::shouldNotHaveReceived('warning');
    }

    public function test_endpoint_responds_to_jsonrpc_initialize(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
        ];

        $this->refreshApplication();

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => new \stdClass,
                'clientInfo' => ['name' => 'pkg-test', 'version' => '0.0.1'],
            ],
        ];

        $response = $this->postJson('/_boost/mcp', $payload, [
            'Accept' => 'application/json, text/event-stream',
        ]);

        // The endpoint must be wired and reachable (no 404, no 405).
        $this->assertNotSame(404, $response->getStatusCode(), 'Endpoint not registered');
        $this->assertNotSame(405, $response->getStatusCode(), 'POST not accepted');
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
        if ($this->forcedEnvironment !== null) {
            $app->detectEnvironment(fn (): string => $this->forcedEnvironment);
        }

        foreach ($this->configOverrides as $key => $value) {
            $app['config']->set($key, $value);
        }

        if ($this->spyLog) {
            Log::swap(\Mockery::spy(\Psr\Log\LoggerInterface::class));
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
