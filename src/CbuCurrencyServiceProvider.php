<?php

declare(strict_types=1);

namespace Cbu\Currency;

use Cbu\Currency\Console\Commands\SyncCurrenciesCommand;
use Cbu\Currency\Console\Commands\SyncRatesCommand;
use Cbu\Currency\Repositories\ApiCurrencyRepository;
use Cbu\Currency\Repositories\DatabaseCurrencyRepository;
use Cbu\Currency\Services\CbuCurrency;
use Illuminate\Support\ServiceProvider;

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

        $this->app->singleton(ApiCurrencyRepository::class, function ($app) {
            return new ApiCurrencyRepository;
        });

        $this->app->singleton(DatabaseCurrencyRepository::class, function ($app) {
            return new DatabaseCurrencyRepository($app->make(ApiCurrencyRepository::class));
        });

        $this->app->singleton('cbu-currency', function ($app) {
            return new CbuCurrency;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();

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
     */
    protected function registerRoutes(): void
    {
        $routeConfig = config('cbu-currency.routes', [
            'prefix' => 'api/currency',
            'middleware' => ['api'],
        ]);

        \Illuminate\Support\Facades\Route::group([
            'prefix' => $routeConfig['prefix'],
            'middleware' => $routeConfig['middleware'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}
