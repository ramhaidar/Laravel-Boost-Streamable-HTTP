<?php

declare(strict_types=1);

namespace Ramhaidar\LaravelBoostStreamableHttp\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route as Router;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Boost\Mcp\Boost;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Mcp\Response;
use Mockery;
use Psr\Log\LoggerInterface;
use Ramhaidar\LaravelBoostStreamableHttp\LaravelBoostStreamableHttpServiceProvider;
use ReflectionMethod;
use RuntimeException;
use stdClass;

class LaravelBoostStreamableHttpServiceProviderTest extends TestCase
{
    /** @var array<string, mixed> */
    protected array $configOverrides = [];

    protected ?string $forcedEnvironment = null;

    protected bool $spyLog = false;

    public function test_disabled_by_default_does_not_register_any_route(): void
    {
        $this->assertNull($this->findRoute('POST', '_boost/mcp'));
        $this->assertNull($this->findRoute('GET', '_boost/mcp'));
        $this->assertNull($this->findRoute('DELETE', '_boost/mcp'));
    }

    public function test_enabling_registers_get_post_and_delete_at_default_path(): void
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

    public function test_middleware_config_applies_to_all_registered_verbs(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => ['auth:sanctum', 'throttle:30,1'],
        ];

        $this->refreshApplication();

        foreach (['POST', 'GET', 'DELETE'] as $verb) {
            $route = $this->findRoute($verb, '_boost/mcp');

            $this->assertNotNull($route, "{$verb} route not registered");

            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:sanctum', $middleware, "auth:sanctum missing on {$verb}");
            $this->assertContains('throttle:30,1', $middleware, "throttle missing on {$verb}");
        }
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

    public function test_refuses_registration_in_production_without_middleware(): void
    {
        $this->forcedEnvironment = 'production';
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => [],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to register the Laravel Boost MCP endpoint in the "production" environment without middleware');

        $this->refreshApplication();
    }

    public function test_refuses_registration_in_configured_protected_environment_without_middleware(): void
    {
        $this->forcedEnvironment = 'staging';
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => [],
            'laravel-boost-streamable-http.protected_environments' => ['staging'],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to register the Laravel Boost MCP endpoint');

        $this->refreshApplication();
    }

    public function test_non_protected_environment_without_middleware_still_registers(): void
    {
        $this->forcedEnvironment = 'local';
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => [],
        ];

        $this->refreshApplication();

