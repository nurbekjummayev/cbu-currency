<?php

declare(strict_types=1);

namespace Cbu\Currency;

use Cbu\Currency\Console\Commands\SyncCurrenciesCommand;
use Cbu\Currency\Console\Commands\SyncRatesCommand;
use Cbu\Currency\Enums\CurrencySource;
use Cbu\Currency\Mcp\CbuCurrencyServer;
use Cbu\Currency\Repositories\ApiCurrencyRepository;
use Cbu\Currency\Repositories\DatabaseCurrencyRepository;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;
use Cbu\Currency\Services\CbuCurrency;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server;

class CbuCurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/cbu-currency.php', 'cbu-currency'
        );

        $this->app->singleton(ApiCurrencyRepository::class);

        $this->app->singleton(DatabaseCurrencyRepository::class, function ($app) {
            return new DatabaseCurrencyRepository($app->make(ApiCurrencyRepository::class));
        });

        // Default repository is resolved from the configured source (api or database)
        $this->app->bind(CurrencyRepositoryInterface::class, function ($app) {
            $source = CurrencySource::from(config('cbu-currency.source', 'api'));

            return $source === CurrencySource::API
                ? $app->make(ApiCurrencyRepository::class)
                : $app->make(DatabaseCurrencyRepository::class);
        });

        $this->app->singleton(CbuCurrency::class, function ($app) {
            return new CbuCurrency($app->make(CurrencyRepositoryInterface::class));
        });

        $this->app->alias(CbuCurrency::class, 'cbu-currency');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();
        $this->registerMcpServer();

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncCurrenciesCommand::class,
                SyncRatesCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/cbu-currency.php' => config_path('cbu-currency.php'),
            ], 'cbu-currency-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'cbu-currency-migrations');
        }
    }

    /**
     * Register package routes
     *
     * Loads API routes with configured prefix and middleware.
     * Routes can be disabled entirely via the cbu-currency.routes.enabled config.
     */
    protected function registerRoutes(): void
    {
        $routeConfig = config('cbu-currency.routes', [
            'enabled' => true,
            'prefix' => 'api/currency',
            'middleware' => ['api'],
        ]);

        if (! ($routeConfig['enabled'] ?? true)) {
            return;
        }

        Route::group([
            'prefix' => $routeConfig['prefix'],
            'middleware' => $routeConfig['middleware'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    /**
     * Register the MCP server for AI agents
     *
     * Only active when the optional laravel/mcp package is installed and
     * the cbu-currency.mcp.enabled config is not disabled. Registers a
     * local (stdio) server and, optionally, a web (HTTP) server.
     */
    protected function registerMcpServer(): void
    {
        if (! class_exists(Server::class)) {
            return;
        }

        $config = config('cbu-currency.mcp', []);

        if (! ($config['enabled'] ?? true)) {
            return;
        }

        Mcp::local(
            $config['name'] ?? 'cbu-currency',
            CbuCurrencyServer::class
        );

        if ($config['web']['enabled'] ?? false) {
            Mcp::web(
                $config['web']['path'] ?? '/mcp/cbu-currency',
                CbuCurrencyServer::class
            )->middleware($config['web']['middleware'] ?? ['api']);
        }
    }
}
