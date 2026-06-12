<?php

declare(strict_types=1);

namespace Cbu\Currency\Tests;

use Cbu\Currency\CbuCurrencyServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        $providers = [];

        // laravel/mcp is an optional dependency — register its provider
        // (before ours) only when it is installed.
        if (class_exists(McpServiceProvider::class)) {
            $providers[] = McpServiceProvider::class;
        }

        $providers[] = CbuCurrencyServiceProvider::class;

        return $providers;
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set CBU Currency config
        $app['config']->set('cbu-currency.source', 'database');
        $app['config']->set('cbu-currency.scale', 0);
        $app['config']->set('cbu-currency.cache_duration', null);
        $app['config']->set('cbu-currency.log_enabled', false);
        $app['config']->set('cbu-currency.routes.enabled', true);
        $app['config']->set('cbu-currency.routes.prefix', 'api/cbu');
        $app['config']->set('cbu-currency.routes.middleware', []);
    }
}