        $this->assertNotNull($this->findRoute('POST', '_boost/mcp'));
    }

    public function test_allows_unprotected_registration_in_production_with_escape_hatch_and_warns(): void
    {
        $this->forcedEnvironment = 'production';
        $this->spyLog = true;
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => [],
            'laravel-boost-streamable-http.allow_unprotected_in_production' => true,
        ];

        $this->refreshApplication();

        $this->assertNotNull($this->findRoute('POST', '_boost/mcp'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message): bool => str_contains($message, 'laravel-boost-streamable-http'));
    }

    public function test_warns_in_configured_protected_environment_with_escape_hatch(): void
    {
        $this->forcedEnvironment = 'staging';
        $this->spyLog = true;
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.middleware' => [],
            'laravel-boost-streamable-http.protected_environments' => ['staging'],
            'laravel-boost-streamable-http.allow_unprotected_in_production' => true,
        ];

        $this->refreshApplication();

        $this->assertNotNull($this->findRoute('POST', '_boost/mcp'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message): bool => str_contains($message, '"staging"'));
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
            'laravel-boost-streamable-http.allow_unprotected_in_production' => true,
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
                'capabilities' => new stdClass,
                'clientInfo' => ['name' => 'pkg-test', 'version' => '0.0.1'],
            ],
        ];

        $response = $this->postJson('/_boost/mcp', $payload, [
            'Accept' => 'application/json, text/event-stream',
        ]);

        // A successful initialize must return an actual MCP result, not just
        // "anything that is not a 4xx/5xx".
        $this->assertSame(200, $response->getStatusCode(), 'Endpoint must return a successful initialize response');

        $json = $this->extractFirstJsonObject((string) $response->getContent());

        $this->assertIsArray($json, 'Response body did not contain a JSON object');
        $this->assertSame('2.0', $json['jsonrpc'] ?? null, 'jsonrpc field missing or wrong');
        $this->assertSame(1, $json['id'] ?? null, 'id field missing or did not echo');
        $this->assertArrayHasKey('result', $json, 'JSON-RPC response must contain a result');
        $this->assertSame('Laravel Boost', $json['result']['serverInfo']['name'] ?? null, 'serverInfo.name missing or wrong');
        $this->assertSame('2025-06-18', $json['result']['protocolVersion'] ?? null, 'protocolVersion missing or wrong');
    }

    public function test_tools_list_returns_boost_tools(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
        ];

        $this->refreshApplication();

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
            'params' => new stdClass,
        ];

        $response = $this->postJson('/_boost/mcp', $payload, [
            'Accept' => 'application/json, text/event-stream',
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'tools/list must return a successful response');

        $json = $this->extractFirstJsonObject((string) $response->getContent());

        $this->assertIsArray($json, 'Response body did not contain a JSON object');
        $this->assertSame('2.0', $json['jsonrpc'] ?? null);
        $this->assertArrayHasKey('result', $json, 'JSON-RPC response must contain a result');

        $tools = $json['result']['tools'] ?? null;
        $this->assertIsArray($tools, 'tools/list result must contain a tools array');
        $this->assertNotEmpty($tools, 'tools/list must expose at least one Boost tool');

        $names = array_column($tools, 'name');
        $this->assertContains('application-info', $names, 'Boost tools must be exposed over HTTP');
    }

    public function test_tools_call_reaches_boost_executor(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
        ];

        $this->refreshApplication();

        // Boost runs each tool in a subprocess; that cannot run inside the
        // testbench skeleton. Mock the executor to prove the full HTTP ->
        // MCP -> tools/call -> Boost executor wiring works end to end.
        $executor = Mockery::mock(ToolExecutor::class);
        $executor->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($toolClass, $arguments): bool => $toolClass === ApplicationInfo::class && is_array($arguments))
            ->andReturn(Response::text('probe ok'));

        $this->app->instance(ToolExecutor::class, $executor);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'application-info',
                'arguments' => new stdClass,
            ],
        ];

        $response = $this->postJson('/_boost/mcp', $payload, [
            'Accept' => 'application/json, text/event-stream',
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'tools/call must return a successful response');

        $json = $this->extractFirstJsonObject((string) $response->getContent());

        $this->assertIsArray($json, 'Response body did not contain a JSON object');
        $this->assertSame('2.0', $json['jsonrpc'] ?? null);
        $this->assertSame(3, $json['id'] ?? null);
        $this->assertArrayHasKey('result', $json, 'JSON-RPC response must contain a result');
        $this->assertFalse($json['result']['isError'] ?? true, 'Tool call must not report an error');
        $this->assertIsArray($json['result']['content'] ?? null, 'Tool call result must contain content');
    }

    public function test_provider_loads_alongside_boost_without_replacing_it(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(BoostServiceProvider::class));
        $this->assertTrue($this->app->providerIsLoaded(LaravelBoostStreamableHttpServiceProvider::class));
        $this->assertTrue(class_exists(Boost::class));
    }

    public function test_explicit_php_binary_is_written_to_boost_config(): void
    {
        // Use a path distinct from PHP_BINARY so this test actually proves the
        // explicit override is applied, even when PHP_BINARY already looks like
        // a CLI php binary (the previous weak assertion masked finding #3).
        $expected = '/custom/path/to/php8.3';

        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.php_binary' => $expected,
            'boost.executable_paths.php' => null,
        ];

        $this->refreshApplication();

        $actual = config('boost.executable_paths.php');

        $this->assertSame($expected, $actual, 'The explicit php_binary override must be written to boost.executable_paths.php');
    }

    public function test_explicit_php_binary_takes_priority_over_cli_php_binary(): void
    {
        // Under CLI test runs PHP_BINARY already looks like a CLI php binary.
        // The explicit override must still win (finding #3).
        $expected = '/custom/path/to/php8.3';

        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.php_binary' => $expected,
            'boost.executable_paths.php' => null,
        ];

        $this->refreshApplication();

        $this->assertSame($expected, config('boost.executable_paths.php'));
    }

    public function test_blank_boost_executable_path_is_repaired(): void
    {
        // Boost issue #930: a blank BOOST_PHP_EXECUTABLE_PATH= is treated as a
        // real empty executable path. When PHP_BINARY is a valid CLI binary the
        // provider must repair the blank instead of leaving it untouched.
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'boost.executable_paths.php' => '',
        ];

        $this->refreshApplication();

        $this->assertSame(PHP_BINARY, config('boost.executable_paths.php'));
    }

    public function test_whitespace_only_boost_executable_path_is_repaired(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'boost.executable_paths.php' => '   ',
        ];

        $this->refreshApplication();

        $this->assertSame(PHP_BINARY, config('boost.executable_paths.php'));
    }

    public function test_existing_boost_executable_path_is_not_overwritten(): void
    {
        $existing = '/custom/path/to/php';

        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.php_binary' => '/some/other/php',
            'boost.executable_paths.php' => $existing,
        ];

        $this->refreshApplication();

        $this->assertSame($existing, config('boost.executable_paths.php'));
    }

    public function test_auto_resolve_can_be_disabled(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.auto_resolve_php_binary' => false,
            'laravel-boost-streamable-http.php_binary' => '/should/not/apply/php',
            'boost.executable_paths.php' => null,
        ];

        $this->refreshApplication();

        $this->assertNull(config('boost.executable_paths.php'));
    }

    public function test_path_must_not_be_empty(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.path' => '',
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('path must not be empty');

        $this->refreshApplication();
    }

    public function test_path_must_not_be_root(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.path' => '/',
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be the root path');

        $this->refreshApplication();
    }

    public function test_path_must_not_contain_url_scheme(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.path' => 'https://evil.example/_boost/mcp',
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not contain a URL scheme');

        $this->refreshApplication();
    }

    public function test_path_must_not_be_protocol_relative(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.path' => '//evil.example/_boost/mcp',
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be protocol-relative');

        $this->refreshApplication();
    }

    public function test_path_must_not_contain_query_or_fragment(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.path' => '/_boost/mcp?admin=1',
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not contain a query string or fragment');

        $this->refreshApplication();
    }

    public function test_path_is_normalized_before_registration(): void
    {
        $this->configOverrides = [
            'laravel-boost-streamable-http.enabled' => true,
            'laravel-boost-streamable-http.path' => '  /_boost/mcp  ',
        ];

        $this->refreshApplication();

        $this->assertNotNull($this->findRoute('POST', '_boost/mcp'));
    }

    public function test_boost_version_below_242_is_rejected(): void
    {
        $provider = new LaravelBoostStreamableHttpServiceProvider($this->app);
        $method = new ReflectionMethod($provider, 'assertBoostVersionSupportsExecutablePathsConfig');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not honor boost.executable_paths.php');

        $method->invoke($provider, '2.4.1');
    }

    public function test_boost_version_242_or_newer_is_accepted(): void
    {
        $provider = new LaravelBoostStreamableHttpServiceProvider($this->app);
        $method = new ReflectionMethod($provider, 'assertBoostVersionSupportsExecutablePathsConfig');

        $method->invoke($provider, '2.4.2');
        $method->invoke($provider, '2.4.5');
        $method->invoke($provider, '2.4.6');
        $method->invoke($provider, '2.7.0');

        $this->assertTrue(true);
    }

    public function test_boost_version_unknown_or_dev_is_accepted(): void
    {
        $provider = new LaravelBoostStreamableHttpServiceProvider($this->app);
        $method = new ReflectionMethod($provider, 'assertBoostVersionSupportsExecutablePathsConfig');

        $method->invoke($provider, null);
        $method->invoke($provider, '');
        $method->invoke($provider, 'dev-main');

        $this->assertTrue(true);
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        if ($this->forcedEnvironment !== null) {
            $app->detectEnvironment(fn (): string => $this->forcedEnvironment);
        }

        foreach ($this->configOverrides as $key => $value) {
            $app['config']->set($key, $value);
        }

        if ($this->spyLog) {
            Log::swap(Mockery::spy(LoggerInterface::class));
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

    /**
     * Best-effort extraction of the first JSON object from a response body.
     * Handles plain JSON and minimal SSE framing (`data: {...}` lines).
     *
     * @return array<string, mixed>|null
     */
    private function extractFirstJsonObject(string $body): ?array
    {
        $trimmed = trim($body);

        if ($trimmed === '') {
            return null;
        }

        if ($trimmed[0] === '{') {
            $decoded = json_decode($trimmed, true);

            return is_array($decoded) ? $decoded : null;
        }

        // Try SSE: pick the first `data: ...` line containing a JSON object.
        foreach (preg_split('/\r?\n/', $trimmed) ?: [] as $line) {
            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = trim(substr($line, 5));

            if ($payload === '' || $payload[0] !== '{') {
                continue;
            }

            $decoded = json_decode($payload, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
