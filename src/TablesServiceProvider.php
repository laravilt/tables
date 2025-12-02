<?php

namespace Laravilt\Tables;

use Illuminate\Support\ServiceProvider;

class TablesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravilt-tables.php',
            'laravilt-tables'
        );

        // Register any services, bindings, or singletons here
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'tables');


        // Load web routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');


        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/laravilt-tables.php' => config_path('laravilt-tables.php'),
            ], 'laravilt-tables-config');

            // Publish assets
            $this->publishes([
                __DIR__ . '/../dist' => public_path('vendor/laravilt/tables'),
            ], 'laravilt-tables-assets');


            // Register commands
            $this->commands([
                Commands\InstallTablesCommand::class,
                Commands\MakeTableCommand::class,
            ]);
        }
    }
}
