<?php

namespace SoftHouse\MonitoringService;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SoftHouse\MonitoringService\Contracts\EntriesRepository;
use SoftHouse\MonitoringService\Http\Livewire\HomeLivewire;
use SoftHouse\MonitoringService\Storage\DatabaseEntriesRepository;

class MonitoringServiceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        MonitoringService::start($this->app);
        MonitoringService::listenForStorageOpportunities($this->app);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('monitoring-service.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'monitoring-service');

        $this->app->singleton('monitoring-service', function () {
            return new MonitoringService;
        });

        $this->registerStorageDriver();
    }

    protected function registerStorageDriver()
    {
        $driver = config('monitoring-service.driver');

        if (method_exists($this, $method = 'register'.ucfirst($driver).'Driver')) {
            $this->$method();
        }
    }

    protected function registerDatabaseDriver()
    {
        $this->app->singleton(
            EntriesRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give(config('monitoring-service.storage.database.connection'));

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$chunkSize')
            ->give(config('monitoring-service.storage.database.chunk'));
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('monitoring-service.route.prefix'),
            'middleware' => config('monitoring-service.route.middleware'),
        ];
    }
}
